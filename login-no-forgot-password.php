<?php
require 'config/db.php';

session_start();
$db = getDB();

// Already logged in? Send straight to dashboard.
if (isset($_SESSION['user'])) {
    redirect_to_dashboard($_SESSION['user']['role']);
}

// ── Helpers ──────────────────────────────────────────────────

function h(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

function redirect_to_dashboard(string $role): void {
    $map = [
        'intern'      => 'intern.html',
        'coordinator' => 'coordinator.html',
        'admin'       => 'admin.php',
    ];
    header('Location: ' . ($map[$role] ?? 'login.php'));
    exit;
}

// ── Validation ────────────────────────────────────────────

function validate_input(array $post): array {
    $errors = [];

    if (empty(trim($post['email'] ?? ''))) {
        $errors[] = 'Email address is required.';
    } elseif (!filter_var($post['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    if (empty($post['password'] ?? '')) {
        $errors[] = 'Password is required.';
    } elseif (strlen($post['password']) < 6) {
        $errors[] = 'Password must be at least 6 characters.';
    }

    return $errors;
}

function attempt_login(string $email, string $password): ?array {
    $stmt = getDB()->prepare(
        'SELECT id, name, email, password, role, is_active
         FROM   users
         WHERE  email = :email
         LIMIT  1'
    );
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch();

    if (!$user)                                    return null;
    if (!password_verify($password, $user['password'])) return null;

    return $user;
}

function start_user_session(array $user): void {
    session_regenerate_id(true);
    $_SESSION['user'] = [
        'id'    => $user['id'],
        'name'  => $user['name'],
        'email' => $user['email'],
        'role'  => $user['role'],
    ];
}

// ── Handle Session Flash Alerts & POST ────────────────────────

$errors    = [];
$old_email = '';
$Alert     = '';

if (!empty($_SESSION['flash_success'])) {
    $Alert = $_SESSION['flash_success'];
    unset($_SESSION['flash_success']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email     = trim($_POST['email']    ?? '');
    $password  =      $_POST['password'] ?? '';
    $old_email = $email;

    $errors = validate_input($_POST);

    if (empty($errors)) {
        $user = attempt_login($email, $password);

        if ($user === null) {
            $errors[] = 'Incorrect email or password. Please try again.';
        } else if ($user['is_active'] === 0) {
            $Alert = 'Your account has not been approved yet.';
        } else {
            start_user_session($user);
            redirect_to_dashboard($user['role']);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>CITAS — Sign In</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Sora:wght@700;800&family=DM+Sans:wght@400;500&display=swap" rel="stylesheet">
  <style>
    :root {
      --o1: #FF6B00; --o2: #EA580C; --o3: #C2410C;
      --pale: #FFF7ED; --ring: rgba(234,88,12,.2);
    }
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    body {
      font-family: 'DM Sans', sans-serif; min-height: 100vh; background: var(--o3);
      display: flex; flex-direction: column; align-items: center; justify-content: center;
      padding: 1.5rem 1rem 2rem; overflow-x: hidden;
    }
    body::before, body::after { content: ''; position: fixed; border-radius: 50%; pointer-events: none; }
    body::before { width: 520px; height: 520px; top: -180px; right: -140px; background: radial-gradient(circle, rgba(255,140,0,.35) 0%, transparent 70%); }
    body::after { width: 400px; height: 400px; bottom: -130px; left: -100px; background: radial-gradient(circle, rgba(255,100,0,.2) 0%, transparent 70%); }

    .banner {
      width: 100%; max-width: 440px; background: rgba(255,255,255,.12); backdrop-filter: blur(8px);
      border: 1px solid rgba(255,255,255,.2); border-radius: 10px; padding: .6rem 1rem; margin-bottom: 1rem;
      display: flex; align-items: center; gap: .6rem; animation: slideDown .4s ease both;
    }
    .banner-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; background: #FCD34D; box-shadow: 0 0 6px #FCD34D; animation: blink 2s infinite; }
    @keyframes blink { 0%,100% { opacity:1; transform:scale(1); } 50% { opacity:.6; transform:scale(.85); } }
    .banner p { font-size: .78rem; color: rgba(255,255,255,.9); line-height: 1.4; }
    .banner strong { color: #FCD34D; }

    .card {
      width: 100%; max-width: 440px; background: #fff; border-radius: 20px; overflow: hidden;
      box-shadow: 0 24px 64px rgba(194,65,12,.18), 0 4px 16px rgba(0,0,0,.08); animation: slideUp .4s .1s ease both;
    }
    .card-head { background: linear-gradient(135deg, var(--o1) 0%, var(--o2) 60%, var(--o3) 100%); padding: 2rem 2rem 1.75rem; position: relative; overflow: hidden; }
    .card-head::before { content: ''; position: absolute; border-radius: 50%; width: 180px; height: 180px; top: -60px; right: -40px; background: rgba(255,255,255,.08); }
    .logo-row { display: flex; align-items: center; gap: .75rem; margin-bottom: 1rem; position: relative; z-index: 1; }
    .logo-icon { width: 46px; height: 46px; background: rgba(255,255,255,.2); border: 1.5px solid rgba(255,255,255,.35); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; }
    .logo-name { font-family: 'Sora',sans-serif; font-size: 1.15rem; font-weight: 800; color: #fff; }
    .logo-sub  { font-size: .72rem; opacity: .8; color: #fff; margin-top: .1rem; }
    .card-head h1 { font-family: 'Sora',sans-serif; font-size: 1.5rem; font-weight: 800; color: #fff; letter-spacing: -.4px; position: relative; z-index: 1; }
    .card-head p { color: rgba(255,255,255,.75); font-size: .85rem; margin-top: .3rem; position: relative; z-index: 1; }

    .card-body { padding: 1.75rem 2rem 2rem; }

    .errors { background: #FEF2F2; border: 1px solid #FECACA; border-radius: 10px; padding: .85rem 1rem; margin-bottom: 1.25rem; }
    .errors ul { list-style: none; display: flex; flex-direction: column; gap: .3rem; }
    .errors li { font-size: .83rem; font-weight: 500; color: #991B1B; display: flex; align-items: flex-start; gap: .4rem; }
    .errors li::before { content: '⚠'; flex-shrink: 0; }

    .alert { display:flex; align-items:flex-start; gap:.5rem; border-radius:10px; padding:.8rem 1rem; margin-bottom:1.1rem; font-size:.83rem; font-weight:500; }
    .alert-success { background:#F0FDF4; border:1px solid #BBF7D0; color:#166534; }

    .field { margin-bottom: 1.1rem; }
    label  { display: block; font-size: .8rem; font-weight: 600; color: #6B3A1F; margin-bottom: .4rem; }

    .inp-wrap { position: relative; }
    .inp-icon { position: absolute; left: .85rem; top: 50%; transform: translateY(-50%); font-size: 1rem; pointer-events: none; opacity: .4; z-index: 2; }
    
    .toggle-pass {
      position: absolute; right: .85rem; top: 50%; transform: translateY(-50%);
      background: none; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center;
      padding: 0; margin: 0; opacity: 0.4; transition: opacity 0.15s; z-index: 5;
    }
    .toggle-pass:hover { opacity: 0.8; }
    .toggle-pass svg { width: 20px; height: 20px; fill: #6B3A1F; }

    /* FIX: Targeted styling by tag and wrapper properties so structural layouts don't crash when types transform */
    .inp-wrap input {
      display: block; width: 100%; padding: .7rem 2.5rem .7rem 2.5rem;
      font-size: .9rem; font-family: 'DM Sans',sans-serif; color: #1A0A00; background: var(--pale);
      border: 1.5px solid #FED7AA; border-radius: 10px; outline: none; transition: border-color .15s, box-shadow .15s, background .15s;
    }
    .inp-wrap input::placeholder { color: #C4845A; opacity: .7; }
    .inp-wrap input:focus        { border-color: var(--o2); background: #fff; box-shadow: 0 0 0 3px var(--ring); }
    .inp-wrap input.err          { border-color: #EF4444; background: #FEF2F2; }
    .inp-wrap input.err:focus    { box-shadow: 0 0 0 3px rgba(239,68,68,.15); }

    .btn-submit {
      display: flex; align-items: center; justify-content: center; gap: .5rem; width: 100%; padding: .8rem; margin-top: 1.5rem;
      background: linear-gradient(135deg, var(--o1) 0%, var(--o2) 100%); color: #fff; font-family: 'Sora',sans-serif; font-size: .95rem; font-weight: 700;
      border: none; border-radius: 10px; cursor: pointer; box-shadow: 0 4px 14px rgba(234,88,12,.4); transition: filter .15s, transform .12s;
    }
    .btn-submit:hover  { filter: brightness(1.08); transform: translateY(-1px); }
    .btn-submit:active { transform: none; }

    .card-link { text-align: center; margin-top: 1.25rem; font-size: .83rem; color: #9A6647; }
    .card-link a { color: var(--o2); font-weight: 600; text-decoration: none; }
    .card-link a:hover { text-decoration: underline; }

    .page-foot { margin-top: 1.5rem; text-align: center; animation: fadeIn .6s .3s ease both; }
    .page-foot p { font-size: .73rem; color: rgba(255,255,255,.5); line-height: 1.9; }
    .page-foot strong { color: rgba(255,255,255,.75); }

    @keyframes slideUp   { from { opacity:0; transform:translateY(20px); } to { opacity:1; transform:none; } }
    @keyframes slideDown { from { opacity:0; transform:translateY(-12px);} to { opacity:1; transform:none; } }
    @keyframes fadeIn    { from { opacity:0; }                             to { opacity:1; } }

    @media (max-width:480px) {
      .card-head { padding: 1.5rem 1.5rem 1.35rem; }
      .card-body { padding: 1.4rem 1.5rem 1.5rem; }
    }
  </style>
</head>
<body>
<!--
<div class="banner">
  <div class="banner-dot"></div>
  <p>
    <strong>Academic Project — </strong>CITAS is a <strong>Capstone Project</strong> by Samar College BSIT students. For academic use only.
  </p>
</div>
  -->

<div class="card">

  <div class="card-head">
    <div class="logo-row">
      <div class="logo-icon">🎓</div>
      <div>
        <div class="logo-name">CITAS</div>
        <div class="logo-sub">Internship Monitoring System</div>
      </div>
    </div>
    <h1>Welcome back</h1>
    <p>Sign in to access your internship portal</p>
  </div>

  <div class="card-body">

    <?php if (!empty($errors)): ?>
      <div class="errors" role="alert">
        <ul>
          <?php foreach ($errors as $e): ?>
            <li><?= h($e) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>
    
    <?php if (!empty($Alert)): ?>
      <div class="alert alert-success">
        <span>✅</span>&nbsp;<?= h($Alert) ?>
      </div>
    <?php endif; ?>

    <form method="POST" action="" novalidate>

      <div class="field">
        <label for="email">School Email Address</label>
        <div class="inp-wrap">
          <span class="inp-icon">✉️</span>
          <input
            type="email" id="email" name="email"
            placeholder="you@samar.edu.ph"
            value="<?= h($old_email) ?>"
            class="<?= !empty($errors) ? 'err' : '' ?>"
            autocomplete="email"
            required autofocus>
        </div>
      </div>

      <div class="field">
        <label for="password">Password</label>
        <div class="inp-wrap">
          <span class="inp-icon">🔒</span>
          <input
            type="password" id="password" name="password"
            placeholder="Enter your password"
            class="<?= !empty($errors) ? 'err' : '' ?>"
            autocomplete="current-password"
            required>
          <button type="button" class="toggle-pass" data-target="password" aria-label="Toggle password visibility">
            <svg class="eye-open" viewBox="0 0 24 24"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg>
          </button>
        </div>
      </div>

      <button class="btn-submit" type="submit">Sign In &nbsp;→</button>

    </form>

    <div class="card-link">
      Don't have an account? <a href="register.php">Register here</a>
    </div>

  </div>
</div>

<div class="page-foot">
  <p>
    <strong>CITAS Internship Monitoring System</strong><br>
    Capstone Project 2025–2026 &nbsp;·&nbsp; Samar College BSIT Students<br>
    For academic and demonstration purposes only
  </p>
</div>

<script>
// Toggle Password Visibility Functional Implementation with SVG swapping
document.querySelectorAll('.toggle-pass').forEach(btn => {
  btn.addEventListener('click', function() {
    const targetId = this.getAttribute('data-target');
    const input = document.getElementById(targetId);
    if (input.type === 'password') {
      input.type = 'text';
      this.innerHTML = '<svg class="eye-closed" viewBox="0 0 24 24"><path d="M12 7c2.76 0 5 2.24 5 5 0 .65-.13 1.26-.36 1.82l2.92 2.92c1.51-1.26 2.7-2.89 3.44-4.74-1.73-4.39-6-7.5-11-7.5-1.4 0-2.74.25-3.98.7l2.16 2.16C10.74 7.13 11.35 7 12 7zM2 4.27l2.28 2.28.46.46C3.08 8.3 1.78 10.02 1 12c1.73 4.39 6 7.5 11 7.5 1.55 0 3.03-.3 4.38-.84l.42.42L19.73 22 21 20.73 3.27 3 2 4.27zM7.53 9.8l1.55 1.55c-.05.21-.08.43-.08.65 0 1.66 1.34 3 3 3 .22 0 .44-.03.65-.08l1.55 1.55c-.67.33-1.41.53-2.2.53-2.76 0-5-2.24-5-5 0-.79.2-1.53.53-2.2zm4.31-.78l3.15 3.15.02-.16c0-1.66-1.34-3-3-3l-.17.01z"/></svg>';
    } else {
      input.type = 'password';
      this.innerHTML = '<svg class="eye-open" viewBox="0 0 24 24"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg>';
    }
  });
});
</script>
</body>
</html>