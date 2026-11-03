<?php
/**
 * cart.php — Shopping Cart (Vibrant Light Mode 2026)
 */
session_start();
require_once 'db.php';

/* ── Cart Actions ────────────────────────────────────────────── */
if (isset($_POST['clear_cart']))    { unset($_SESSION['cart']); header('Location: cart.php'); exit; }
if (isset($_GET['add_one']))        { $_SESSION['cart'][] = (int)$_GET['add_one']; header('Location: cart.php'); exit; }
if (isset($_GET['remove_one'])) {
    $cart = $_SESSION['cart'] ?? [];
    $pos  = array_search((int)$_GET['remove_one'], $cart);
    if ($pos !== false) array_splice($cart, $pos, 1);
    $_SESSION['cart'] = $cart;
    header('Location: cart.php'); exit;
}
if (isset($_GET['remove_all_of'])) {
    $pid = (int)$_GET['remove_all_of'];
    $_SESSION['cart'] = array_values(array_filter($_SESSION['cart'] ?? [], fn($id) => $id !== $pid));
    header('Location: cart.php'); exit;
}

/* ── Build Cart ──────────────────────────────────────────────── */
$cart_ids   = $_SESSION['cart'] ?? [];
$cart_items = [];
$grand_total = 0.0;

if (!empty($cart_ids)) {
    $qty_map = array_count_values($cart_ids);
    $uids    = array_unique($cart_ids);
    $ph      = implode(',', array_fill(0, count($uids), '?'));
    $stmt    = $pdo->prepare("SELECT * FROM products WHERE id IN ($ph)");
    $stmt->execute(array_values($uids));
    $map = array_column($stmt->fetchAll(), null, 'id');
    foreach ($qty_map as $pid => $qty) {
        if (!isset($map[$pid])) continue;
        $p   = $map[$pid];
        $sub = (float)$p['price'] * $qty;
        $grand_total += $sub;
        $cart_items[] = [
            'id'    => $pid,
            'name'  => $p['name'],
            'brand' => $p['brand'],
            'price' => (float)$p['price'],
            'qty'   => $qty,
            'sub'   => $sub,
        ];
    }
}

$item_count = count($cart_ids);
$user       = $_SESSION['user'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cart — TECHMOBILE</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="style.css">
</head>
<body class="bg-slate-50 min-h-screen">

<header class="glass-nav sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 h-16 flex items-center justify-between gap-4">
        <a href="index.php" class="flex items-center gap-2 no-underline shrink-0">
            <img src="uploads/logo.png" alt="TECHMOBILE" class="h-12 w-auto object-contain">
            <span class="font-extrabold text-lg tracking-tight text-slate-900">TECH<span class="text-violet-600">MOBILE</span></span>
        </a>
        <div class="flex items-center gap-2">
            <?php if ($user): ?>
                <a href="user_dashboard.php" class="hidden sm:inline text-sm font-medium text-slate-600 hover:text-slate-900">My Orders</a>
                <a href="logout.php" class="text-xs font-semibold text-slate-500 border border-slate-200 px-3 py-1.5 rounded-lg hover:bg-slate-100 transition-colors">Logout</a>
            <?php else: ?>
                <a href="login.php" class="text-sm font-semibold text-violet-600 border border-violet-200 px-3 py-1.5 rounded-lg hover:bg-violet-50 transition-colors">Sign In</a>
            <?php endif; ?>
            <a href="cart.php" class="flex items-center gap-1.5 bg-violet-600 text-white text-sm font-bold px-4 py-2 rounded-full shadow-sm shadow-violet-500/25">
                🛒
                <span class="bg-white/20 text-white text-xs font-black rounded-full min-w-[20px] h-5 flex items-center justify-center px-1 leading-none"><?= $item_count ?></span>
            </a>
        </div>
    </div>
</header>

<main class="max-w-7xl mx-auto px-4 sm:px-6 py-8">
    <h1 class="text-2xl font-extrabold text-slate-900 mb-6">🛒 Your Cart</h1>

    <?php if (empty($cart_items)): ?>
        <div class="bg-white border border-slate-200 rounded-2xl text-center py-20 px-6 shadow-sm">
            <div class="text-6xl mb-4">🛒</div>
            <h2 class="text-xl font-extrabold text-slate-900 mb-2">Your cart is empty</h2>
            <p class="text-slate-500 mb-6">Browse our collection and add smartphones to your cart.</p>
            <a href="index.php" class="inline-flex items-center gap-2 bg-violet-600 hover:bg-violet-700 text-white font-bold px-6 py-3 rounded-xl transition-colors shadow-sm shadow-violet-500/20">Browse Products</a>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">

            <!-- Cart Table -->
            <div class="lg:col-span-2">
                <div class="tbl-wrap bg-white shadow-sm">
                    <table class="w-full">
                        <thead>
                            <tr class="bg-gradient-to-r from-violet-900 to-violet-700">
                                <th class="text-left text-xs font-bold text-violet-200 uppercase tracking-wider px-5 py-3.5">#</th>
                                <th class="text-left text-xs font-bold text-violet-200 uppercase tracking-wider px-5 py-3.5">Product</th>
                                <th class="text-left text-xs font-bold text-violet-200 uppercase tracking-wider px-5 py-3.5 hidden sm:table-cell">Unit Price</th>
                                <th class="text-center text-xs font-bold text-violet-200 uppercase tracking-wider px-5 py-3.5">Qty</th>
                                <th class="text-right text-xs font-bold text-violet-200 uppercase tracking-wider px-5 py-3.5">Subtotal</th>
                                <th class="px-5 py-3.5"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php foreach ($cart_items as $i => $item): ?>
                                <tr class="hover:bg-violet-50/40 transition-colors">
                                    <td class="px-5 py-4 text-sm text-slate-400 font-medium"><?= $i + 1 ?></td>
                                    <td class="px-5 py-4">
                                        <p class="font-bold text-slate-900 text-sm"><?= htmlspecialchars($item['name']) ?></p>
                                        <p class="text-xs text-slate-500 font-medium mt-0.5"><?= htmlspecialchars($item['brand']) ?></p>
                                    </td>
                                    <td class="px-5 py-4 hidden sm:table-cell text-sm font-semibold text-slate-700"><?= formatINR($item['price']) ?></td>
                                    <td class="px-5 py-4 text-center">
                                        <div class="inline-flex items-center gap-1.5">
                                            <a href="cart.php?remove_one=<?= $item['id'] ?>" class="qty-btn">−</a>
                                            <span class="text-sm font-extrabold text-slate-800 min-w-[1.75rem] text-center"><?= $item['qty'] ?></span>
                                            <a href="cart.php?add_one=<?= $item['id'] ?>" class="qty-btn">+</a>
                                        </div>
                                    </td>
                                    <td class="px-5 py-4 text-right font-extrabold text-emerald-600 text-sm"><?= formatINR($item['sub']) ?></td>
                                    <td class="px-5 py-4 text-right">
                                        <a href="cart.php?remove_all_of=<?= $item['id'] ?>"
                                           class="text-xs text-rose-400 hover:text-rose-600 font-bold transition-colors"
                                           onclick="return confirm('Remove <?= htmlspecialchars(addslashes($item['name'])) ?> from cart?')">✕</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="flex flex-wrap gap-3 mt-4">
                    <a href="index.php" class="text-sm font-semibold text-slate-600 border border-slate-200 px-4 py-2 rounded-xl hover:bg-slate-100 transition-colors">← Continue Shopping</a>
                    <form method="POST" action="cart.php">
                        <button type="submit" name="clear_cart"
                                class="text-sm font-semibold text-rose-500 border border-rose-200 px-4 py-2 rounded-xl hover:bg-rose-50 transition-colors"
                                onclick="return confirm('Clear all items from cart?')">🗑 Clear Cart</button>
                    </form>
                </div>
            </div>

            <!-- Order Summary -->
            <aside class="bg-white border border-slate-200/80 rounded-2xl shadow-lg shadow-violet-500/5 p-6 sticky top-20">
                <h3 class="font-extrabold text-slate-900 text-base pb-4 border-b border-slate-100 mb-4">📋 Order Summary</h3>

                <div class="space-y-2.5 mb-4">
                    <?php foreach ($cart_items as $item): ?>
                        <div class="flex justify-between items-start text-sm gap-2">
                            <span class="text-slate-600 flex-1 leading-snug"><?= htmlspecialchars($item['name']) ?>
                                <?php if ($item['qty'] > 1): ?>
                                    <span class="text-slate-400 text-xs ml-1">×<?= $item['qty'] ?></span>
                                <?php endif; ?>
                            </span>
                            <span class="font-semibold text-slate-800 shrink-0"><?= formatINR($item['sub']) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="flex justify-between items-center pt-4 border-t-2 border-violet-100 mb-4">
                    <span class="font-extrabold text-slate-900">Grand Total</span>
                    <span class="text-xl font-extrabold text-emerald-600"><?= formatINR($grand_total) ?></span>
                </div>

                <div class="flex items-center gap-2 bg-emerald-50 border border-emerald-200 text-emerald-700 text-xs font-semibold px-3 py-2.5 rounded-xl mb-5">
                    💵 Cash on Delivery — No advance payment
                </div>

                <a href="<?= $user ? 'checkout.php' : 'login.php' ?>"
                   class="block w-full text-center bg-violet-600 hover:bg-violet-700 text-white font-bold py-3 rounded-xl transition-all duration-200 text-sm shadow-sm shadow-violet-500/20 hover:shadow-md hover:shadow-violet-500/30 active:scale-[0.98]">
                    <?= $user ? 'Proceed to Checkout →' : '🔐 Sign In to Checkout' ?>
                </a>
                <?php if (!$user): ?>
                    <p class="text-center text-xs text-slate-400 mt-3">
                        <a href="register.php" class="text-violet-500 font-semibold hover:underline">Create account</a> if you're new.
                    </p>
                <?php endif; ?>
            </aside>
        </div>
    <?php endif; ?>
</main>

<footer class="bg-slate-900 text-slate-500 text-center py-4 text-xs mt-12">
    <p>&copy; <?= date('Y') ?> <strong class="text-slate-300">TECHMOBILE</strong>. All prices in ₹ Indian Rupees.</p>
</footer>
</body>
</html>
