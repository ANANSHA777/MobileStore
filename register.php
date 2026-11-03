<?php
/**
 * register.php — Customer Registration (Compact Vibrant Light Mode)
 */
session_start();
require_once 'db.php';

if (!empty($_SESSION['user'])) { header('Location: user_dashboard.php'); exit; }

$errors = [];
$f = ['name' => '', 'email' => '', 'phone' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $f['name']  = trim($_POST['name']  ?? '');
    $f['email'] = trim($_POST['email'] ?? '');
    $f['phone'] = trim($_POST['phone'] ?? '');
    $pass  = $_POST['password']         ?? '';
    $pass2 = $_POST['confirm_password'] ?? '';

    if ($f['name'] === '')                                     $errors[] = 'Full name is required.';
    if (!filter_var($f['email'], FILTER_VALIDATE_EMAIL))       $errors[] = 'Enter a valid email address.';
    if (!preg_match('/^[\d\s\+\-\(\)]{7,20}$/', $f['phone'])) $errors[] = 'Enter a valid phone number.';
    if (strlen($pass) < 6)                                     $errors[] = 'Password must be at least 6 characters.';
    if ($pass !== $pass2)                                      $errors[] = 'Passwords do not match.';

    if (empty($errors)) {
        $chk = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $chk->execute([$f['email']]);
        if ($chk->fetch()) $errors[] = 'An account with this email already exists.';
    }

    if (empty($errors)) {
        $pdo->prepare('INSERT INTO users (name, email, password, role, phone, created_at) VALUES (?,?,?,?,?,NOW())')
            ->execute([$f['name'], $f['email'], password_hash($pass, PASSWORD_BCRYPT), 'customer', $f['phone']]);
        header('Location: login.php?registered=1');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account — TECHMOBILE</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="style.css">
</head>
<body class="bg-slate-50 min-h-screen">

<!-- ── Navbar ───────────────────────────────────────────────── -->
<header class="glass-nav sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 h-16 flex items-center justify-between gap-4">
        <a href="index.php" class="flex items-center gap-2 no-underline shrink-0">
            <img src="uploads/logo.png" alt="TECHMOBILE" class="h-12 w-auto object-contain">
            <span class="font-extrabold text-lg tracking-tight text-slate-900">TECH<span class="text-violet-600">MOBILE</span></span>
        </a>
        <div class="flex items-center gap-2">
            <a href="index.php"  class="text-sm font-semibold text-slate-600 hover:text-violet-700 px-3 py-1.5 rounded-lg hover:bg-violet-50 transition-colors">Home</a>
            <a href="login.php"  class="text-sm font-semibold text-slate-700 border border-slate-200 px-4 py-1.5 rounded-lg hover:bg-slate-100 transition-colors">Sign In</a>
        </div>
    </div>
</header>

<!-- ── Registration Content ──────────────────────────────────── -->
<div class="flex items-center justify-center px-4 py-6 relative overflow-hidden min-h-[calc(100vh-64px)]">

    <!-- ambient orbs -->
    <div class="absolute -top-16 -right-16 w-72 h-72 bg-violet-400/10 rounded-full blur-3xl pointer-events-none" aria-hidden="true"></div>
    <div class="absolute -bottom-20 -left-20 w-64 h-64 bg-indigo-400/10 rounded-full blur-3xl pointer-events-none" aria-hidden="true"></div>

    <div class="w-full max-w-md my-6 animate-fade-up relative z-10">
        <div class="bg-white border border-slate-200/80 rounded-2xl shadow-xl shadow-violet-500/8 p-6">

            <!-- Header -->
            <div class="flex items-center gap-3 mb-5">
                <div class="w-10 h-10 bg-violet-100 text-violet-600 rounded-xl flex items-center justify-center shrink-0 p-2">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0zM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632z"/>
                    </svg>
                </div>
                <div>
                    <h1 class="text-lg font-extrabold text-slate-900 leading-tight">Create your account</h1>
                    <p class="text-slate-500 text-xs mt-0.5">Join thousands of happy customers across India</p>
                </div>
            </div>

            <!-- Error alert -->
            <?php if (!empty($errors)): ?>
                <div class="bg-rose-50 border border-rose-200 text-rose-700 text-xs font-medium px-3.5 py-2.5 rounded-xl mb-4">
                    <p class="font-bold mb-1">⚠ Please fix the following:</p>
                    <ul class="list-disc list-inside space-y-0.5">
                        <?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" action="register.php" novalidate class="space-y-3.5">

                <!-- Full Name -->
                <div>
                    <label for="reg_name" class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1">Full Name</label>
                    <input id="reg_name" type="text" name="name" class="field" style="padding:0.55rem 0.875rem"
                           placeholder="Jane Smith"
                           value="<?= htmlspecialchars($f['name']) ?>"
                           required autocomplete="name">
                </div>

                <!-- Email -->
                <div>
                    <label for="reg_email" class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1">Email Address</label>
                    <input id="reg_email" type="email" name="email" class="field" style="padding:0.55rem 0.875rem"
                           placeholder="jane@example.com"
                           value="<?= htmlspecialchars($f['email']) ?>"
                           required autocomplete="email">
                </div>

                <!-- Phone -->
                <div>
                    <label for="reg_phone" class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1">Phone Number</label>
                    <input id="reg_phone" type="tel" name="phone" class="field" style="padding:0.55rem 0.875rem"
                           placeholder="+91 98765 43210"
                           value="<?= htmlspecialchars($f['phone']) ?>"
                           required autocomplete="tel">
                </div>

                <!-- Password -->
                <div>
                    <label for="reg_pass" class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1">Password</label>
                    <div class="pw-wrap">
                        <input id="reg_pass" type="password" name="password" class="field" style="padding:0.55rem 0.875rem"
                               placeholder="Minimum 6 characters"
                               required autocomplete="new-password"
                               oninput="checkMatch()">
                        <button type="button" class="pw-toggle" onclick="togglePw('reg_pass','reg_pw_icon')" aria-label="Toggle password visibility">
                            <svg id="reg_pw_icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Confirm Password -->
                <div>
                    <label for="reg_pass2" class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1">Confirm Password</label>
                    <div class="pw-wrap">
                        <input id="reg_pass2" type="password" name="confirm_password" class="field" style="padding:0.55rem 0.875rem"
                               placeholder="Repeat your password"
                               required autocomplete="new-password"
                               oninput="checkMatch()">
                        <button type="button" class="pw-toggle" onclick="togglePw('reg_pass2','reg_pw2_icon')" aria-label="Toggle confirm password visibility">
                            <svg id="reg_pw2_icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"/>
                            </svg>
                        </button>
                    </div>
                    <p id="match_msg" class="text-xs mt-1 hidden"></p>
                </div>

                <button type="submit"
                    class="w-full bg-violet-600 hover:bg-violet-700 active:bg-violet-800 text-white font-bold py-2.5 rounded-xl transition-all duration-200 text-sm shadow-sm shadow-violet-500/25 hover:shadow-md hover:shadow-violet-500/30 active:scale-[0.98] mt-1">
                    Create Account →
                </button>
            </form>

            <p class="text-center text-sm text-slate-500 mt-4">
                Already have an account?
                <a href="login.php" class="font-bold text-violet-600 hover:underline hover:text-violet-700">Sign in</a>
            </p>
        </div>
    </div>
</div>

<footer class="bg-slate-900 text-slate-500 text-center py-4 text-xs">
    <p>&copy; <?= date('Y') ?> <strong class="text-slate-300">TECHMOBILE</strong>. India's #1 Smartphone Store.</p>
</footer>

<script>
const EYE_OPEN  = '<path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"/>';
const EYE_SLASH = '<path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88"/>';

function togglePw(inputId, iconId) {
    const inp = document.getElementById(inputId);
    const ico = document.getElementById(iconId);
    const nowText = inp.type === 'password';
    inp.type = nowText ? 'text' : 'password';
    ico.innerHTML = nowText ? EYE_SLASH : EYE_OPEN;
}

function checkMatch() {
    const p1  = document.getElementById('reg_pass').value;
    const p2  = document.getElementById('reg_pass2').value;
    const msg = document.getElementById('match_msg');
    if (p2.length === 0) { msg.classList.add('hidden'); return; }
    msg.classList.remove('hidden');
    if (p1 === p2) {
        msg.textContent = '✓ Passwords match';
        msg.className = 'text-xs mt-1 text-emerald-600 font-semibold';
    } else {
        msg.textContent = '✕ Passwords do not match';
        msg.className = 'text-xs mt-1 text-rose-600 font-semibold';
    }
}
</script>
</body>
</html>
