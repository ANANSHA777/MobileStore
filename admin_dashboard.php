<?php
/**
 * admin_dashboard.php — Admin Overview Hub (Vibrant Light Mode 2026)
 */
session_start();
require_once 'db.php';
requireAdmin();

$admin        = $_SESSION['user'];
$revenue      = (float)$pdo->query('SELECT COALESCE(SUM(total_amount),0) FROM orders WHERE status != 'Cancelled'')->fetchColumn();
$orders_cnt   = (int)$pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn();
$products_cnt = (int)$pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();
$customers_cnt= (int)$pdo->query('SELECT COUNT(*) FROM users WHERE role = "customer"')->fetchColumn();
$recent       = $pdo->query('SELECT o.*, u.name AS uname FROM orders o LEFT JOIN users u ON u.id = o.user_id ORDER BY o.created_at DESC LIMIT 6')->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard — TECHMOBILE</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="style.css">
</head>
<body class="bg-slate-50 min-h-screen">

<!-- ── Main Navbar ───────────────────────────────────────────── -->
<header class="bg-white/80 backdrop-blur-md border-b border-slate-200/80 sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 h-16 flex items-center justify-between gap-4">
        <a href="admin_dashboard.php" class="flex items-center gap-2 no-underline shrink-0">
            <img src="uploads/logo.png" alt="TECHMOBILE" class="h-12 w-auto object-contain">
            <span class="font-extrabold text-lg tracking-tight text-slate-900">TECH<span class="text-violet-600">MOBILE</span></span>
            <span class="text-xs font-bold bg-violet-100 text-violet-700 px-2 py-0.5 rounded-full ml-1">Admin</span>
        </a>
        <div class="flex items-center gap-2 shrink-0">
            <span class="hidden sm:flex items-center gap-1.5 text-sm text-slate-600">
                <span class="w-7 h-7 rounded-full bg-violet-100 flex items-center justify-center text-violet-700 font-bold text-xs">🛡</span>
                <span class="font-semibold text-slate-800 max-w-[100px] truncate"><?= htmlspecialchars($admin['name'] ?? 'Admin') ?></span>
            </span>
            <a href="logout.php" class="text-xs font-semibold text-slate-500 border border-slate-200 px-3 py-1.5 rounded-lg hover:bg-slate-100 transition-colors">Logout</a>
        </div>
    </div>
</header>

<!-- ── Admin Sub-Nav ─────────────────────────────────────────── -->
<div class="bg-gradient-to-r from-violet-950 to-indigo-900 px-4 sm:px-6 py-2">
    <div class="max-w-7xl mx-auto flex items-center gap-1 flex-wrap">
        <a href="admin_dashboard.php" class="text-xs font-bold px-3 py-1.5 rounded-lg bg-white/20 text-white">📊 Dashboard</a>
        <a href="admin_products.php"  class="text-xs font-semibold px-3 py-1.5 rounded-lg text-slate-300 hover:text-white hover:bg-white/10 transition-colors">📦 Products</a>
        <a href="admin_orders.php"    class="text-xs font-semibold px-3 py-1.5 rounded-lg text-slate-300 hover:text-white hover:bg-white/10 transition-colors">🛒 Orders</a>
        <a href="index.php"           class="ml-auto text-xs font-semibold px-3 py-1.5 rounded-lg text-slate-300 hover:text-white hover:bg-white/10 transition-colors">← Storefront</a>
    </div>
</div>

<main class="max-w-7xl mx-auto px-4 sm:px-6 py-8">
    <h1 class="text-2xl font-extrabold text-slate-900 mb-6">📊 Overview</h1>

    <!-- Metric Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-8">

        <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 bg-emerald-50 rounded-xl flex items-center justify-center text-2xl shrink-0">💰</div>
            <div class="min-w-0">
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wide mb-0.5">Total Revenue</p>
                <p class="text-xl font-extrabold text-slate-900 truncate"><?= formatINR($revenue) ?></p>
                <p class="text-xs text-emerald-600 font-semibold mt-0.5">Excl. Cancelled</p>
            </div>
        </div>

        <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 bg-violet-50 rounded-xl flex items-center justify-center text-2xl shrink-0">🛒</div>
            <div>
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wide mb-0.5">Total Orders</p>
                <p class="text-xl font-extrabold text-slate-900"><?= number_format($orders_cnt) ?></p>
                <p class="text-xs text-violet-600 font-semibold mt-0.5">All time</p>
            </div>
        </div>

        <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 bg-amber-50 rounded-xl flex items-center justify-center text-2xl shrink-0">📦</div>
            <div>
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wide mb-0.5">Products</p>
                <p class="text-xl font-extrabold text-slate-900"><?= number_format($products_cnt) ?></p>
                <p class="text-xs text-amber-600 font-semibold mt-0.5">In catalogue</p>
            </div>
        </div>

        <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 bg-slate-100 rounded-xl flex items-center justify-center text-2xl shrink-0">👥</div>
            <div>
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wide mb-0.5">Customers</p>
                <p class="text-xl font-extrabold text-slate-900"><?= number_format($customers_cnt) ?></p>
                <p class="text-xs text-slate-500 font-semibold mt-0.5">Registered</p>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="flex flex-wrap gap-3 mb-8">
        <a href="admin_products.php" class="inline-flex items-center gap-2 bg-violet-600 hover:bg-violet-700 text-white font-bold px-5 py-2.5 rounded-xl transition-colors text-sm shadow-sm shadow-violet-500/20">📦 Manage Products</a>
        <a href="admin_orders.php"   class="inline-flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-5 py-2.5 rounded-xl transition-colors text-sm shadow-sm">🛒 Manage Orders</a>
    </div>

    <!-- Recent Orders -->
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-extrabold text-slate-900">🕐 Recent Orders</h2>
        <a href="admin_orders.php" class="text-sm text-violet-600 font-semibold hover:underline hover:text-violet-700">View All →</a>
    </div>

    <?php if (empty($recent)): ?>
        <div class="bg-white border border-slate-200 rounded-2xl text-center py-10 px-6 shadow-sm">
            <p class="text-slate-500 text-sm">No orders have been placed yet.</p>
        </div>
    <?php else: ?>
        <div class="tbl-wrap bg-white shadow-sm">
            <table class="w-full">
                <thead>
                    <tr class="bg-gradient-to-r from-violet-950 to-indigo-900">
                        <th class="text-left text-xs font-bold text-violet-200 uppercase tracking-wider px-5 py-3.5">Order Code</th>
                        <th class="text-left text-xs font-bold text-violet-200 uppercase tracking-wider px-5 py-3.5 hidden sm:table-cell">Customer</th>
                        <th class="text-left text-xs font-bold text-violet-200 uppercase tracking-wider px-5 py-3.5">Total</th>
                        <th class="text-left text-xs font-bold text-violet-200 uppercase tracking-wider px-5 py-3.5 hidden md:table-cell">Date</th>
                        <th class="text-left text-xs font-bold text-violet-200 uppercase tracking-wider px-5 py-3.5">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($recent as $o): ?>
                        <tr class="hover:bg-violet-50/40 transition-colors">
                            <td class="px-5 py-4"><span class="font-mono font-bold text-slate-900 text-sm"><?= htmlspecialchars($o['order_code'] ?? ('#' . $o['id'])) ?></span></td>
                            <td class="px-5 py-4 hidden sm:table-cell">
                                <p class="font-semibold text-slate-900 text-sm"><?= htmlspecialchars($o['customer_name'] ?? $o['uname'] ?? 'Customer') ?></p>
                                <p class="text-xs text-slate-400"><?= htmlspecialchars($o['uname'] ?? '—') ?></p>
                            </td>
                            <td class="px-5 py-4 font-extrabold text-emerald-600 text-sm"><?= formatINR((float)$o['total_amount']) ?></td>
                            <td class="px-5 py-4 text-sm text-slate-500 hidden md:table-cell"><?= date('d M Y', strtotime($o['created_at'])) ?></td>
                            <td class="px-5 py-4">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold <?= statusClass($o['status']) ?>">
                                    <?= htmlspecialchars($o['status']) ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</main>

<footer class="bg-slate-900 text-slate-500 text-center py-4 text-xs mt-12">
    <p>&copy; <?= date('Y') ?> <strong class="text-slate-300">TECHMOBILE Admin</strong>.</p>
</footer>
</body>
</html>