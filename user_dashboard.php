<?php
/**
 * user_dashboard.php — Customer Portal (Vibrant Light Mode 2026)
 */
session_start();
require_once 'db.php';
requireLogin();

$user = $_SESSION['user'];

$stmt = $pdo->prepare('SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC');
$stmt->execute([$user['id']]);
$orders = $stmt->fetchAll();

/* ── Fetch all order_items for this customer's orders in one query ── */
$order_items_map = [];
if (!empty($orders)) {
    $oids = array_column($orders, 'id');
    $ph   = implode(',', array_fill(0, count($oids), '?'));
    $si   = $pdo->prepare(
        "SELECT oi.*, p.name AS product_name, p.brand
         FROM order_items oi
         LEFT JOIN products p ON p.id = oi.product_id
         WHERE oi.order_id IN ($ph)"
    );
    $si->execute($oids);
    foreach ($si->fetchAll() as $row) {
        $order_items_map[$row['order_id']][] = $row;
    }
}

$total_spent = (float)array_sum(array_column($orders, 'total_amount'));
$cart_count  = count($_SESSION['cart'] ?? []);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Dashboard — TECHMOBILE</title>
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
        <nav class="hidden md:flex items-center gap-1">
            <a href="index.php"         class="text-sm font-medium text-slate-600 hover:text-slate-900 hover:bg-slate-100 px-3 py-1.5 rounded-lg transition-colors">Home</a>
            <a href="user_dashboard.php" class="text-sm font-semibold text-violet-700 bg-violet-50 px-3 py-1.5 rounded-lg">My Orders</a>
        </nav>
        <div class="flex items-center gap-2 shrink-0">
            <span class="hidden sm:flex items-center gap-1.5 text-sm text-slate-600">
                <span class="w-7 h-7 rounded-full bg-violet-100 flex items-center justify-center text-violet-700 font-bold text-xs shrink-0"><?= strtoupper(substr($user['name'], 0, 1)) ?></span>
                <span class="font-semibold text-slate-800 max-w-[100px] truncate"><?= htmlspecialchars($user['name']) ?></span>
            </span>
            <a href="logout.php" class="text-xs font-semibold text-slate-500 border border-slate-200 px-3 py-1.5 rounded-lg hover:bg-slate-100 transition-colors">Logout</a>
            <a href="cart.php" class="flex items-center gap-1.5 bg-violet-600 hover:bg-violet-700 text-white text-sm font-bold px-4 py-2 rounded-full transition-colors shadow-sm shadow-violet-500/20">
                🛒
                <span class="bg-white/20 text-white text-xs font-black rounded-full min-w-[20px] h-5 flex items-center justify-center px-1 leading-none"><?= $cart_count ?></span>
            </a>
        </div>
    </div>
</header>

<main class="max-w-7xl mx-auto px-4 sm:px-6 py-8">

    <!-- Profile Banner -->
    <div class="hero-gradient rounded-3xl p-6 sm:p-8 mb-8 text-white shadow-xl shadow-violet-500/20 flex flex-col sm:flex-row items-center sm:items-start gap-5">
        <div class="relative shrink-0">
            <div class="w-16 h-16 rounded-full bg-white/15 border-2 border-white/35 flex items-center justify-center text-2xl font-black text-white">
                <?= strtoupper(substr($user['name'], 0, 1)) ?>
            </div>
            <span class="absolute -bottom-1 -right-1 w-5 h-5 bg-emerald-400 border-2 border-white rounded-full"></span>
        </div>
        <div class="text-center sm:text-left flex-1 min-w-0">
            <h2 class="text-xl font-extrabold truncate"><?= htmlspecialchars($user['name']) ?></h2>
            <p class="text-violet-200 text-sm mt-0.5 truncate"><?= htmlspecialchars($user['email']) ?></p>
            <p class="text-violet-200 text-sm"><?= htmlspecialchars($user['phone']) ?></p>
        </div>
        <div class="flex gap-6 sm:ml-auto text-center shrink-0">
            <div>
                <p class="text-2xl font-extrabold"><?= count($orders) ?></p>
                <p class="text-xs text-violet-200 uppercase tracking-wide font-semibold mt-0.5">Orders</p>
            </div>
            <div>
                <p class="text-xl font-extrabold"><?= formatINR($total_spent) ?></p>
                <p class="text-xs text-violet-200 uppercase tracking-wide font-semibold mt-0.5">Spent</p>
            </div>
        </div>
        <a href="logout.php" class="sm:self-start bg-white/10 hover:bg-white/20 border border-white/20 text-white text-xs font-bold px-4 py-2 rounded-xl transition-colors">Logout</a>
    </div>

    <!-- Order History -->
    <div class="flex items-center justify-between mb-5">
        <h2 class="text-xl font-extrabold text-slate-900">📦 Order History</h2>
        <a href="index.php" class="text-sm text-violet-600 font-semibold hover:underline hover:text-violet-700">Shop More →</a>
    </div>

    <?php if (empty($orders)): ?>
        <div class="bg-white border border-slate-200 rounded-2xl text-center py-16 px-6 shadow-sm">
            <div class="text-5xl mb-4">📭</div>
            <h2 class="text-lg font-extrabold text-slate-900 mb-2">No Orders Yet</h2>
            <p class="text-slate-500 mb-5">Start shopping to see your order history here.</p>
            <a href="index.php" class="inline-flex items-center gap-2 bg-violet-600 hover:bg-violet-700 text-white font-bold px-5 py-2.5 rounded-xl transition-colors text-sm shadow-sm shadow-violet-500/20">Browse Products</a>
        </div>
    <?php else: ?>
        <div class="space-y-3">
        <?php foreach ($orders as $o):
            $items = $order_items_map[$o['id']] ?? [];
            $detail_id = 'order-' . $o['id'];
        ?>
            <!-- Order Card -->
            <div class="bg-white border border-slate-200/80 rounded-2xl shadow-sm overflow-hidden transition-card">

                <!-- Summary Row (clickable) -->
                <button type="button"
                    onclick="toggleOrder('<?= $detail_id ?>', this)"
                    class="w-full text-left px-5 py-4 flex flex-wrap sm:flex-nowrap items-center gap-3 hover:bg-violet-50/40 transition-colors group">

                    <!-- Chevron -->
                    <span class="chevron text-slate-400 group-hover:text-violet-500 transition-colors text-base shrink-0 w-5 text-center">▶</span>

                    <!-- Order Code -->
                    <span class="font-mono font-extrabold text-slate-900 text-sm min-w-[130px]"><?= htmlspecialchars($o['order_code']) ?></span>

                    <!-- Date -->
                    <span class="hidden sm:block text-xs text-slate-400 font-medium min-w-[90px]">
                        <?= date('d M Y', strtotime($o['created_at'])) ?>
                    </span>

                    <!-- Items count -->
                    <span class="text-xs font-semibold text-slate-500 bg-slate-100 px-2 py-0.5 rounded-full">
                        <?= count($items) ?> item<?= count($items) !== 1 ? 's' : '' ?>
                    </span>

                    <!-- Total -->
                    <span class="font-extrabold text-emerald-600 text-sm ml-auto shrink-0"><?= formatINR((float)$o['total_amount']) ?></span>

                    <!-- Status badge -->
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold shrink-0 <?= statusClass($o['status']) ?>">
                        <?= htmlspecialchars($o['status']) ?>
                    </span>

                    <!-- View detail link -->
                    <a href="order_detail.php?id=<?= (int)$o['id'] ?>"
                       onclick="event.stopPropagation()"
                       class="shrink-0 text-xs font-bold text-violet-500 hover:text-violet-700 underline underline-offset-2 transition-colors">
                        View →
                    </a>
                </button>

                <!-- Expandable Detail Panel -->
                <div id="<?= $detail_id ?>" class="hidden border-t border-slate-100">

                    <!-- Delivery Info -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 px-5 py-4 bg-slate-50/70">
                        <div>
                            <p class="text-xs font-bold text-slate-400 uppercase tracking-wide mb-1">📦 Delivery Address</p>
                            <p class="text-sm text-slate-700 leading-relaxed"><?= nl2br(htmlspecialchars($o['address'])) ?></p>
                        </div>
                        <div>
                            <p class="text-xs font-bold text-slate-400 uppercase tracking-wide mb-1">📞 Contact</p>
                            <p class="text-sm text-slate-700 font-medium"><?= htmlspecialchars($o['phone']) ?></p>
                            <p class="text-xs text-slate-400 mt-1">💵 <?= htmlspecialchars($o['payment_method']) ?></p>
                            <p class="text-xs text-slate-400"><?= date('d M Y, h:i A', strtotime($o['created_at'])) ?></p>
                        </div>
                    </div>

                    <!-- Items Table -->
                    <?php if (!empty($items)): ?>
                        <div class="px-5 pb-5">
                            <p class="text-xs font-bold text-slate-500 uppercase tracking-wide mb-2 mt-3">🛒 Items Ordered</p>
                            <div class="rounded-xl border border-slate-200 overflow-hidden">
                                <table class="w-full text-sm">
                                    <thead>
                                        <tr class="bg-violet-50 border-b border-violet-100">
                                            <th class="text-left text-xs font-bold text-violet-700 px-4 py-2.5">Product</th>
                                            <th class="text-center text-xs font-bold text-violet-700 px-4 py-2.5">Qty</th>
                                            <th class="text-right text-xs font-bold text-violet-700 px-4 py-2.5">Unit Price</th>
                                            <th class="text-right text-xs font-bold text-violet-700 px-4 py-2.5">Subtotal</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        <?php foreach ($items as $it): ?>
                                            <tr class="hover:bg-slate-50">
                                                <td class="px-4 py-2.5">
                                                    <p class="font-semibold text-slate-900"><?= htmlspecialchars($it['product_name'] ?? 'Product #'.$it['product_id']) ?></p>
                                                    <p class="text-xs text-slate-400"><?= htmlspecialchars($it['brand'] ?? '') ?></p>
                                                </td>
                                                <td class="px-4 py-2.5 text-center font-bold text-slate-700">×<?= (int)$it['quantity'] ?></td>
                                                <td class="px-4 py-2.5 text-right text-slate-600"><?= formatINR((float)$it['price']) ?></td>
                                                <td class="px-4 py-2.5 text-right font-extrabold text-emerald-600"><?= formatINR((float)$it['price'] * (int)$it['quantity']) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                    <tfoot>
                                        <tr class="bg-slate-50 border-t-2 border-violet-100">
                                            <td colspan="3" class="px-4 py-2.5 text-right text-xs font-extrabold text-slate-700 uppercase tracking-wide">Grand Total</td>
                                            <td class="px-4 py-2.5 text-right font-extrabold text-emerald-600"><?= formatINR((float)$o['total_amount']) ?></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    <?php else: ?>
                        <p class="px-5 pb-4 text-xs text-slate-400 italic">No item details available for this order.</p>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>

<footer class="bg-slate-900 text-slate-500 text-center py-4 text-xs mt-12">
    <p>&copy; <?= date('Y') ?> <strong class="text-slate-300">TECHMOBILE</strong>.</p>
</footer>

<script>
function toggleOrder(id, btn) {
    const panel  = document.getElementById(id);
    const chev   = btn.querySelector('.chevron');
    const isOpen = !panel.classList.contains('hidden');
    panel.classList.toggle('hidden', isOpen);
    chev.textContent = isOpen ? '▶' : '▼';
    chev.style.color  = isOpen ? '' : '#7c3aed';
}
</script>
</body>
</html>
