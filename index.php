<?php
/**
 * index.php — TECHMOBILE Storefront (Vibrant Light Mode 2026)
 */
session_start();
require_once 'db.php';

/* ── Add to Cart ─────────────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['product_id'])) {
    $pid = (int) $_POST['product_id'];
    if ($pid > 0) $_SESSION['cart'][] = $pid;
    header('Location: index.php' . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : ''));
    exit;
}

/* ── Brands & Products ───────────────────────────────────────── */
$brands  = $pdo->query('SELECT DISTINCT brand FROM products ORDER BY brand ASC')->fetchAll(PDO::FETCH_COLUMN);
$search  = trim($_GET['q']     ?? '');
$brand   = trim($_GET['brand'] ?? '');

$sql = 'SELECT * FROM products WHERE 1=1';
$p   = [];
if ($search !== '') { $sql .= ' AND (name LIKE ? OR specs LIKE ? OR brand LIKE ?)'; $p[] = "%$search%"; $p[] = "%$search%"; $p[] = "%$search%"; }
if ($brand  !== '') { $sql .= ' AND brand = ?'; $p[] = $brand; }
$sql .= ' ORDER BY id ASC';
$stmt = $pdo->prepare($sql);
$stmt->execute($p);
$products   = $stmt->fetchAll();
$cart_count = count($_SESSION['cart'] ?? []);
$user       = $_SESSION['user'] ?? null;
$isAdmin    = (($user['role'] ?? '') === 'admin');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TECHMOBILE — India's #1 Smartphone Store</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .hero-gradient { background: linear-gradient(135deg, #4c1d95 0%, #6d28d9 50%, #4f46e5 100%); }
        .chip-active { background-color: #7c3aed; color: #ffffff; border-color: #7c3aed; }
        .product-card { transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1); }
        .product-card:hover { transform: translateY(-4px); }
    </style>
</head>
<body class="bg-slate-50 min-h-screen flex flex-col justify-between">

<!-- ── Glassmorphism Navbar ──────────────────────────────────── -->
<header class="bg-white/80 backdrop-blur-md border-b border-slate-200/80 sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 h-16 flex items-center justify-between gap-4">

        <!-- Logo -->
        <a href="index.php" class="flex items-center gap-2 text-slate-900 no-underline shrink-0">
            <img src="uploads/logo.png" alt="TECHMOBILE" class="h-12 w-auto object-contain">
            <span class="font-extrabold text-lg tracking-tight">TECH<span class="text-violet-600">MOBILE</span></span>
        </a>

        <!-- Center Nav -->
        <nav class="hidden md:flex items-center gap-1">
            <a href="index.php" class="text-sm font-semibold text-violet-700 bg-violet-50 px-3 py-1.5 rounded-lg">Home</a>
            
            <?php if ($user): ?>
                <?php if ($isAdmin): ?>
                    <!-- Admin Panel Link -->
                    <a href="admin_dashboard.php" class="text-xs font-bold text-violet-700 bg-violet-100 border border-violet-200 px-3 py-1.5 rounded-lg hover:bg-violet-200 transition-colors flex items-center gap-1">
                        🛡️ Admin Panel
                    </a>
                <?php else: ?>
                    <!-- Customer My Orders Link -->
                    <a href="user_dashboard.php" class="text-sm font-medium text-slate-600 hover:text-slate-900 hover:bg-slate-100 px-3 py-1.5 rounded-lg transition-colors">My Orders</a>
                <?php endif; ?>
            <?php else: ?>
                <a href="login.php"    class="text-sm font-medium text-slate-600 hover:text-slate-900 hover:bg-slate-100 px-3 py-1.5 rounded-lg transition-colors">Sign In</a>
                <a href="register.php" class="text-sm font-medium text-slate-600 hover:text-slate-900 hover:bg-slate-100 px-3 py-1.5 rounded-lg transition-colors">Register</a>
            <?php endif; ?>
        </nav>

        <!-- Right Actions -->
        <div class="flex items-center gap-2 shrink-0">
            <?php if ($user): ?>
                <span class="hidden sm:flex items-center gap-1.5 text-sm text-slate-600">
                    <span class="w-7 h-7 rounded-full bg-violet-100 flex items-center justify-center text-violet-700 font-bold text-xs shrink-0">
                        <?= strtoupper(substr($user['name'], 0, 1)) ?>
                    </span>
                    <span class="font-semibold text-slate-800 max-w-[100px] truncate"><?= htmlspecialchars($user['name']) ?></span>
                </span>
                <a href="logout.php" class="text-xs font-semibold text-slate-500 border border-slate-200 px-3 py-1.5 rounded-lg hover:bg-slate-100 transition-colors">Logout</a>
            <?php endif; ?>
            
            <!-- Hide Cart Button for Admin Users -->
            <?php if (!$isAdmin): ?>
                <a href="cart.php" class="flex items-center gap-1.5 bg-violet-600 hover:bg-violet-700 text-white text-sm font-bold px-4 py-2 rounded-full transition-colors shadow-sm shadow-violet-500/25">
                    🛒
                    <span class="bg-white/20 text-white text-xs font-black rounded-full min-w-[20px] h-5 flex items-center justify-center px-1 leading-none"><?= $cart_count ?></span>
                </a>
            <?php endif; ?>
        </div>
    </div>
</header>

<!-- ── Main Content Area ───────────────────────────────────────── -->
<div class="flex-1">

    <!-- ── Hero Section ──────────────────────────────────────────── -->
    <section class="hero-gradient mx-3 mt-4 sm:mx-6 rounded-3xl text-white overflow-hidden shadow-xl shadow-violet-500/20">
        <div class="relative z-10 max-w-3xl mx-auto text-center px-6 py-16 sm:py-20">
            <span class="inline-flex items-center gap-1.5 bg-white/15 border border-white/25 text-violet-100 text-xs font-bold tracking-widest uppercase px-4 py-1.5 rounded-full mb-5 backdrop-blur-sm">
                ✦ Flagship Deals · 2026
            </span>
            <h1 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold leading-tight tracking-tight mb-4">
                India's Best <span class="text-emerald-400">Smartphones</span><br class="hidden sm:block"> Delivered to Your Door
            </h1>
            <p class="text-white/90 text-base sm:text-lg max-w-lg mx-auto mb-8">
                Shop the latest flagships, mid-rangers, and budget picks — all with <strong class="text-white">Cash on Delivery</strong>.
            </p>

            <!-- Search Bar -->
            <form method="GET" action="index.php" class="flex flex-col sm:flex-row gap-3 max-w-xl mx-auto">
                <input type="text" name="q" value="<?= htmlspecialchars($search) ?>"
                    placeholder="🔍 Search phones, specs, brands..."
                    class="flex-1 px-4 py-3 rounded-xl bg-white/12 border border-white/30 text-white placeholder-slate-300 focus:outline-none focus:ring-2 focus:ring-violet-300 focus:bg-white/18 transition text-sm backdrop-blur-sm"
                    autocomplete="off">
                <button type="submit" class="bg-emerald-500 hover:bg-emerald-600 text-white font-bold px-6 py-2.5 rounded-xl transition-colors text-sm shrink-0 shadow-sm shadow-emerald-500/30">Search</button>
                <?php if ($search || $brand): ?>
                    <a href="index.php" class="bg-white/15 hover:bg-white/25 text-white font-semibold px-4 py-2.5 rounded-xl transition-colors text-sm text-center shrink-0 border border-white/20">✕ Clear</a>
                <?php endif; ?>
            </form>
        </div>
    </section>

    <!-- ── Catalog ───────────────────────────────────────────────── -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 py-8">

        <!-- Brand Filter Chips -->
        <div class="flex flex-wrap gap-2 mb-6">
            <a href="index.php<?= $search ? '?q=' . urlencode($search) : '' ?>"
               class="px-4 py-1.5 rounded-full text-sm font-semibold border transition-colors <?= $brand === '' ? 'chip-active' : 'bg-white text-slate-600 border-slate-200 hover:border-violet-400 hover:text-violet-600' ?>">
                All
            </a>
            <?php foreach ($brands as $b): ?>
                <a href="index.php?brand=<?= urlencode($b) ?><?= $search ? '&q=' . urlencode($search) : '' ?>"
                   class="px-4 py-1.5 rounded-full text-sm font-semibold border transition-colors <?= $brand === $b ? 'chip-active' : 'bg-white text-slate-600 border-slate-200 hover:border-violet-400 hover:text-violet-600' ?>">
                    <?= htmlspecialchars($b) ?>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- Results Header -->
        <div class="flex items-center justify-between mb-5">
            <h2 class="text-xl font-extrabold text-slate-900">
                <?= ($search || $brand) ? '🔍 Search Results' : '📦 All Products' ?>
                <span class="ml-2 text-sm font-medium text-slate-400"><?= count($products) ?> found</span>
            </h2>
        </div>

        <?php if (empty($products)): ?>
            <!-- Empty State -->
            <div class="bg-white border border-slate-200 rounded-2xl text-center py-20 px-6 shadow-sm">
                <div class="text-6xl mb-4">📭</div>
                <h2 class="text-xl font-extrabold text-slate-900 mb-2">No Products Found</h2>
                <p class="text-slate-500 mb-6">Try a different search term or browse all products.</p>
                <a href="index.php" class="inline-flex items-center gap-2 bg-violet-600 hover:bg-violet-700 text-white font-bold px-6 py-3 rounded-xl transition-colors shadow-sm shadow-violet-500/20">Browse All</a>
            </div>
        <?php else: ?>
            <!-- Product Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5">
                <?php foreach ($products as $prod): ?>
                    <?php $stock = (int)($prod['stock'] ?? 0); ?>
                    <article class="product-card bg-white border border-slate-200/80 rounded-2xl overflow-hidden flex flex-col shadow-lg shadow-violet-500/5 group">

                        <!-- Image / Fallback -->
                        <a href="product.php?id=<?= (int)$prod['id'] ?>" class="block h-52 flex items-center justify-center bg-gradient-to-br from-slate-50 to-violet-50/40 border-b border-slate-100 overflow-hidden">
                            <?php if (!empty($prod['image_url'])): ?>
                                <img src="<?= htmlspecialchars($prod['image_url']) ?>"
                                     alt="<?= htmlspecialchars($prod['name']) ?>"
                                     class="w-full h-full object-contain p-4 group-hover:scale-105 transition-transform duration-300"
                                     loading="lazy"
                                     onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                                <span class="hidden w-full h-full items-center justify-center text-5xl" aria-hidden="true">📱</span>
                            <?php else: ?>
                                <span class="w-full h-full flex items-center justify-center text-5xl" aria-hidden="true">📱</span>
                            <?php endif; ?>
                        </a>

                        <!-- Card Body -->
                        <div class="p-4 flex flex-col flex-1 gap-1.5">
                            <!-- Brand Badge -->
                            <span class="text-xs font-extrabold uppercase tracking-wider text-violet-600"><?= htmlspecialchars($prod['brand']) ?></span>

                            <!-- Name -->
                            <h3 class="text-sm font-bold text-slate-900 leading-snug line-clamp-2">
                                <a href="product.php?id=<?= (int)$prod['id'] ?>" class="hover:text-violet-700 transition-colors"><?= htmlspecialchars($prod['name']) ?></a>
                            </h3>

                            <!-- Specs Badge (Fixed Text Wrapping) -->
                            <?php if (!empty($prod['specs'])): ?>
                                <span class="block w-full text-[11px] leading-snug text-violet-700 bg-violet-50 border border-violet-100/80 rounded-lg px-2.5 py-1.5 mt-0.5 font-medium break-words line-clamp-2">
                                    ⚡ <?= htmlspecialchars($prod['specs']) ?>
                                </span>
                            <?php endif; ?>

                            <!-- Stock Badge -->
                            <?php if ($stock > 0): ?>
                                <span class="inline-flex w-fit items-center gap-1 text-xs font-semibold text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-md px-2 py-0.5">
                                    ✓ In Stock (<?= $stock ?>)
                                </span>
                            <?php else: ?>
                                <span class="inline-flex w-fit items-center gap-1 text-xs font-semibold text-red-600 bg-red-50 border border-red-200 rounded-md px-2 py-0.5">
                                    ✕ Out of Stock
                                </span>
                            <?php endif; ?>

                            <!-- Price -->
                            <div class="text-xl font-extrabold text-emerald-600 mt-auto pt-2">
                                <?= formatINR((float)$prod['price']) ?>
                            </div>

                            <!-- Actions Row: Role Dependent -->
                            <div class="flex gap-2 mt-1">
                                <a href="product.php?id=<?= (int)$prod['id'] ?>"
                                   class="flex-1 text-center py-2.5 rounded-xl text-xs font-bold border border-violet-200 text-violet-600 hover:bg-violet-50 transition-colors">
                                    View Details
                                </a>

                                <?php if ($isAdmin): ?>
                                    <!-- Admin Shortcut: Edit Product -->
                                    <a href="admin_products.php?edit=<?= (int)$prod['id'] ?>"
                                       class="flex-1 text-center py-2.5 rounded-xl text-xs font-bold bg-amber-500 hover:bg-amber-600 text-white transition-colors shadow-sm">
                                        ✏️ Edit
                                    </a>
                                <?php else: ?>
                                    <!-- Customer Action: Add to Cart -->
                                    <form method="POST" action="index.php?<?= htmlspecialchars(http_build_query(['q' => $search, 'brand' => $brand])) ?>" class="flex-1">
                                        <input type="hidden" name="product_id" value="<?= (int)$prod['id'] ?>">
                                        <button type="submit"
                                            class="w-full py-2.5 rounded-xl text-xs font-bold transition-all duration-200 <?= $stock > 0 ? 'bg-violet-600 hover:bg-violet-700 text-white shadow-sm active:scale-95' : 'bg-slate-100 text-slate-400 cursor-not-allowed' ?>"
                                            <?= $stock <= 0 ? 'disabled' : '' ?>>
                                            <?= $stock > 0 ? '🛒 Add' : 'Out of Stock' ?>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>
</div>

<!-- ── Footer ───────────────────────────────────────────────── -->
<footer class="bg-slate-900 text-slate-500 text-center py-6 mt-12 text-sm">
    <p>&copy; <?= date('Y') ?> <strong class="text-slate-300">TECHMOBILE</strong>. All prices in Indian Rupees (₹). Cash on Delivery only.</p>
</footer>

</body>
</html>