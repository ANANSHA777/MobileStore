<?php
/**
 * checkout.php — COD Checkout (Vibrant Light Mode 2026)
 */
session_start();
require_once 'db.php';
requireLogin();
if (empty($_SESSION['cart'])) { header('Location: index.php'); exit; }

$user = $_SESSION['user'];

/* ── Rebuild Cart ────────────────────────────────────────────── */
$cart_ids = $_SESSION['cart'];
$qty_map  = array_count_values($cart_ids);
$uids     = array_unique($cart_ids);
$ph       = implode(',', array_fill(0, count($uids), '?'));
$stmt     = $pdo->prepare("SELECT * FROM products WHERE id IN ($ph)");
$stmt->execute(array_values($uids));
$pmap = array_column($stmt->fetchAll(), null, 'id');

$cart_items  = [];
$grand_total = 0.0;
foreach ($qty_map as $pid => $qty) {
    if (!isset($pmap[$pid])) continue;
    $p   = $pmap[$pid];
    $sub = (float)$p['price'] * $qty;
    $grand_total += $sub;
    $cart_items[] = ['id' => $pid, 'name' => $p['name'], 'price' => (float)$p['price'], 'qty' => $qty, 'sub' => $sub];
}

/* ── Handle POST ─────────────────────────────────────────────── */
$order_placed = false;
$order_code   = '';
$errors       = [];
$f = ['name' => $user['name'], 'phone' => $user['phone'], 'address' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    $f['name']    = trim($_POST['customer_name'] ?? '');
    $f['phone']   = trim($_POST['phone']         ?? '');
    $f['address'] = trim($_POST['address']        ?? '');

    if ($f['name']    === '') $errors[] = 'Full name is required.';
    if (!preg_match('/^[\d\s\+\-\(\)]{7,20}$/', $f['phone'])) $errors[] = 'Enter a valid phone number.';
    if ($f['address'] === '') $errors[] = 'Delivery address is required.';

    if (empty($errors)) {
        do {
            $order_code = 'ORD-' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));
            $ck = $pdo->prepare('SELECT id FROM orders WHERE order_code = ? LIMIT 1');
            $ck->execute([$order_code]);
        } while ($ck->fetch());

        try {
            $pdo->beginTransaction();
            $pdo->prepare('INSERT INTO orders (order_code,user_id,customer_name,phone,address,total_amount,payment_method,status,created_at) VALUES (?,?,?,?,?,?,?,?,NOW())')
                ->execute([$order_code, $user['id'], $f['name'], $f['phone'], $f['address'], $grand_total, 'Cash on Delivery', 'Pending']);
            $oid = $pdo->lastInsertId();
            $ins = $pdo->prepare('INSERT INTO order_items (order_id,product_id,quantity,price) VALUES (?,?,?,?)');
            $dec = $pdo->prepare('UPDATE products SET stock = GREATEST(stock - ?, 0) WHERE id = ?');
            foreach ($cart_items as $ci) {
                $ins->execute([$oid, $ci['id'], $ci['qty'], $ci['price']]);
                $dec->execute([$ci['qty'], $ci['id']]);
            }
            $pdo->commit();
            unset($_SESSION['cart']);
            $order_placed = true;
        } catch (Throwable $e) {
            $pdo->rollBack();
            $errors[] = 'Could not place order. Please try again.';
        }
    }
}
$cart_count = $order_placed ? 0 : count($cart_ids);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $order_placed ? 'Order Confirmed' : 'Checkout' ?> — TECHMOBILE</title>
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
            <span class="hidden sm:flex items-center gap-1.5 text-sm text-slate-600">
                <span class="w-7 h-7 rounded-full bg-violet-100 flex items-center justify-center text-violet-700 font-bold text-xs"><?= strtoupper(substr($user['name'], 0, 1)) ?></span>
                <span class="font-semibold text-slate-800 max-w-[100px] truncate"><?= htmlspecialchars($user['name']) ?></span>
            </span>
            <a href="logout.php" class="text-xs font-semibold text-slate-500 border border-slate-200 px-3 py-1.5 rounded-lg hover:bg-slate-100 transition-colors">Logout</a>
            <a href="cart.php" class="flex items-center gap-1.5 bg-violet-600 text-white text-sm font-bold px-4 py-2 rounded-full shadow-sm shadow-violet-500/20">
                🛒
                <span class="bg-white/20 text-white text-xs font-black rounded-full min-w-[20px] h-5 flex items-center justify-center px-1 leading-none"><?= $cart_count ?></span>
            </a>
        </div>
    </div>
</header>

<main class="max-w-7xl mx-auto px-4 sm:px-6 py-8">

<?php if ($order_placed): ?>
<!-- ── Order Success ──────────────────────────────────────────── -->
<div class="max-w-lg mx-auto animate-fade-up">
    <div class="bg-white border border-slate-200/80 rounded-3xl shadow-xl shadow-violet-500/8 p-8 sm:p-10 text-center">

        <div class="w-20 h-20 bg-emerald-50 border-2 border-emerald-300 rounded-full flex items-center justify-center text-3xl mx-auto mb-5 pulse-emerald animate-scale-in">✅</div>

        <h2 class="text-2xl font-extrabold text-slate-900 mb-1">Order Confirmed!</h2>
        <p class="text-slate-500 text-sm mb-5">
            Thank you, <strong class="text-slate-800"><?= htmlspecialchars($f['name']) ?></strong>. Your order has been placed successfully.
        </p>

        <div class="inline-block bg-gradient-to-r from-violet-700 to-indigo-700 text-white font-extrabold text-lg px-8 py-3 rounded-xl tracking-widest mb-6 shadow-md shadow-violet-500/25">
            <?= htmlspecialchars($order_code) ?>
        </div>

        <div class="space-y-3 text-left mb-6">
            <div class="bg-slate-50 border border-slate-200 rounded-xl px-4 py-3">
                <p class="text-xs font-bold text-slate-500 uppercase tracking-wide mb-1">📦 Delivering to</p>
                <p class="text-sm text-slate-700 leading-relaxed"><?= nl2br(htmlspecialchars($f['address'])) ?></p>
            </div>
            <div class="bg-slate-50 border border-slate-200 rounded-xl px-4 py-3">
                <p class="text-xs font-bold text-slate-500 uppercase tracking-wide mb-1">📞 Contact</p>
                <p class="text-sm text-slate-700 font-medium"><?= htmlspecialchars($f['phone']) ?></p>
            </div>
            <div class="bg-violet-50 border border-violet-200 rounded-xl px-4 py-3 flex justify-between items-center">
                <p class="text-xs font-bold text-slate-500 uppercase tracking-wide">💰 Order Total</p>
                <p class="font-extrabold text-emerald-600 text-base"><?= formatINR($grand_total) ?> · COD</p>
            </div>
        </div>

        <div class="inline-flex items-center gap-2 bg-emerald-50 border border-emerald-200 text-emerald-700 text-xs font-bold px-4 py-2 rounded-full mb-7">
            🚚 Estimated Delivery: 3–5 Business Days
        </div>

        <div class="flex gap-3 flex-col sm:flex-row">
            <a href="index.php" class="flex-1 text-center bg-slate-900 hover:bg-slate-800 text-white font-bold py-3 rounded-xl transition-colors text-sm">← Continue Shopping</a>
            <a href="user_dashboard.php" class="flex-1 text-center bg-violet-600 hover:bg-violet-700 text-white font-bold py-3 rounded-xl transition-colors text-sm shadow-sm shadow-violet-500/20">My Orders</a>
        </div>
    </div>
</div>

<?php else: ?>
<!-- ── Checkout Form ──────────────────────────────────────────── -->
<h1 class="text-2xl font-extrabold text-slate-900 mb-6">Checkout</h1>

<?php if (!empty($errors)): ?>
    <div class="bg-rose-50 border border-rose-200 text-rose-700 text-sm font-medium px-5 py-4 rounded-xl mb-6 flex items-start gap-2">
        <span class="mt-0.5 shrink-0">⚠</span>
        <ul class="list-disc list-inside space-y-0.5"><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul>
    </div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">

    <!-- Shipping Form -->
    <div class="lg:col-span-2 bg-white border border-slate-200/80 rounded-2xl shadow-lg shadow-violet-500/5 p-6 sm:p-8">
        <h2 class="font-extrabold text-slate-900 text-base mb-5 pb-4 border-b border-slate-100 flex items-center gap-2">🏠 Delivery Information</h2>
        <form method="POST" action="checkout.php" novalidate class="space-y-5">

            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1.5">Full Name</label>
                <input type="text" name="customer_name" class="field"
                       placeholder="Jane Smith"
                       value="<?= htmlspecialchars($f['name']) ?>"
                       required autocomplete="name">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1.5">Phone Number</label>
                <input type="tel" name="phone" class="field"
                       placeholder="+91 98765 43210"
                       value="<?= htmlspecialchars($f['phone']) ?>"
                       required autocomplete="tel">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1.5">Full Delivery Address</label>
                <textarea name="address" class="field"
                          placeholder="Flat/House No., Street, City, State, PIN Code"
                          required><?= htmlspecialchars($f['address']) ?></textarea>
            </div>

            <div class="flex items-center gap-2 bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm font-semibold px-4 py-3 rounded-xl">
                💵 Payment Method: <strong class="ml-1">Cash on Delivery (COD)</strong>
            </div>

            <button type="submit" name="place_order"
                class="w-full bg-violet-600 hover:bg-violet-700 active:bg-violet-800 text-white font-bold py-3.5 rounded-xl transition-all duration-200 text-sm shadow-sm shadow-violet-500/20 hover:shadow-md hover:shadow-violet-500/30 active:scale-[0.98]">
                ✅ Place Order — <?= formatINR($grand_total) ?>
            </button>
        </form>
    </div>

    <!-- Order Summary Panel -->
    <aside class="bg-white border border-slate-200/80 rounded-2xl shadow-lg shadow-violet-500/5 p-6 sticky top-20">
        <h3 class="font-extrabold text-slate-900 text-base pb-4 border-b border-slate-100 mb-4">🧾 Order Summary</h3>

        <div class="space-y-2.5 mb-4">
            <?php foreach ($cart_items as $item): ?>
                <div class="flex justify-between text-sm gap-2">
                    <span class="text-slate-600 flex-1 leading-snug">
                        <?= htmlspecialchars($item['name']) ?>
                        <?php if ($item['qty'] > 1): ?><span class="text-slate-400 text-xs"> ×<?= $item['qty'] ?></span><?php endif; ?>
                    </span>
                    <span class="font-semibold text-slate-800 shrink-0"><?= formatINR($item['sub']) ?></span>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="flex justify-between items-center pt-4 border-t-2 border-violet-100 mb-4">
            <span class="font-extrabold text-slate-900">Total</span>
            <span class="text-xl font-extrabold text-emerald-600"><?= formatINR($grand_total) ?></span>
        </div>
        <div class="flex items-center gap-2 bg-emerald-50 border border-emerald-200 text-emerald-700 text-xs font-semibold px-3 py-2.5 rounded-xl mb-4">
            💵 Cash on Delivery
        </div>
        <a href="cart.php" class="block w-full text-center text-sm font-semibold text-slate-600 border border-slate-200 py-2.5 rounded-xl hover:bg-slate-100 transition-colors">← Edit Cart</a>
    </aside>
</div>
<?php endif; ?>

</main>

<footer class="bg-slate-900 text-slate-500 text-center py-4 text-xs mt-12">
    <p>&copy; <?= date('Y') ?> <strong class="text-slate-300">TECHMOBILE</strong>. Cash on Delivery only.</p>
</footer>
</body>
</html>
