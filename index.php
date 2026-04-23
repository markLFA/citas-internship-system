<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>CITAS — Sign In</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;500;600;700;800&family=DM+Sans:wght@400;500&display=swap" rel="stylesheet">
  <style>
    /* ── Tokens ─────────────────────────────────────────── */
    :root {
      --orange-1: #FF6B00;
      --orange-2: #EA580C;
      --orange-3: #C2410C;
      --orange-pale: #FFF7ED;
      --orange-ring: rgba(234,88,12,.25);
      --text-dark: #1A0A00;
      --text-mid:  #6B3A1F;
      --text-muted:#9A6647;
      --white:     #FFFFFF;
      --card-shadow: 0 24px 64px rgba(194,65,12,.18), 0 4px 16px rgba(0,0,0,.08);
    }

    /* ── Reset ───────────────────────────────────────────── */
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    /* ── Page ────────────────────────────────────────────── */
    body {
      min-height: 100vh;
      font-family: 'DM Sans', sans-serif;
      background: var(--orange-3);
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 1.5rem 1rem 2rem;
      position: relative;
      overflow-x: hidden;
    }

    /* Decorative background circles */
    body::before, body::after {
      content: '';
      position: fixed;
      border-radius: 50%;
      pointer-events: none;
    }
    body::before {
      width: 520px; height: 520px;
      top: -180px; right: -140px;
      background: radial-gradient(circle, rgba(255,140,0,.35) 0%, transparent 70%);
    }
    body::after {
      width: 400px; height: 400px;
      bottom: -130px; left: -100px;
      background: radial-gradient(circle, rgba(255,100,0,.2) 0%, transparent 70%);
    }

    /* Subtle noise texture overlay */
    .bg-texture {
      position: fixed;
      inset: 0;
      pointer-events: none;
      background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noise'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noise)' opacity='0.04'/%3E%3C/svg%3E");
      opacity: .5;
    }

    /* ── Capstone banner ─────────────────────────────────── */
    .capstone-banner {
      width: 100%;
      max-width: 440px;
      background: rgba(255,255,255,.12);
      backdrop-filter: blur(8px);
      border: 1px solid rgba(255,255,255,.2);
      border-radius: 10px;
      padding: .6rem 1rem;
      margin-bottom: 1rem;
      display: flex;
      align-items: center;
      gap: .6rem;
      animation: slideDown .5s ease both;
    }
    .capstone-banner-dot {
      width: 8px; height: 8px;
      background: #FCD34D;
      border-radius: 50%;
      flex-shrink: 0;
      box-shadow: 0 0 6px #FCD34D;
      animation: pulse 2s infinite;
    }
    @keyframes pulse {
      0%, 100% { opacity: 1; transform: scale(1); }
      50%       { opacity: .6; transform: scale(.85); }
    }
    .capstone-banner p {
      font-size: .78rem;
      color: rgba(255,255,255,.9);
      font-weight: 500;
      line-height: 1.4;
    }
    .capstone-banner strong { color: #FCD34D; }

    /* ── Card ────────────────────────────────────────────── */
    .card {
      width: 100%;
      max-width: 440px;
      background: var(--white);
      border-radius: 20px;
      box-shadow: var(--card-shadow);
      overflow: hidden;
      animation: slideUp .5s .1s ease both;
      position: relative;
      z-index: 1;
    }

    /* Orange header stripe inside card */
    .card-header {
      background: linear-gradient(135deg, var(--orange-1) 0%, var(--orange-2) 60%, var(--orange-3) 100%);
      padding: 2rem 2rem 1.75rem;
      position: relative;
      overflow: hidden;
    }
    .card-header::before {
      content: '';
      position: absolute;
      width: 180px; height: 180px;
      border-radius: 50%;
      background: rgba(255,255,255,.08);
      top: -60px; right: -40px;
    }
    .card-header::after {
      content: '';
      position: absolute;
      width: 100px; height: 100px;
      border-radius: 50%;
      background: rgba(255,255,255,.06);
      bottom: -30px; left: 30px;
    }

    .logo-row {
      display: flex;
      align-items: center;
      gap: .75rem;
      margin-bottom: 1rem;
      position: relative;
      z-index: 1;
    }
    .logo-icon {
      width: 46px; height: 46px;
      background: rgba(255,255,255,.2);
      border: 1.5px solid rgba(255,255,255,.35);
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.5rem;
      backdrop-filter: blur(4px);
    }
    .logo-text { color: #fff; }
    .logo-text .system-name {
      font-family: 'Sora', sans-serif;
      font-size: 1.15rem;
      font-weight: 800;
      letter-spacing: -.3px;
      line-height: 1;
    }
    .logo-text .system-sub {
      font-size: .72rem;
      opacity: .8;
      margin-top: .2rem;
      font-weight: 400;
    }

    .card-header h1 {
      font-family: 'Sora', sans-serif;
      font-size: 1.5rem;
      font-weight: 800;
      color: #fff;
      line-height: 1.2;
      position: relative;
      z-index: 1;
      letter-spacing: -.4px;
    }
    .card-header p {
      color: rgba(255,255,255,.75);
      font-size: .85rem;
      margin-top: .3rem;
      position: relative;
      z-index: 1;
    }

    /* Card body */
    .card-body { padding: 1.75rem 2rem 2rem; }

    /* ── Form ────────────────────────────────────────────── */
    .field { margin-bottom: 1.1rem; }

    label {
      display: block;
      font-size: .8rem;
      font-weight: 600;
      color: var(--text-mid);
      margin-bottom: .4rem;
      letter-spacing: .01em;
    }

    .input-wrap { position: relative; }
    .input-icon {
      position: absolute;
      left: .85rem;
      top: 50%;
      transform: translateY(-50%);
      font-size: 1rem;
      pointer-events: none;
      opacity: .45;
    }

    input[type="email"],
    input[type="password"],
    input[type="text"],
    select {
      display: block;
      width: 100%;
      padding: .7rem .85rem .7rem 2.5rem;
      font-size: .9rem;
      font-family: 'DM Sans', sans-serif;
      color: var(--text-dark);
      background: var(--orange-pale);
      border: 1.5px solid #FED7AA;
      border-radius: 10px;
      outline: none;
      transition: border-color .15s, box-shadow .15s, background .15s;
      -webkit-appearance: none;
    }
    input::placeholder { color: #C4845A; opacity: .7; }
    input:focus, select:focus {
      border-color: var(--orange-2);
      background: #fff;
      box-shadow: 0 0 0 3px var(--orange-ring);
    }

    /* No icon version */
    .no-icon input,
    .no-icon select {
      padding-left: .85rem;
    }

    .submit-btn {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: .5rem;
      width: 100%;
      padding: .8rem;
      margin-top: 1.5rem;
      background: linear-gradient(135deg, var(--orange-1) 0%, var(--orange-2) 100%);
      color: #fff;
      font-family: 'Sora', sans-serif;
      font-size: .95rem;
      font-weight: 700;
      border: none;
      border-radius: 10px;
      cursor: pointer;
      letter-spacing: .01em;
      transition: filter .15s, transform .12s, box-shadow .15s;
      box-shadow: 0 4px 14px rgba(234,88,12,.4);
    }
    .submit-btn:hover  { filter: brightness(1.08); transform: translateY(-1px); box-shadow: 0 6px 18px rgba(234,88,12,.45); }
    .submit-btn:active { transform: translateY(0); filter: brightness(.97); }

    /* ── Error / alert ───────────────────────────────────── */
    .alert-error {
      display: flex;
      align-items: center;
      gap: .5rem;
      background: #FEF2F2;
      border: 1px solid #FECACA;
      color: #991B1B;
      border-radius: 8px;
      padding: .65rem .9rem;
      font-size: .82rem;
      font-weight: 500;
      margin-bottom: 1.1rem;
    }

    /* ── Footer link ─────────────────────────────────────── */
    .card-footer-link {
      text-align: center;
      margin-top: 1.25rem;
      font-size: .83rem;
      color: var(--text-muted);
    }
    .card-footer-link a {
      color: var(--orange-2);
      font-weight: 600;
      text-decoration: none;
    }
    .card-footer-link a:hover { text-decoration: underline; }

    /* Divider */
    .divider {
      display: flex;
      align-items: center;
      gap: .75rem;
      margin: 1.25rem 0;
      color: #D6B8A4;
      font-size: .75rem;
    }
    .divider::before, .divider::after {
      content: '';
      flex: 1;
      height: 1px;
      background: #FFE0CC;
    }

    /* ── Bottom footer ───────────────────────────────────── */
    .page-footer {
      margin-top: 1.5rem;
      text-align: center;
      animation: fadeIn .6s .3s ease both;
    }
    .page-footer p {
      font-size: .73rem;
      color: rgba(255,255,255,.5);
      line-height: 1.8;
    }
    .page-footer strong { color: rgba(255,255,255,.75); }

    /* ── Animations ──────────────────────────────────────── */
    @keyframes slideUp   { from { opacity:0; transform:translateY(20px); } to { opacity:1; transform:none; } }
    @keyframes slideDown { from { opacity:0; transform:translateY(-12px); } to { opacity:1; transform:none; } }
    @keyframes fadeIn    { from { opacity:0; } to { opacity:1; } }

    /* ── Responsive ──────────────────────────────────────── */
    @media (max-width: 480px) {
      .card-header { padding: 1.5rem 1.5rem 1.35rem; }
      .card-body   { padding: 1.5rem; }
    }
  </style>
</head>
<body>
<div class="bg-texture"></div>

<!-- Capstone notice above the card -->
<div class="capstone-banner">
  <div class="capstone-banner-dot"></div>
  <p>
    <strong>Academic Project Notice —</strong>
    This system is a <strong>Capstone Project</strong> developed by BSIT students of Samar College.
    For academic use only.
  </p>
</div>

<!-- Login card -->
<div class="card">

  <!-- Orange header -->
  <div class="card-header">
    <div class="logo-row">
      <div class="logo-icon">🎓</div>
      <div class="logo-text">
        <div class="system-name">CITAS</div>
        <div class="system-sub">Internship Monitoring System</div>
      </div>
    </div>
    <h1>Welcome back</h1>
    <p>Sign in to access your internship portal</p>
  </div>

  <div class="card-body">

    <!-- Error message (PHP) -->
    <?php if (!empty($error)): ?>
      <div class="alert-error">
        <span>⚠️</span>
        <?php echo htmlspecialchars($error); ?>
      </div>
    <?php endif; ?>

    <form method="POST" action="">

      <div class="field">
        <label for="email">School Email Address</label>
        <div class="input-wrap">
          <span class="input-icon">✉️</span>
          <input
            type="email"
            id="email"
            name="email"
            placeholder="you@samar.edu.ph"
            value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
            required
            autofocus
          >
        </div>
      </div>

      <div class="field">
        <label for="password">Password</label>
        <div class="input-wrap">
          <span class="input-icon">🔒</span>
          <input
            type="password"
            id="password"
            name="password"
            placeholder="Enter your password"
            required
          >
        </div>
      </div>

      <button class="submit-btn" type="submit">
        Sign In →
      </button>

    </form>

    <div class="card-footer-link">
      Don't have an account? <a href="signup.php">Register here</a>
    </div>

  </div>
</div>

<!-- Page footer -->
<div class="page-footer">
  <p>
    <strong>CITAS Internship Monitoring System</strong><br>
    Capstone Project 2025–2026 &nbsp;·&nbsp; Samar College BSIT Students<br>
    For academic and demonstration purposes only
  </p>
</div>

</body>
</html>