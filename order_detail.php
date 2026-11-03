<?php
/**
 * order_detail.php — Full Order Detail (Customer + Admin View)
 * Database: mobile_shop_db
 */
session_start();
require_once 'db.php';

// Authentication guard
if (function_exists('requireLogin')) {
    requireLogin();
} else if (empty($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$viewer   = $_SESSION['user'];
$order_id = (int)($_GET['id'] ?? 0);
if ($order_id <= 0) { header('Location: index.php'); exit; }

/* ── Auto-Create order_reviews Table if Not Present ────────────── */
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS order_reviews (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT NOT NULL,
        user_id INT NOT NULL,
        rating INT NOT NULL,
        comment TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY user_order (order_id, user_id)
    )");
} catch (PDOException $e) {
    // Table exists or database permissions active
}

/* ── Fetch order — enforce ownership for customers ────────────── */
if (($viewer['role'] ?? '') === 'admin') {
    $stmt = $pdo->prepare(
        'SELECT o.*, u.name AS uname, u.email AS uemail
         FROM orders o LEFT JOIN users u ON u.id = o.user_id
         WHERE o.id = ? LIMIT 1'
    );
    $stmt->execute([$order_id]);
} else {
    $stmt = $pdo->prepare(
        'SELECT o.*, u.name AS uname, u.email AS uemail
         FROM orders o LEFT JOIN users u ON u.id = o.user_id
         WHERE o.id = ? AND o.user_id = ? LIMIT 1'
    );
    $stmt->execute([$order_id, $viewer['id']]);
}
$order = $stmt->fetch();
if (!$order) { 
    header('Location: ' . (($viewer['role'] ?? '') === 'admin' ? 'admin_orders.php' : 'user_dashboard.php')); 
    exit; 
}

/* ── Fetch order items with Product Lookup ─────────────────────── */
$si = $pdo->prepare('SELECT * FROM order_items WHERE order_id = ?');
$si->execute([$order_id]);
$raw_items = $si->fetchAll();

$items = [];
foreach ($raw_items as $it) {
    $prod_stmt = $pdo->prepare('SELECT * FROM products WHERE id = ? LIMIT 1');
    $prod_stmt->execute([$it['product_id']]);
    $prod = $prod_stmt->fetch();

    $it['display_name']  = !empty($prod['name']) ? $prod['name'] : (!empty($it['product_name']) ? $it['product_name'] : 'Samsung Galaxy S24 Ultra');
    $it['display_brand'] = !empty($prod['brand']) ? $prod['brand'] : 'SAMSUNG';
    
    if (!empty($prod['image_url'])) {
        $it['display_image'] = $prod['image_url'];
    } else if (!empty($it['image_url'])) {
        $it['display_image'] = $it['image_url'];
    } else {
        $it['display_image'] = 'https://images.unsplash.com/photo-1610945265064-0e34e5519bbf?w=300&auto=format&fit=crop&q=80';
    }
    
    $items[] = $it;
}

/* ── Fetch Submitted Feedback (For both Admin & Customer) ───────── */
$existing_review = null;
try {
    $rev_stmt = $pdo->prepare('SELECT * FROM order_reviews WHERE order_id = ? LIMIT 1');
    $rev_stmt->execute([$order_id]);
    $existing_review = $rev_stmt->fetch();
} catch (Exception $e) {
    $existing_review = null;
}

/* ── Handle Review Submission (Customer Only) ──────────────────── */
if (($viewer['role'] ?? '') !== 'admin' && strtolower($order['status']) === 'delivered' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    $rating  = max(1, min(5, (int)($_POST['rating'] ?? 5)));
    $comment = trim($_POST['comment'] ?? '');

    try {
        if ($existing_review) {
            $upd = $pdo->prepare('UPDATE order_reviews SET rating = ?, comment = ? WHERE order_id = ? AND user_id = ?');
            $upd->execute([$rating, $comment, $order_id, $viewer['id']]);
        } else {
            $ins = $pdo->prepare('INSERT INTO order_reviews (order_id, user_id, rating, comment) VALUES (?, ?, ?, ?)');
            $ins->execute([$order_id, $viewer['id'], $rating, $comment]);
        }
        header("Location: order_detail.php?id={$order_id}&review_saved=1");
        exit;
    } catch (PDOException $e) {
        $review_error = "Could not save review.";
    }
}

/* ── Admin: handle status update ─────────────────────────────── */
$allowed_statuses = ['Pending','Processing','Shipped','Delivered','Cancelled'];
$update_msg = '';
if (($viewer['role'] ?? '') === 'admin' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $new_status = $_POST['status'] ?? '';
    if (in_array($new_status, $allowed_statuses, true)) {
        $pdo->prepare('UPDATE orders SET status = ? WHERE id = ?')->execute([$new_status, $order_id]);
        $order['status'] = $new_status;
        $update_msg = 'Status updated to ' . htmlspecialchars($new_status) . '.';
    }
}

/* ── Helper Functions ────────────────────────────────────────── */
if (!function_exists('statusClass')) {
    function statusClass($status) {
        switch (strtolower($status)) {
            case 'delivered':
                return 'bg-emerald-50 text-emerald-700 border border-emerald-200';
            case 'shipped':
                return 'bg-blue-50 text-blue-700 border border-blue-200';
            case 'processing':
                return 'bg-violet-50 text-violet-700 border border-violet-200';
            case 'cancelled':
                return 'bg-rose-50 text-rose-700 border border-rose-200';
            default:
                return 'bg-amber-50 text-amber-700 border border-amber-200';
        }
    }
}

if (!function_exists('formatINR')) {
    function formatINR($amount) {
        return '₹' . number_format((float)$amount, 2);
    }
}

$cart_count = count($_SESSION['cart'] ?? []);
$back_url   = ($viewer['role'] ?? '') === 'admin' ? 'admin_orders.php' : 'user_dashboard.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order <?= htmlspecialchars($order['order_code']) ?> — TECHMOBILE</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>body { font-family: 'Plus Jakarta Sans', sans-serif; }</style>
</head>
<body class="bg-slate-50 min-h-screen flex flex-col justify-between text-slate-900">

<!-- ── Navbar ───────────────────────────────────────────────── -->
<header class="bg-white/80 backdrop-blur-md border-b border-slate-200/80 sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 h-16 flex items-center justify-between gap-4">
        
        <a href="index.php" class="flex items-center gap-2.5 shrink-0 no-underline">
            <img src="uploads/logo.png" alt="TECHMOBILE" class="h-10 sm:h-12 w-auto object-contain">
            <?php if (($viewer['role'] ?? '') === 'admin'): ?>
                <span class="text-xs font-bold bg-violet-100 text-violet-700 border border-violet-200 px-2.5 py-0.5 rounded-full">Admin</span>
            <?php endif; ?>
        </a>

        <div class="flex items-center gap-2 shrink-0">
            <span class="hidden sm:flex items-center gap-1.5 text-sm text-slate-600">
                <span class="w-7 h-7 rounded-full bg-violet-100 flex items-center justify-center text-violet-700 font-bold text-xs shrink-0">
                    <?= strtoupper(substr($viewer['name'] ?? 'U', 0, 1)) ?>
                </span>
                <span class="font-semibold text-slate-800 max-w-[100px] truncate"><?= htmlspecialchars($viewer['name'] ?? 'User') ?></span>
            </span>
            <a href="logout.php" class="text-xs font-semibold text-slate-500 border border-slate-200 px-3 py-1.5 rounded-lg hover:bg-slate-100 transition-colors">Logout</a>
            
            <?php if (($viewer['role'] ?? '') !== 'admin'): ?>
                <a href="cart.php" class="flex items-center gap-1.5 bg-violet-600 hover:bg-violet-700 text-white text-sm font-bold px-4 py-2 rounded-full transition-colors shadow-sm shadow-violet-500/20">
                    🛒
                    <span class="bg-white/20 text-white text-xs font-black rounded-full min-w-[20px] h-5 flex items-center justify-center px-1 leading-none"><?= $cart_count ?></span>
                </a>
            <?php endif; ?>
        </div>
    </div>
</header>

<?php if (($viewer['role'] ?? '') === 'admin'): ?>
<div class="bg-gradient-to-r from-violet-950 to-indigo-900 px-4 sm:px-6 py-2">
    <div class="max-w-7xl mx-auto flex items-center gap-1 flex-wrap">
        <a href="admin_dashboard.php" class="text-xs font-semibold px-3 py-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-white/10 transition-colors">📊 Dashboard</a>
        <a href="admin_products.php"  class="text-xs font-semibold px-3 py-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-white/10 transition-colors">📦 Products</a>
        <a href="admin_orders.php"    class="text-xs font-bold px-3 py-1.5 rounded-lg text-violet-200 bg-white/10">🛒 Orders</a>
        <a href="index.php"           class="ml-auto text-xs font-semibold px-3 py-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-white/10 transition-colors">← Storefront</a>
    </div>
</div>
<?php endif; ?>

<main class="max-w-4xl mx-auto px-4 sm:px-6 py-8 flex-1 w-full">

    <nav class="flex items-center gap-1.5 text-xs text-slate-500 mb-6" aria-label="Breadcrumb">
        <a href="<?= $back_url ?>" class="hover:text-violet-600 transition-colors font-medium">
            <?= ($viewer['role'] ?? '') === 'admin' ? 'All Orders' : 'My Orders' ?>
        </a>
        <span class="text-slate-300">/</span>
        <span class="text-slate-900 font-semibold font-mono"><?= htmlspecialchars($order['order_code']) ?></span>
    </nav>

    <?php if ($update_msg): ?>
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm font-semibold px-4 py-3 rounded-xl mb-5 flex items-center gap-2">
            ✅ <?= $update_msg ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['review_saved'])): ?>
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm font-semibold px-4 py-3 rounded-xl mb-5 flex items-center gap-2">
            ⭐ Thank you! Your feedback has been saved.
        </div>
    <?php endif; ?>

    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-extrabold text-slate-900 font-mono"><?= htmlspecialchars($order['order_code']) ?></h1>
            <p class="text-xs text-slate-500 mt-0.5">Placed on <?= date('d M Y \a\t h:i A', strtotime($order['created_at'])) ?></p>
        </div>
        <span class="inline-flex items-center px-3.5 py-1.5 rounded-full text-xs font-bold <?= statusClass($order['status']) ?>">
            <?= htmlspecialchars($order['status']) ?>
        </span>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Left Column: Items & Feedback -->
        <div class="lg:col-span-2 space-y-5">

            <!-- Items Ordered Table -->
            <div class="bg-white border border-slate-200/80 rounded-2xl shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-100 font-extrabold text-slate-900 text-sm flex items-center gap-2">
                    <span>🛒</span> Items Ordered
                </div>
                
                <?php if (empty($items)): ?>
                    <p class="px-5 py-6 text-sm text-slate-400 italic text-center">No item records found for this order.</p>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="bg-violet-600 text-white text-[11px] font-extrabold uppercase tracking-wider">
                                    <th class="px-5 py-3">Product</th>
                                    <th class="px-5 py-3 text-center">Qty</th>
                                    <th class="px-5 py-3 text-right hidden sm:table-cell">Unit</th>
                                    <th class="px-5 py-3 text-right">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-sm">
                                <?php foreach ($items as $it): ?>
                                    <tr class="hover:bg-slate-50/60 transition-colors">
                                        <td class="px-5 py-4 flex items-center gap-3">
                                            <div class="w-14 h-14 rounded-xl bg-violet-50/60 border border-violet-100 p-1 flex items-center justify-center shrink-0 overflow-hidden">
                                                <img src="<?= htmlspecialchars($it['display_image']) ?>"
                                                     alt="<?= htmlspecialchars($it['display_name']) ?>"
                                                     class="max-h-full max-w-full object-contain"
                                                     onerror="this.onerror=null; this.src='https://images.unsplash.com/photo-1610945265064-0e34e5519bbf?w=300&auto=format&fit=crop&q=80';">
                                            </div>
                                            <div>
                                                <p class="font-bold text-slate-900 text-sm leading-snug">
                                                    <?= htmlspecialchars($it['display_name']) ?>
                                                </p>
                                                <p class="text-xs font-bold text-slate-400 uppercase tracking-wide mt-0.5">
                                                    <?= htmlspecialchars($it['display_brand']) ?>
                                                </p>
                                            </div>
                                        </td>
                                        <td class="px-5 py-4 text-center font-extrabold text-slate-700">×<?= (int)$it['quantity'] ?></td>
                                        <td class="px-5 py-4 text-right text-slate-600 font-semibold hidden sm:table-cell"><?= formatINR((float)$it['price']) ?></td>
                                        <td class="px-5 py-4 text-right font-extrabold text-emerald-600"><?= formatINR((float)$it['price'] * (int)$it['quantity']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="p-5 bg-slate-50/80 border-t border-slate-100 flex justify-between items-center text-sm font-extrabold">
                        <span class="text-xs uppercase tracking-wide text-slate-500">Grand Total</span>
                        <span class="text-emerald-600 text-xl font-extrabold"><?= formatINR((float)$order['total_amount']) ?></span>
                    </div>
                <?php endif; ?>
            </div>

            <!-- ── REVIEWS & FEEDBACK CARD ─────────────────────────────────────── -->
            <?php if (strtolower($order['status']) === 'delivered'): ?>
                <div class="bg-white border border-slate-200/80 rounded-2xl shadow-sm p-5 space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                        <h2 class="font-extrabold text-slate-900 text-sm flex items-center gap-2">
                            <span>⭐</span> Customer Delivery Review
                        </h2>
                        <span class="text-xs font-bold text-emerald-700 bg-emerald-50 px-2.5 py-1 rounded-full border border-emerald-200">
                            Delivered
                        </span>
                    </div>

                    <?php if (($viewer['role'] ?? '') === 'admin'): ?>
                        <!-- ADMIN READ-ONLY VIEW -->
                        <?php if ($existing_review): ?>
                            <div class="space-y-2">
                                <div class="flex items-center gap-1 text-amber-400 text-xl">
                                    <?php for ($s = 1; $s <= 5; $s++): ?>
                                        <span><?= $s <= (int)$existing_review['rating'] ? '★' : '☆' ?></span>
                                    <?php endfor; ?>
                                    <span class="text-xs font-bold text-slate-600 ml-2">(<?= (int)$existing_review['rating'] ?> / 5 Stars)</span>
                                </div>
                                <p class="text-xs text-slate-700 bg-slate-50 p-3 rounded-xl border border-slate-100 leading-relaxed">
                                    "<?= htmlspecialchars($existing_review['comment']) ?>"
                                </p>
                            </div>
                        <?php else: ?>
                            <p class="text-xs text-slate-400 italic">No feedback submitted by customer yet.</p>
                        <?php endif; ?>

                    <?php else: ?>
                        <!-- CUSTOMER VIEW -->
                        <?php if ($existing_review): ?>
                            <!-- LOCKED AFTER SUBMISSION -->
                            <div class="bg-emerald-50/50 border border-emerald-200/80 rounded-xl p-4 space-y-2">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-bold text-emerald-800">✅ Feedback Submitted</span>
                                    <div class="flex items-center gap-1 text-amber-400 text-lg">
                                        <?php for ($s = 1; $s <= 5; $s++): ?>
                                            <span><?= $s <= (int)$existing_review['rating'] ? '★' : '☆' ?></span>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                                <p class="text-xs text-slate-700 bg-white p-3 rounded-lg border border-emerald-100 leading-relaxed">
                                    "<?= htmlspecialchars($existing_review['comment']) ?>"
                                </p>
                            </div>
                        <?php else: ?>
                            <!-- SUBMISSION FORM -->
                            <form method="POST" action="order_detail.php?id=<?= $order_id ?>" class="space-y-4">
                                <div>
                                    <label class="block text-xs font-extrabold uppercase text-slate-500 mb-2">Rating</label>
                                    <div class="flex items-center gap-2" id="star_container">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <button type="button" onclick="setRating(<?= $i ?>)" 
                                                    class="star-btn text-2xl transition-transform hover:scale-125 focus:outline-none text-amber-400"
                                                    data-index="<?= $i ?>">
                                                ★
                                            </button>
                                        <?php endfor; ?>
                                    </div>
                                    <input type="hidden" name="rating" id="rating_input" value="5">
                                </div>

                                <div>
                                    <label class="block text-xs font-extrabold uppercase text-slate-500 mb-1">Feedback Comments</label>
                                    <textarea name="comment" rows="3" required placeholder="How was the product quality and delivery experience?"
                                              class="w-full px-4 py-3 rounded-xl bg-slate-50 border border-slate-200 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:bg-white transition-all"></textarea>
                                </div>

                                <button type="submit" name="submit_review"
                                        class="w-full bg-violet-600 hover:bg-violet-700 text-white font-bold py-3 rounded-xl transition-all text-xs shadow-md shadow-violet-500/20">
                                    Submit Feedback
                                </button>
                            </form>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <a href="<?= $back_url ?>" class="inline-flex items-center gap-1.5 text-xs font-bold text-slate-500 hover:text-violet-700 transition-colors">
                ← Back to <?= ($viewer['role'] ?? '') === 'admin' ? 'All Orders' : 'My Orders' ?>
            </a>
        </div>

        <!-- Right Column: Sidebar Info -->
        <aside class="space-y-4">

            <div class="bg-white border border-slate-200/80 rounded-2xl shadow-sm p-5 space-y-3">
                <h3 class="font-extrabold text-slate-900 text-sm border-b border-slate-100 pb-2">💰 Payment Details</h3>
                <p class="text-xs font-semibold text-slate-700">💵 <?= htmlspecialchars($order['payment_method'] ?? 'Cash on Delivery') ?></p>
                <div class="bg-emerald-50 border border-emerald-200 p-2.5 rounded-xl text-xs font-bold text-emerald-800 flex items-center gap-2">
                    <span>✓</span> Cash collected on delivery
                </div>
            </div>

            <div class="bg-white border border-slate-200/80 rounded-2xl shadow-sm p-5 space-y-2">
                <h3 class="font-extrabold text-slate-900 text-sm border-b border-slate-100 pb-2">📦 Delivery Details</h3>
                <p class="text-sm font-bold text-slate-800"><?= htmlspecialchars($order['customer_name'] ?? 'N/A') ?></p>
                <p class="text-xs text-slate-500 font-medium">📞 <?= htmlspecialchars($order['phone'] ?? 'N/A') ?></p>
                <p class="text-xs text-slate-600 leading-relaxed mt-1 bg-slate-50 p-3 rounded-xl border border-slate-100">
                    <?= nl2br(htmlspecialchars($order['address'] ?? '')) ?>
                </p>
            </div>

            <?php if (($viewer['role'] ?? '') === 'admin'): ?>
                <div class="bg-white border border-slate-200/80 rounded-2xl shadow-sm p-5 space-y-1">
                    <h3 class="font-extrabold text-slate-900 text-sm border-b border-slate-100 pb-2 mb-2">👤 Customer Account</h3>
                    <p class="text-sm font-bold text-slate-800"><?= htmlspecialchars($order['uname'] ?? 'Guest User') ?></p>
                    <p class="text-xs text-slate-400 font-medium"><?= htmlspecialchars($order['uemail'] ?? 'No email linked') ?></p>
                </div>

                <div class="bg-white border border-slate-200/80 rounded-2xl shadow-sm p-5 space-y-3">
                    <h3 class="font-extrabold text-slate-900 text-sm border-b border-slate-100 pb-2">🔄 Update Order Status</h3>
                    <form method="POST" action="order_detail.php?id=<?= $order_id ?>" class="space-y-3">
                        <select name="status" class="w-full px-3 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-xs font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-violet-500">
                            <?php foreach ($allowed_statuses as $s): ?>
                                <option value="<?= $s ?>" <?= $order['status'] === $s ? 'selected' : '' ?>><?= $s ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" name="update_status"
                            class="w-full bg-violet-600 hover:bg-violet-700 text-white font-bold py-2.5 rounded-xl transition-all text-xs shadow-sm shadow-violet-500/20">
                            Save Status Change
                        </button>
                    </form>
                </div>
            <?php endif; ?>

        </aside>
    </div>
</main>

<footer class="bg-slate-900 text-slate-500 text-center py-6 text-xs mt-12">
    <p>&copy; <?= date('Y') ?> <strong class="text-slate-300">TECHMOBILE</strong>. All rights reserved.</p>
</footer>

<script>
function setRating(val) {
    document.getElementById('rating_input').value = val;
    document.querySelectorAll('.star-btn').forEach(btn => {
        const index = parseInt(btn.getAttribute('data-index'), 10);
        if (index <= val) {
            btn.classList.remove('text-slate-300');
            btn.classList.add('text-amber-400');
        } else {
            btn.classList.remove('text-amber-400');
            btn.classList.add('text-slate-300');
        }
    });
}
</script>
</body>
</html>