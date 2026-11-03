<?php
/**
 * admin_orders.php — Order Management (Vibrant Light Mode 2026)
 */
session_start();
require_once 'db.php';
requireAdmin();

$admin            = $_SESSION['user'];
$allowed_statuses = ['Pending', 'Processing', 'Shipped', 'Delivered', 'Cancelled'];

/* ── Update Status ───────────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $oid    = (int)$_POST['order_id'];
    $status = $_POST['status'] ?? '';
    if ($oid > 0 && in_array($status, $allowed_statuses, true)) {
        $pdo->prepare('UPDATE orders SET status = ? WHERE id = ?')->execute([$status, $oid]);
    }
    header('Location: admin_orders.php?updated=1'); exit;
}

/* ── Filter & Fetch ──────────────────────────────────────────── */
$filter = trim($_GET['status'] ?? '');
$sql    = 'SELECT o.*, u.name AS uname FROM orders o LEFT JOIN users u ON u.id = o.user_id';
$params = [];
if ($filter && in_array($filter, $allowed_statuses, true)) {
    $sql .= ' WHERE o.status = ?';
    $params[] = $filter;
}
$sql .= ' ORDER BY o.created_at DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();

/* ── Per-status counts for filter chips ──────────────────────── */
$status_counts = ['__all__' => 0];
foreach ($allowed_statuses as $s) $status_counts[$s] = 0;
$cnt_rows = $pdo->query('SELECT status, COUNT(*) as cnt FROM orders GROUP BY status')->fetchAll();
foreach ($cnt_rows as $r) {
    $status_counts[$r['status']] = (int)$r['cnt'];
    $status_counts['__all__'] += (int)$r['cnt'];
}

/* ── Fetch order_items for displayed orders ───────────────────── */
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Orders — TECHMOBILE</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="style.css">
</head>
<body class="bg-slate-50 min-h-screen">

<header class="glass-nav sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 h-16 flex items-center justify-between gap-4">
        <a href="admin_dashboard.php" class="flex items-center gap-2 no-underline shrink-0">
            <img src="uploads/logo.png" alt="TECHMOBILE" class="h-12 w-auto object-contain">
            <span class="font-extrabold text-lg tracking-tight text-slate-900">TECH<span class="text-violet-600">MOBILE</span></span>
            <span class="text-xs font-bold bg-violet-100 text-violet-700 px-2 py-0.5 rounded-full ml-1">Admin</span>
        </a>
        <div class="flex items-center gap-2">
            <span class="hidden sm:flex items-center gap-1.5 text-sm text-slate-600">🛡 <strong class="text-slate-800"><?= htmlspecialchars($admin['name']) ?></strong></span>
            <a href="logout.php" class="text-xs font-semibold text-slate-500 border border-slate-200 px-3 py-1.5 rounded-lg hover:bg-slate-100 transition-colors">Logout</a>
        </div>
    </div>
</header>

<div class="bg-gradient-to-r from-violet-950 to-indigo-900 px-4 sm:px-6 py-2">
    <div class="max-w-7xl mx-auto flex items-center gap-1 flex-wrap">
        <a href="admin_dashboard.php" class="admin-link text-xs font-semibold px-3 py-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-white/10 transition-colors">📊 Dashboard</a>
        <a href="admin_products.php"  class="admin-link text-xs font-semibold px-3 py-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-white/10 transition-colors">📦 Products</a>
        <a href="admin_orders.php"    class="admin-link active text-xs font-bold px-3 py-1.5 rounded-lg text-violet-200">🛒 Orders</a>
        <a href="index.php"           class="ml-auto text-xs font-semibold px-3 py-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-white/10 transition-colors">← Storefront</a>
    </div>
</div>

<main class="max-w-7xl mx-auto px-4 sm:px-6 py-8">

    <div class="flex items-center justify-between flex-wrap gap-4 mb-6">
        <h1 class="text-2xl font-extrabold text-slate-900">🛒 Orders</h1>

        <!-- Status Filter Chips -->
        <div class="flex gap-1.5 flex-wrap">
            <a href="admin_orders.php"
               class="px-3 py-1.5 rounded-full text-xs font-semibold border transition-colors <?= $filter === '' ? 'chip-active' : 'bg-white text-slate-600 border-slate-200 hover:border-violet-400 hover:text-violet-600' ?>">
                All <span class="ml-0.5 opacity-70">(<?= $status_counts['__all__'] ?>)</span>
            </a>
            <?php foreach ($allowed_statuses as $s): ?>
                <a href="admin_orders.php?status=<?= urlencode($s) ?>"
                   class="px-3 py-1.5 rounded-full text-xs font-semibold border transition-colors <?= $filter === $s ? 'chip-active' : 'bg-white text-slate-600 border-slate-200 hover:border-violet-400 hover:text-violet-600' ?>">
                    <?= $s ?> <span class="ml-0.5 opacity-60">(<?= $status_counts[$s] ?? 0 ?>)</span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <?php if (isset($_GET['updated'])): ?>
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm font-medium px-4 py-3 rounded-xl mb-5">✅ Order status updated successfully.</div>
    <?php endif; ?>

    <?php if (empty($orders)): ?>
        <div class="bg-white border border-slate-200 rounded-2xl text-center py-16 px-6 shadow-sm">
            <div class="text-5xl mb-4">📭</div>
            <h2 class="text-lg font-extrabold text-slate-900 mb-2">No Orders Found</h2>
            <p class="text-slate-500"><?= $filter ? "No orders with status \"" . htmlspecialchars($filter) . "\"." : 'No orders have been placed yet.' ?></p>
        </div>
    <?php else: ?>
        <div class="tbl-wrap bg-white shadow-sm">
            <table class="w-full">
                <thead>
                    <tr class="bg-gradient-to-r from-violet-950 to-indigo-900">
                        <th class="text-left text-xs font-bold text-violet-200 uppercase tracking-wider px-5 py-3.5">Order Code</th>
                        <th class="text-left text-xs font-bold text-violet-200 uppercase tracking-wider px-5 py-3.5 hidden sm:table-cell">Customer</th>
                        <th class="text-left text-xs font-bold text-violet-200 uppercase tracking-wider px-5 py-3.5 hidden md:table-cell">Phone</th>
                        <th class="text-left text-xs font-bold text-violet-200 uppercase tracking-wider px-5 py-3.5">Total</th>
                        <th class="text-left text-xs font-bold text-violet-200 uppercase tracking-wider px-5 py-3.5 hidden lg:table-cell">Date</th>
                        <th class="text-left text-xs font-bold text-violet-200 uppercase tracking-wider px-5 py-3.5">Status</th>
                        <th class="text-left text-xs font-bold text-violet-200 uppercase tracking-wider px-5 py-3.5">Update</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($orders as $o):
                        $items     = $order_items_map[$o['id']] ?? [];
                        $detail_id = 'adm-order-' . $o['id'];
                    ?>
                        <!-- Main Row -->
                        <tr class="hover:bg-violet-50/30 transition-colors cursor-pointer"
                            onclick="toggleAdminOrder('<?= $detail_id ?>', this)">
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-2">
                                    <span class="adm-chev text-slate-400 text-xs">▶</span>
                                    <span class="font-mono font-bold text-slate-900 text-sm"><?= htmlspecialchars($o['order_code']) ?></span>
                                </div>
                            </td>
                            <td class="px-5 py-4 hidden sm:table-cell">
                                <p class="font-semibold text-slate-900 text-sm"><?= htmlspecialchars($o['customer_name']) ?></p>
                                <p class="text-xs text-slate-400"><?= htmlspecialchars($o['uname'] ?? '—') ?></p>
                            </td>
                            <td class="px-5 py-4 text-sm text-slate-600 hidden md:table-cell font-medium"><?= htmlspecialchars($o['phone']) ?></td>
                            <td class="px-5 py-4 font-extrabold text-emerald-600 text-sm"><?= formatINR((float)$o['total_amount']) ?></td>
                            <td class="px-5 py-4 text-sm text-slate-500 hidden lg:table-cell">
                                <span class="font-medium"><?= date('d M Y', strtotime($o['created_at'])) ?></span><br>
                                <span class="text-xs"><?= date('h:i A', strtotime($o['created_at'])) ?></span>
                            </td>
                            <td class="px-5 py-4" onclick="event.stopPropagation()">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold <?= statusClass($o['status']) ?>">
                                    <?= htmlspecialchars($o['status']) ?>
                                </span>
                            </td>
                            <td class="px-5 py-4" onclick="event.stopPropagation()">
                                <div class="flex flex-col gap-2">
                                    <form method="POST" action="admin_orders.php" class="flex items-center gap-2">
                                        <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                                        <select name="status"
                                            class="text-xs font-semibold px-2 py-1.5 rounded-lg border border-slate-200 bg-slate-50 text-slate-700 focus:outline-none focus:border-violet-400 focus:ring-2 focus:ring-violet-400/15 cursor-pointer">
                                            <?php foreach ($allowed_statuses as $s): ?>
                                                <option value="<?= $s ?>" <?= $o['status'] === $s ? 'selected' : '' ?>><?= $s ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button type="submit" name="update_status"
                                            class="text-xs font-bold bg-violet-600 hover:bg-violet-700 text-white px-3 py-1.5 rounded-lg transition-colors shadow-sm shadow-violet-500/20">✓</button>
                                    </form>
                                    <a href="order_detail.php?id=<?= (int)$o['id'] ?>"
                                       class="text-xs font-bold text-violet-500 hover:text-violet-700 underline underline-offset-2 transition-colors">
                                        View Full Detail →
                                    </a>
                                </div>
                            </td>
                        </tr>

                        <!-- Expandable Detail Row -->
                        <tr id="<?= $detail_id ?>" class="hidden bg-slate-50/80">
                            <td colspan="7" class="px-6 py-5">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                                    <div class="bg-white border border-slate-200 rounded-xl p-4">
                                        <p class="text-xs font-bold text-slate-400 uppercase tracking-wide mb-2">📦 Delivery Address</p>
                                        <p class="text-sm text-slate-700 leading-relaxed"><?= nl2br(htmlspecialchars($o['address'])) ?></p>
                                    </div>
                                    <div class="bg-white border border-slate-200 rounded-xl p-4">
                                        <p class="text-xs font-bold text-slate-400 uppercase tracking-wide mb-2">👤 Customer Info</p>
                                        <p class="text-sm font-semibold text-slate-900"><?= htmlspecialchars($o['customer_name']) ?></p>
                                        <p class="text-sm text-slate-600 mt-0.5">📞 <?= htmlspecialchars($o['phone']) ?></p>
                                        <p class="text-xs text-slate-400 mt-1">Account: <?= htmlspecialchars($o['uname'] ?? 'Guest') ?></p>
                                        <p class="text-xs text-slate-400">Placed: <?= date('d M Y, h:i A', strtotime($o['created_at'])) ?></p>
                                    </div>
                                </div>
                                <?php if (!empty($items)): ?>
                                    <p class="text-xs font-bold text-slate-500 uppercase tracking-wide mb-2">🛒 Order Items</p>
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
                                            <tbody class="divide-y divide-slate-100 bg-white">
                                                <?php foreach ($items as $it): ?>
                                                    <tr>
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
                                <?php else: ?>
                                    <p class="text-xs text-slate-400 italic">No item details recorded for this order.</p>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <p class="text-slate-400 text-xs mt-3 font-medium">
            Showing <?= count($orders) ?> order<?= count($orders) !== 1 ? 's' : '' ?><?= $filter ? ' with status <strong class="text-slate-600">' . htmlspecialchars($filter) . '</strong>' : '' ?>.
        </p>
    <?php endif; ?>
</main>

<footer class="bg-slate-900 text-slate-500 text-center py-4 text-xs mt-12">
    <p>&copy; <?= date('Y') ?> <strong class="text-slate-300">TECHMOBILE Admin</strong>.</p>
</footer>

<script>
function toggleAdminOrder(id, row) {
    const detail = document.getElementById(id);
    const chev   = row.querySelector('.adm-chev');
    const isOpen = !detail.classList.contains('hidden');
    detail.classList.toggle('hidden', isOpen);
    if (chev) {
        chev.textContent = isOpen ? '▶' : '▼';
        chev.style.color  = isOpen ? '' : '#7c3aed';
    }
}
</script>
</body>
</html>
