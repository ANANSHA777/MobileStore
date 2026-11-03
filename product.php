<?php
/**
 * product.php — Product Details Page (Vibrant Light Mode 2026)
 * Dynamic single-product view with add-to-cart, qty selector, and specs breakdown.
 */
session_start();
require_once 'db.php';

/* ── Fetch Product ───────────────────────────────────────────── */
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { header('Location: index.php'); exit; }

$stmt = $pdo->prepare('SELECT * FROM products WHERE id = ? LIMIT 1');
$stmt->execute([$id]);
$prod = $stmt->fetch();
if (!$prod) { header('Location: index.php'); exit; }

$stock      = (int)($prod['stock'] ?? 0);
$cart_count = count($_SESSION['cart'] ?? []);
$user       = $_SESSION['user'] ?? null;
$isAdmin    = (($user['role'] ?? '') === 'admin');

/* ── Add to Cart (Customers Only) ────────────────────────────── */
$added = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart']) && !$isAdmin) {
    $qty = max(1, min((int)($_POST['qty'] ?? 1), $stock));
    for ($i = 0; $i < $qty; $i++) {
        $_SESSION['cart'][] = $id;
    }
    $cart_count = count($_SESSION['cart']);
    $added = true;
}

/* ── Specs Parsing ───────────────────────────────────────────── */
$spec_bullets = [];
if (!empty($prod['specs'])) {
    $raw_parts = preg_split('/\s*[\|,;\/]\s*/', $prod['specs']);
    foreach ($raw_parts as $part) {
        $part = trim($part);
        if ($part !== '') $spec_bullets[] = $part;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($prod['name']) ?> — TECHMOBILE</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .qty-btn {
            width: 2rem;
            height: 2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            color: #475569;
            border-radius: 0.5rem;
            transition: all 0.2s;
        }
        .qty-btn:hover { background-color: #e2e8f0; color: #0f172a; }
    </style>
</head>
<body class="bg-slate-50 min-h-screen flex flex-col justify-between">

<!-- ── Navbar ───────────────────────────────────────────────── -->
<header class="bg-white/80 backdrop-blur-md border-b border-slate-200/80 sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 h-16 flex items-center justify-between gap-4">

        <a href="index.php" class="flex items-center gap-2 text-slate-900 no-underline shrink-0">
            <img src="uploads/logo.png" alt="TECHMOBILE" class="h-12 w-auto object-contain">
            <span class="font-extrabold text-lg tracking-tight">TECH<span class="text-violet-600">MOBILE</span></span>
        </a>

        <!-- Center Nav -->
        <nav class="hidden md:flex items-center gap-1">
            <a href="index.php" class="text-sm font-medium text-slate-600 hover:text-slate-900 hover:bg-slate-100 px-3 py-1.5 rounded-lg transition-colors">Home</a>
            
            <?php if ($user): ?>
                <?php if ($isAdmin): ?>
                    <a href="admin_dashboard.php" class="text-xs font-bold text-violet-700 bg-violet-100 border border-violet-200 px-3 py-1.5 rounded-lg hover:bg-violet-200 transition-colors flex items-center gap-1">
                        🛡️ Admin Panel
                    </a>
                <?php else: ?>
                    <a href="user_dashboard.php" class="text-sm font-medium text-slate-600 hover:text-slate-900 hover:bg-slate-100 px-3 py-1.5 rounded-lg transition-colors">My Orders</a>
                <?php endif; ?>
            <?php else: ?>
                <a href="login.php"    class="text-sm font-medium text-slate-600 hover:text-slate-900 hover:bg-slate-100 px-3 py-1.5 rounded-lg transition-colors">Sign In</a>
                <a href="register.php" class="text-sm font-medium text-slate-600 hover:text-slate-900 hover:bg-slate-100 px-3 py-1.5 rounded-lg transition-colors">Register</a>
            <?php endif; ?>
        </nav>

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

            <!-- Hide Cart Badge for Admin Users -->
            <?php if (!$isAdmin): ?>
                <a href="cart.php" class="flex items-center gap-1.5 bg-violet-600 hover:bg-violet-700 text-white text-sm font-bold px-4 py-2 rounded-full transition-colors shadow-sm shadow-violet-500/25">
                    🛒
                    <span class="bg-white/20 text-white text-xs font-black rounded-full min-w-[20px] h-5 flex items-center justify-center px-1 leading-none"><?= $cart_count ?></span>
                </a>
            <?php endif; ?>
        </div>
    </div>
</header>

<main class="max-w-7xl mx-auto px-4 sm:px-6 py-6 flex-1 w-full">

    <!-- ── Breadcrumb ────────────────────────────────────────── -->
    <nav class="flex items-center gap-1.5 text-sm text-slate-500 mb-6 flex-wrap" aria-label="Breadcrumb">
        <a href="index.php" class="hover:text-violet-600 transition-colors font-medium">Home</a>
        <span class="text-slate-300">/</span>
        <a href="index.php?brand=<?= urlencode($prod['brand']) ?>" class="hover:text-violet-600 transition-colors font-medium">Smartphones</a>
        <span class="text-slate-300">/</span>
        <span class="text-slate-900 font-semibold truncate max-w-[200px]"><?= htmlspecialchars($prod['name']) ?></span>
    </nav>

    <!-- ── Added to Cart Banner ──────────────────────────────── -->
    <?php if ($added): ?>
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm font-semibold px-5 py-3 rounded-xl mb-6 flex items-center gap-2">
            ✅ Added to cart!
            <a href="cart.php" class="ml-auto text-xs font-bold text-emerald-600 underline hover:text-emerald-700">View Cart →</a>
        </div>
    <?php endif; ?>

    <!-- ── Main Product Layout ───────────────────────────────── -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 items-start">

        <!-- Left: Image Showcase Frame -->
        <div class="bg-white border border-slate-200/80 rounded-3xl shadow-lg shadow-violet-500/5 overflow-hidden">

            <!-- Brand & Stock Header -->
            <div class="flex items-center justify-between px-6 pt-6 pb-4 border-b border-slate-100">
                <span class="inline-flex items-center gap-1.5 text-xs font-extrabold uppercase tracking-wider text-violet-700 bg-violet-50 border border-violet-100 px-3.5 py-1.5 rounded-full">
                    <?= htmlspecialchars($prod['brand']) ?>
                </span>
                <?php if ($stock > 0): ?>
                    <span class="inline-flex items-center gap-1 text-xs font-bold text-emerald-700 bg-emerald-50 border border-emerald-200 px-3.5 py-1.5 rounded-full">
                        ✓ In Stock: <?= $stock ?> unit<?= $stock !== 1 ? 's' : '' ?>
                    </span>
                <?php else: ?>
                    <span class="inline-flex items-center gap-1 text-xs font-bold text-rose-600 bg-rose-50 border border-rose-200 px-3.5 py-1.5 rounded-full">
                        ✕ Out of Stock
                    </span>
                <?php endif; ?>
            </div>

            <!-- Image Container -->
            <div class="w-full h-96 sm:h-[480px] flex items-center justify-center bg-gradient-to-br from-slate-50 to-violet-50/30 p-6 sm:p-8">
                <?php if (!empty($prod['image_url'])): ?>
                    <img src="<?= htmlspecialchars($prod['image_url']) ?>"
                         alt="<?= htmlspecialchars($prod['name']) ?>"
                         class="max-h-full max-w-full object-contain drop-shadow-xl hover:scale-105 transition-transform duration-300"
                         loading="lazy"
                         onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                    <span class="hidden text-8xl flex items-center justify-center" aria-hidden="true">📱</span>
                <?php else: ?>
                    <span class="text-8xl flex items-center justify-center" aria-hidden="true">📱</span>
                <?php endif; ?>
            </div>
        </div>

        <!-- Right: Product Details -->
        <div class="flex flex-col gap-5">

            <!-- Title & Specs Tag -->
            <div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 leading-tight mb-3">
                    <?= htmlspecialchars($prod['name']) ?>
                </h1>

                <?php if (!empty($prod['specs'])): ?>
                    <span class="inline-flex items-center gap-1.5 text-xs text-violet-700 bg-violet-50 border border-violet-100 rounded-lg px-3 py-1.5 font-semibold">
                        ⚡ <?= htmlspecialchars($prod['specs']) ?>
                    </span>
                <?php endif; ?>
            </div>

            <!-- Price -->
            <div class="bg-slate-50 border border-slate-100 rounded-2xl px-5 py-4">
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wide mb-1">Price</p>
                <p class="text-3xl font-extrabold text-emerald-600"><?= formatINR((float)$prod['price']) ?></p>
                <p class="text-xs text-slate-500 mt-1 font-medium">💵 Cash on Delivery available</p>
            </div>

            <!-- Description -->
            <?php if (!empty($prod['description'])): ?>
                <div class="bg-white border border-slate-200/80 rounded-2xl shadow-sm p-5">
                    <h2 class="text-sm font-extrabold text-slate-900 mb-2 flex items-center gap-2">
                        <span class="w-5 h-5 bg-violet-100 rounded-md flex items-center justify-center text-violet-600 text-xs">📝</span>
                        About this Phone
                    </h2>
                    <p class="text-sm text-slate-600 leading-relaxed"><?= nl2br(htmlspecialchars($prod['description'])) ?></p>
                </div>
            <?php endif; ?>

            <!-- Spec Bullets -->
            <?php if (!empty($spec_bullets)): ?>
                <div class="bg-white border border-slate-200/80 rounded-2xl shadow-sm p-5">
                    <h2 class="text-sm font-extrabold text-slate-900 mb-3 flex items-center gap-2">
                        <span class="w-5 h-5 bg-violet-100 rounded-md flex items-center justify-center text-violet-600 text-xs">⚙</span>
                        Key Specifications
                    </h2>
                    <ul class="space-y-2">
                        <?php foreach ($spec_bullets as $bullet): ?>
                            <li class="flex items-start gap-2.5 text-sm text-slate-700">
                                <span class="w-1.5 h-1.5 rounded-full bg-violet-500 mt-1.5 shrink-0"></span>
                                <span class="font-medium"><?= htmlspecialchars($bullet) ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <!-- Role-Specific Action Card -->
            <?php if ($isAdmin): ?>
                <!-- Admin Card: Edit Shortcut -->
                <div class="bg-amber-50 border border-amber-200 rounded-2xl p-5 shadow-sm space-y-3">
                    <div class="flex items-center gap-2">
                        <span class="text-xl">🛡️</span>
                        <div>
                            <h3 class="text-sm font-bold text-amber-900">Manage Products</h3>
                            <p class="text-xs text-amber-700">Edit/delete Products</p>
                        </div>
                    </div>
                    <a href="admin_products.php?edit=<?= $id ?>" 
                       class="block w-full text-center bg-amber-500 hover:bg-amber-600 text-white font-bold py-3.5 rounded-xl transition-all shadow-sm text-sm">
                        ✏️ Edit Product Details & Stock
                    </a>
                </div>
            <?php elseif ($stock > 0): ?>
                <!-- Customer Form: Add to Cart -->
                <form method="POST" action="product.php?id=<?= $id ?>" class="bg-white border border-slate-200/80 rounded-2xl shadow-sm p-5">
                    <h2 class="text-sm font-extrabold text-slate-900 mb-4 flex items-center gap-2">
                        <span class="w-5 h-5 bg-emerald-100 rounded-md flex items-center justify-center text-emerald-600 text-xs">🛒</span>
                        Add to Cart
                    </h2>

                    <!-- Quantity Selector -->
                    <div class="flex items-center gap-4 mb-4">
                        <span class="text-xs font-bold text-slate-500 uppercase tracking-wide">Quantity</span>
                        <div class="flex items-center gap-2 bg-slate-50 border border-slate-200 rounded-xl px-3 py-1.5">
                            <button type="button" class="qty-btn" id="btn_minus" onclick="changeQty(-1)" aria-label="Decrease quantity">−</button>
                            <input type="number" id="qty_input" name="qty"
                                   value="1" min="1" max="<?= $stock ?>"
                                   class="w-12 text-center text-sm font-extrabold text-slate-800 bg-transparent border-none outline-none"
                                   oninput="clampQty(this)">
                            <button type="button" class="qty-btn" id="btn_plus" onclick="changeQty(1)" aria-label="Increase quantity">+</button>
                        </div>
                        <span class="text-xs text-slate-400 font-medium"><?= $stock ?> available</span>
                    </div>

                    <button type="submit" name="add_to_cart"
                        class="w-full bg-violet-600 hover:bg-violet-700 active:bg-violet-800 text-white font-bold py-3.5 rounded-xl transition-all duration-200 text-base shadow-sm shadow-violet-500/25 active:scale-[0.98] flex items-center justify-center gap-2">
                        🛒 Add to Cart
                    </button>
                </form>
            <?php else: ?>
                <div class="bg-rose-50 border border-rose-200 rounded-2xl p-5 text-center">
                    <p class="text-rose-700 font-bold text-sm mb-2">This product is currently out of stock.</p>
                    <a href="index.php" class="text-sm text-violet-600 font-semibold hover:underline">Browse other products →</a>
                </div>
            <?php endif; ?>

            <!-- Back to Store -->
            <a href="index.php" class="inline-flex items-center gap-1.5 text-sm font-semibold text-slate-500 hover:text-violet-700 transition-colors">
                ← Back to all products
            </a>
        </div>
    </div>

</main>

<!-- ── Footer ───────────────────────────────────────────────── -->
<footer class="bg-slate-900 text-slate-500 text-center py-6 mt-12 text-sm">
    <p>&copy; <?= date('Y') ?> <strong class="text-slate-300">TECHMOBILE</strong>. All prices in Indian Rupees (₹). Cash on Delivery only.</p>
</footer>

<script>
const maxQty = <?= $stock ?>;

function changeQty(delta) {
    const inp = document.getElementById('qty_input');
    let val = parseInt(inp.value, 10) || 1;
    val = Math.max(1, Math.min(maxQty, val + delta));
    inp.value = val;
}

function clampQty(el) {
    let val = parseInt(el.value, 10);
    if (isNaN(val) || val < 1) val = 1;
    if (val > maxQty) val = maxQty;
    el.value = val;
}
</script>
</body>
</html>