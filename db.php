<?php
/**
 * db.php — PDO Connection + Shared Helpers
 * TECHMOBILE · mobile_shop_db
 */

define('DB_HOST',    'localhost');
define('DB_NAME',    'mobile_shop_db');
define('DB_USER',    'root');
define('DB_PASS',    '');
define('DB_CHARSET', 'utf8mb4');

try {
    $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', DB_HOST, DB_NAME, DB_CHARSET);
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (PDOException $e) {
    http_response_code(503);
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8">
    <title>DB Error</title>
    <script src="https://cdn.tailwindcss.com"></script></head>
    <body class="bg-slate-50 min-h-screen flex items-center justify-center p-4">
      <div class="bg-white border border-red-200 border-l-4 border-l-red-500 rounded-2xl p-8 max-w-lg w-full shadow-lg">
        <div class="flex items-center gap-3 mb-3">
          <span class="text-2xl">⚠️</span>
          <h2 class="text-lg font-bold text-red-700">Database Connection Failed</h2>
        </div>
        <p class="text-slate-500 text-sm mb-3">WAMP must be running and <strong>mobile_shop_db</strong> must exist.</p>
        <code class="block bg-red-50 text-red-800 text-xs p-3 rounded-xl font-mono break-all">'
        . htmlspecialchars($e->getMessage()) . '</code>
      </div></body></html>';
    exit;
}

/* ── Helpers ──────────────────────────────────────────────── */

/** Format amount as Indian Rupees — e.g. ₹72,999.00 */
if (!function_exists('formatINR')) {
    function formatINR(float $amount): string {
        return '₹' . number_format($amount, 2, '.', ',');
    }
}

/** Require logged-in user or redirect to login.php */
if (!function_exists('requireLogin')) {
    function requireLogin(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (empty($_SESSION['user'])) {
            header('Location: login.php');
            exit;
        }
    }
}

/** Require admin role or redirect to login.php */
if (!function_exists('requireAdmin')) {
    function requireAdmin(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (empty($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'admin') {
            header('Location: login.php');
            exit;
        }
    }
}

/** Return a CSS class string for a status badge with native Tailwind fallbacks */
if (!function_exists('statusClass')) {
    function statusClass(string $status): string {
        return match($status) {
            'Pending'    => 'bg-amber-50 text-amber-700 border border-amber-200 status-pending',
            'Processing' => 'bg-indigo-50 text-indigo-700 border border-indigo-200 status-processing',
            'Shipped'    => 'bg-blue-50 text-blue-700 border border-blue-200 status-shipped',
            'Delivered'  => 'bg-emerald-50 text-emerald-700 border border-emerald-200 status-delivered',
            'Cancelled'  => 'bg-rose-50 text-rose-700 border border-rose-200 status-cancelled',
            default      => 'bg-slate-100 text-slate-600 border border-slate-200',
        };
    }
}