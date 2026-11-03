<?php
/**
 * login.php — Authentication Portal (Vibrant Light Mode 2026)
 *
 * Auth flow:
 *  1. Fetch user row by email via PDO prepared statement.
 *  2. Verify submitted password against stored BCRYPT hash.
 *  3. Write $_SESSION['user'] with id, name, email, role, phone.
 *  4. Regenerate session ID to prevent fixation.
 *  5. Redirect purely on $user['role'] from the DB — no client-side gating.
 */
session_start();
require_once 'db.php';

// ── Already logged in — send to correct dashboard ─────────────
if (!empty($_SESSION['user'])) {
    header('Location: ' . ($_SESSION['user']['role'] === 'admin' ? 'admin_dashboard.php' : 'user_dashboard.php'));
    exit;
}

$errors     = [];
$email      = '';
$registered = isset($_GET['registered']);

// ── Handle POST ───────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']    ?? '');
    $pass  = trim($_POST['password'] ?? '');

    if ($email === '' || $pass === '') {
        $errors[] = 'Email address and password are required.';
    } else {
        try {
            // 1. Fetch user by email
            $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // 2. Verify BCRYPT password hash
            if ($user && password_verify($pass, $user['password'])) {

                // 3. Set session payload
                $_SESSION['user'] = [
                    'id'    => $user['id'],
                    'name'  => $user['name'],
                    'email' => $user['email'],
                    'role'  => $user['role'],
                    'phone' => $user['phone'] ?? '',
                ];

                // 4. Regenerate session ID (prevent session fixation)
                session_regenerate_id(true);

                // 5. Role-based redirect — determined entirely by DB users.role column
                if ($user['role'] === 'admin') {
                    header('Location: admin_dashboard.php');
                } else {
                    header('Location: user_dashboard.php');
                }
                exit;

            } else {
                $errors[] = 'Invalid email or password. Please try again.';
            }
        } catch (PDOException $e) {
            $errors[] = 'A database error occurred. Please try again later.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In — TECHMOBILE</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', system-ui, sans-serif; -webkit-font-smoothing: antialiased; }
        .field {
            width: 100%;
            background-color: #f8fafc;
            border: 1.5px solid #e2e8f0;
            color: #0f172a;
            border-radius: 0.75rem;
            padding: 0.625rem 0.875rem;
            font-size: 0.875rem;
            font-family: inherit;
            outline: none;
            transition: border-color 0.18s, box-shadow 0.18s, background 0.18s;
            line-height: 1.5;
        }
        .field:focus {
            background-color: #ffffff;
            border-color: #7c3aed;
            box-shadow: 0 0 0 3.5px rgba(124, 58, 237, 0.12);
        }
        .field::placeholder { color: #94a3b8; }
        .pw-wrap { position: relative; }
        .pw-wrap .field { padding-right: 2.75rem; }
        .pw-toggle {
            position: absolute;
            right: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            cursor: pointer;
            padding: 0.2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            border: none;
            background: none;
            transition: color 0.15s;
        }
        .pw-toggle:hover { color: #7c3aed; }
        .pw-toggle svg { width: 1.125rem; height: 1.125rem; }
    </style>
</head>
<body class="bg-slate-50 min-h-screen flex flex-col justify-between text-slate-900">

<!-- ── Navbar ───────────────────────────────────────────────── -->
<header class="bg-white/85 backdrop-blur-md border-b border-slate-200/80 sticky top-0 z-50 shadow-sm shadow-black/[0.03]">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 h-16 flex items-center justify-between gap-4">
        <a href="index.php" class="flex items-center gap-2 no-underline shrink-0">
            <img src="uploads/logo.png" alt="TECHMOBILE" class="h-12 w-auto object-contain">
            <span class="font-extrabold text-lg tracking-tight text-slate-900">TECH<span class="text-violet-600">MOBILE</span></span>
        </a>
        <div class="flex items-center gap-2">
            <a href="index.php"    class="text-sm font-semibold text-slate-600 hover:text-violet-700 px-3 py-1.5 rounded-lg hover:bg-violet-50 transition-colors">Home</a>
            <a href="register.php" class="text-sm font-semibold text-white bg-violet-600 hover:bg-violet-700 px-4 py-1.5 rounded-lg transition-colors shadow-sm shadow-violet-500/25">Register</a>
        </div>
    </div>
</header>

<!-- ── Auth Content ──────────────────────────────────────────── -->
<div class="flex-1 flex items-center justify-center px-4 py-10 relative overflow-hidden">

    <!-- Ambient orb blobs -->
    <div class="absolute -top-28 -left-28 w-96 h-96 bg-violet-400/10 rounded-full blur-3xl pointer-events-none" aria-hidden="true"></div>
    <div class="absolute -bottom-24 -right-24 w-80 h-80 bg-indigo-400/10 rounded-full blur-3xl pointer-events-none" aria-hidden="true"></div>

    <div class="w-full max-w-md relative z-10">

        <!-- Card -->
        <div class="bg-white border border-slate-200/80 rounded-3xl shadow-xl shadow-violet-500/8 p-6 sm:p-8">

            <!-- Brand Icon & Header -->
            <div class="text-center mb-6">
                <img src="uploads/logo.png" alt="TECHMOBILE" class="h-12 w-auto object-contain inline-flex items-center justify-center bg-violet-50 border border-violet-100 rounded-2xl mb-3 text-3xl">
                <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Welcome back</h1>
                <p class="text-slate-500 text-sm mt-1">Sign in to your TECHMOBILE account</p>
            </div>

            <!-- Role info strip (informational only — no gating) -->
            <div class="flex items-center gap-2.5 bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 mb-6">
                <span class="text-base">🔐</span>
                <p class="text-xs font-medium text-slate-500 leading-snug">
                    Both customers and admins sign in here.<br>
                </p>
            </div>

            <!-- Registration success banner -->
            <?php if ($registered): ?>
                <div class="flex items-center gap-2 bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm font-semibold px-4 py-3 rounded-xl mb-5">
                    ✅ Account created — please sign in below.
                </div>
            <?php endif; ?>

            <!-- Error alert -->
            <?php if (!empty($errors)): ?>
                <div class="bg-rose-50 border border-rose-200 text-rose-700 text-sm font-medium px-4 py-3 rounded-xl mb-5 flex items-start gap-2">
                    <span class="mt-0.5 shrink-0">⚠</span>
                    <div>
                        <?php foreach ($errors as $e): ?>
                            <p><?= htmlspecialchars($e) ?></p>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Login Form -->
            <form method="POST" action="login.php" novalidate class="space-y-4">

                <div>
                    <label for="login_email" class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1.5">
                        Email Address
                    </label>
                    <input id="login_email" type="email" name="email" class="field"
                           placeholder="you@example.com"
                           value="<?= htmlspecialchars($email) ?>"
                           required autocomplete="email">
                </div>

                <div>
                    <label for="login_pass" class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1.5">
                        Password
                    </label>
                    <div class="pw-wrap">
                        <input id="login_pass" type="password" name="password" class="field"
                               placeholder="Your password"
                               required autocomplete="current-password">
                        <button type="button" class="pw-toggle"
                                onclick="togglePw('login_pass','login_pw_icon')"
                                aria-label="Toggle password visibility">
                            <svg id="login_pw_icon" xmlns="http://www.w3.org/2000/svg" fill="none"
                                 viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <button type="submit"
                    class="w-full bg-violet-600 hover:bg-violet-700 active:bg-violet-800 text-white font-bold py-3 rounded-xl transition-all duration-200 text-sm shadow-sm shadow-violet-500/25 hover:shadow-md hover:shadow-violet-500/30 active:scale-[0.98] mt-2">
                    Sign In →
                </button>
            </form>

            <p class="text-center text-sm text-slate-500 mt-6">
                Don't have an account?
                <a href="register.php" class="font-bold text-violet-600 hover:underline hover:text-violet-700">Create New Account</a>
            </p>
        </div><!-- /card -->
    </div>
</div>

<!-- ── Footer ───────────────────────────────────────────────── -->
<footer class="bg-slate-900 text-slate-500 text-center py-4 text-xs">
    <p>&copy; <?= date('Y') ?> <strong class="text-slate-300">TECHMOBILE</strong>. India's #1 Smartphone Store.</p>
</footer>

<script>
/* ── Password visibility toggle ──────────────────────────────── */
function togglePw(inputId, iconId) {
    const inp  = document.getElementById(inputId);
    const icon = document.getElementById(iconId);
    const show = inp.type === 'password';
    inp.type   = show ? 'text' : 'password';
    icon.innerHTML = show
        ? '<path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88"/>'
        : '<path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"/>';
}
</script>
</body>
</html>
