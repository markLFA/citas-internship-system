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

// ── AJAX Handlers for Forgot Password ────────────────────────

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    // 1. Send Password Reset OTP
    if ($_POST['action'] === 'send_reset_otp') {
        $email = trim($_POST['email'] ?? '');

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'Please enter a valid email address.']);
            exit;
        }

        // Check if user exists
        $stmt = getDB()->prepare('SELECT id, name FROM users WHERE email = :email LIMIT 1');
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();

        if (!$user) {
            // For security, don't explicitly reveal if email exists or not, or show message:
            echo json_encode(['success' => false, 'message' => 'No account found with that email address.']);
            exit;
        }

        // Generate 6-digit OTP code valid for 15 minutes
        $otp = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        
        $_SESSION['reset_otp'] = $otp;
        $_SESSION['reset_email'] = $email;
        $_SESSION['reset_expires'] = time() + 900; // 15 mins

        // Send Email using requested sender
        $subject = 'CITAS Password Reset Code';
        $message = "Hello {$user['name']},\n\nSomeone requested a password reset for your CITAS account.\nYour One-Time Password (OTP) is: {$otp}\n\nThis code expires in 15 minutes. If you did not request this, please ignore this email.";
        $headers = "From: CITAS System <no-reply@citas.internship.com>";

        @mail($email,$subject, $message,$headers);

        echo json_encode(['success' => true, 'message' => 'Password reset OTP sent to your email!']);
        exit;
    }

    // 2. Verify OTP & Update Password
    if ($_POST['action'] === 'reset_password') {
        $email           = trim($_POST['email'] ?? '');
        $otp             = trim($_POST['otp'] ?? '');
        $new_password    =$_POST['new_password'] ?? '';
        $confirm_password =$_POST['confirm_password'] ?? '';

        if (empty($email) || empty($otp) || empty($new_password)) {
            echo json_encode(['success' => false, 'message' => 'All fields are required.']);
            exit;
        }

        if (strlen($new_password) < 6) {
            echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters.']);
            exit;
        }

        if ($new_password !==$confirm_password) {
            echo json_encode(['success' => false, 'message' => 'Passwords do not match.']);
            exit;
        }

        // Validate session state matches
        if (!isset($_SESSION['reset_otp'],$_SESSION['reset_email']) || $_SESSION['reset_email'] !==$email) {
            echo json_encode(['success' => false, 'message' => 'Invalid session or email mismatch. Please request a new OTP.']);
            exit;
        }

        if (time() > ($_SESSION['reset_expires'] ?? 0)) {
            echo json_encode(['success' => false, 'message' => 'The reset OTP code has expired. Please request a new one.']);
            exit;
        }

        if (!hash_equals((string)$_SESSION['reset_otp'],$otp)) {
            echo json_encode(['success' => false, 'message' => 'Invalid verification OTP code entered.']);
            exit;
        }

        // Hash and update database password
        $newHash = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = getDB()->prepare('UPDATE users SET password = :password WHERE email = :email');$stmt->execute([':password' => $newHash, ':email' =>$email]);

        // Clear reset session cache
        unset($_SESSION['reset_otp'], $_SESSION['reset_email'],$_SESSION['reset_expires']);

        echo json_encode(['success' => true, 'message' => 'Password successfully updated! You can now log in.']);
        exit;
    }
}

// ── Validation ────────────────────────────────────────────

function validate_input(array $post): array {$errors = [];

    if (empty(trim($post['email'] ?? ''))) {$errors[] = 'Email address is required.';
    } elseif (!filter_var($post['email'], FILTER_VALIDATE_EMAIL)) {$errors[] = 'Please enter a valid email address.';
    }

    if (empty($post['password'] ?? '')) {$errors[] = 'Password is required.';
    } elseif (strlen($post['password']) < 6) {$errors[] = 'Password must be at least 6 characters.';
    }

    return $errors;
}

function attempt_login(string $email, string $password): ?array {$stmt = getDB()->prepare(
        'SELECT id, name, email, password, role, is_active
         FROM   users
         WHERE  email = :email
         LIMIT  1'
    );
    $stmt->execute([':email' =>$email]);
    $user =$stmt->fetch();

    if (!$user)                                    return null;
    if (!password_verify($password,$user['password'])) return null;

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
$old_email = '';$Alert     = '';

if (!empty($_SESSION['flash_success'])) {
    $Alert =$_SESSION['flash_success'];
    unset($_SESSION['flash_success']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['action'])) {

    $email     = trim($_POST['email']    ?? '');
    $password  =$_POST['password'] ?? '';
    $old_email =$email;

    $errors = validate_input($_POST);

    if (empty($errors)) {$user = attempt_login($email,$password);

        if ($user === null) {$errors[] = 'Incorrect email or password. Please try again.';
        } else if ($user['is_active'] === 0) {$Alert = 'Your account has not been approved yet.';
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
    .alert-error   { background:#FEF2F2; border:1px solid #FECACA; color:#991B1B; }

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

    .inp-wrap input {
      display: block; width: 100%; padding: .7rem 2.5rem .7rem 2.5rem;
      font-size: .9rem; font-family: 'DM Sans',sans-serif; color: #1A0A00; background: var(--pale);
      border: 1.5px solid #FED7AA; border-radius: 10px; outline: none; transition: border-color .15s, box-shadow .15s, background .15s;
    }
    .inp-wrap input::placeholder { color: #C4845A; opacity: .7; }
    .inp-wrap input:focus        { border-color: var(--o2); background: #fff; box-shadow: 0 0 0 3px var(--ring); }
    .inp-wrap input.err          { border-color: #EF4444; background: #FEF2F2; }

    .forgot-link-row { display: flex; justify-content: flex-end; margin-top: -.5rem; margin-bottom: 1rem; }
    .forgot-link { font-size: .78rem; color: var(--o2); font-weight: 600; text-decoration: none; cursor: pointer; }
    .forgot-link:hover { text-decoration: underline; }

    .otp-row { display: grid; grid-template-columns: 1fr auto; gap: .5rem; }
    .btn-otp {
      padding: 0 1rem; background: var(--pale); border: 1.5px solid #FED7AA; color: var(--o2);
      font-family: 'Sora', sans-serif; font-size: .8rem; font-weight: 700; border-radius: 10px;
      cursor: pointer; transition: background .15s, border-color .15s; white-space: nowrap;
    }
    .btn-otp:hover { background: #FFEDD5; border-color: var(--o2); }

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
    @keyframes fadeIn    { from { opacity:0; }                             to { opacity:1; } }

    @media (max-width:480px) {
      .card-head { padding: 1.5rem 1.5rem 1.35rem; }
      .card-body { padding: 1.4rem 1.5rem 1.5rem; }
    }
  </style>
</head>
<body>

<div class="card">

  <div class="card-head">
    <div class="logo-row">
      <div class="logo-icon">🎓</div>
      <div>
        <div class="logo-name">CITAS</div>
        <div class="logo-sub">Internship Monitoring System</div>
      </div>
    </div>
    <h1 id="form-title">Welcome back</h1>
    <p id="form-subtitle">Sign in to access your internship portal</p>
  </div>

  <div class="card-body">

    <div id="js-alert" class="alert" style="display:none;"></div>

    <?php if (!empty($errors)): ?>
      <div class="errors" role="alert" id="php-errors">
        <ul>
          <?php foreach ($errors as$e): ?>
            <li><?= h($e) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>
    
    <?php if (!empty($Alert)): ?>
      <div class="alert alert-success" id="php-alert">
        <span>✅</span>&nbsp;<?= h($Alert) ?>
      </div>
    <?php endif; ?>

    <!-- ── SIGN IN FORM ──────────────────────────────────────── -->
    <form method="POST" action="" novalidate id="login-form">

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

      <div class="field" style="margin-bottom: .5rem;">
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

      <div class="forgot-link-row">
        <a href="#" id="show-forgot-btn" class="forgot-link">Forgot password?</a>
      </div>

      <button class="btn-submit" type="submit">Sign In &nbsp;→</button>

    </form>


    <!-- ── FORGOT PASSWORD FORM (Hidden by default) ──────────── -->
    <form id="forgot-form" style="display: none;" novalidate>

      <div class="field">
        <label for="forgot-email">Account Email Address</label>
        <div class="otp-row">
          <div class="inp-wrap" style="width:100%;">
            <span class="inp-icon">✉️</span>
            <input type="email" id="forgot-email" placeholder="you@samar.edu.ph" required style="padding-right:.85rem;">
          </div>
          <button type="button" id="send-reset-otp-btn" class="btn-otp">Get OTP</button>
        </div>
        <div style="font-size:.72rem;color:#9A6647;margin-top:.3rem;">We will email a 6-digit code to verify your request.</div>
      </div>

      <div class="field">
        <label for="forgot-otp">Verification Code (OTP)</label>
        <div class="inp-wrap">
          <span class="inp-icon">🔑</span>
          <input type="text" id="forgot-otp" placeholder="6-digit code" maxlength="6" required>
        </div>
      </div>

      <div class="field">
        <label for="new-password">New Password</label>
        <div class="inp-wrap">
          <span class="inp-icon">🔒</span>
          <input type="password" id="new-password" placeholder="At least 6 characters" required autocomplete="new-password">
        </div>
      </div>

      <div class="field">
        <label for="confirm-password">Confirm New Password</label>
        <div class="inp-wrap">
          <span class="inp-icon">🔑</span>
          <input type="password" id="confirm-password" placeholder="Repeat new password" required autocomplete="new-password">
        </div>
      </div>

      <button class="btn-submit" type="submit" id="reset-submit-btn">Update Password &nbsp;→</button>

      <div class="card-link" style="margin-top:1rem;">
        Remembered your password? <a href="#" id="back-to-login-btn">Sign in here</a>
      </div>

    </form>


    <div class="card-link" id="default-card-link">
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
// Toggle Password Visibility
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

// UI View Switchers
const loginForm = document.getElementById('login-form');
const forgotForm = document.getElementById('forgot-form');
const formTitle = document.getElementById('form-title');
const formSubtitle = document.getElementById('form-subtitle');
const defaultCardLink = document.getElementById('default-card-link');
const jsAlert = document.getElementById('js-alert');

function hideServerAlerts() {
  const phpErr = document.getElementById('php-errors');
  const phpAlert = document.getElementById('php-alert');
  if (phpErr) phpErr.style.display = 'none';
  if (phpAlert) phpAlert.style.display = 'none';
  jsAlert.style.display = 'none';
}

document.getElementById('show-forgot-btn').addEventListener('click', (e) => {
  e.preventDefault();
  hideServerAlerts();
  loginForm.style.display = 'none';
  forgotForm.style.display = 'block';
  defaultCardLink.style.display = 'none';
  formTitle.textContent = 'Reset Password';
  formSubtitle.textContent = 'Enter your email to receive a secure recovery code';
});

document.getElementById('back-to-login-btn').addEventListener('click', (e) => {
  e.preventDefault();
  hideServerAlerts();
  forgotForm.style.display = 'none';
  loginForm.style.display = 'block';
  defaultCardLink.style.display = 'block';
  formTitle.textContent = 'Welcome back';
  formSubtitle.textContent = 'Sign in to access your internship portal';
});

// AJAX: Request Reset OTP
document.getElementById('send-reset-otp-btn').addEventListener('click', function() {
  const email = document.getElementById('forgot-email').value.trim();
  hideServerAlerts();

  if (!email) {
    jsAlert.style.display = 'flex';
    jsAlert.className = 'alert alert-error';
    jsAlert.textContent = 'Please enter your email address first.';
    document.getElementById('forgot-email').focus();
    return;
  }

  this.disabled = true;
  this.textContent = 'Sending...';

  const formData = new FormData();
  formData.append('action', 'send_reset_otp');
  formData.append('email', email);

  fetch('', { method: 'POST', body: formData })
    .then(res => res.json())
    .then(data => {
      jsAlert.style.display = 'flex';
      if (data.success) {
        jsAlert.className = 'alert alert-success';
        jsAlert.textContent = data.message;
        
        let countdown = 60;
        const btn = this;
        const interval = setInterval(() => {
          countdown--;
          btn.textContent = `Resend (${countdown}s)`;
          if (countdown <= 0) {
            clearInterval(interval);
            btn.disabled = false;
            btn.textContent = 'Get OTP';
          }
        }, 1000);
      } else {
        jsAlert.className = 'alert alert-error';
        jsAlert.textContent = data.message;
        this.disabled = false;
        this.textContent = 'Get OTP';
      }
    })
    .catch(err => {
      console.error(err);
      this.disabled = false;
      this.textContent = 'Get OTP';
      jsAlert.style.display = 'flex';
      jsAlert.className = 'alert alert-error';
      jsAlert.textContent = 'Network error occurred.';
    });
});

// AJAX: Submit New Password
forgotForm.addEventListener('submit', function(e) {
  e.preventDefault();
  hideServerAlerts();

  const email = document.getElementById('forgot-email').value.trim();
  const otp = document.getElementById('forgot-otp').value.trim();
  const newPassword = document.getElementById('new-password').value;
  const confirmPassword = document.getElementById('confirm-password').value;

  if (!email || !otp || !newPassword || !confirmPassword) {
    jsAlert.style.display = 'flex';
    jsAlert.className = 'alert alert-error';
    jsAlert.textContent = 'Please fill in all recovery fields.';
    return;
  }

  const formData = new FormData();
  formData.append('action', 'reset_password');
  formData.append('email', email);
  formData.append('otp', otp);
  formData.append('new_password', newPassword);
  formData.append('confirm_password', confirmPassword);

  fetch('', { method: 'POST', body: formData })
    .then(res => res.json())
    .then(data => {
      jsAlert.style.display = 'flex';
      if (data.success) {
        jsAlert.className = 'alert alert-success';
        jsAlert.textContent = data.message;
        forgotForm.reset();
        setTimeout(() => {
          document.getElementById('back-to-login-btn').click();
        }, 2000);
      } else {
        jsAlert.className = 'alert alert-error';
        jsAlert.textContent = data.message;
      }
    })
    .catch(err => {
      console.error(err);
      jsAlert.style.display = 'flex';
      jsAlert.className = 'alert alert-error';
      jsAlert.textContent = 'An unexpected error occurred.';
    });
});
</script>
</body>
</html>