<?php
// ════════════════════════════════════════════════
//  index.php  |  Login page
//  Redirects to dashboard.php on success
// ════════════════════════════════════════════════
require_once 'includes/auth.php';

// Already logged in — go straight to dashboard
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>CITAS – Sign In</title>
  <link href="https://fonts.googleapis.com/css2?family=EB+Garamond:ital,wght@0,400;0,600;0,700;1,400&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="style.css"/>
</head>
<body>

<div id="login-page">

  <!-- Left panel — branding & stats -->
  <div class="login-left">
    <div class="login-left-inner">
      <div class="login-seal">🎓</div>
      <div class="login-tagline">Track. Monitor.<br/><em>Analyze.</em></div>
      <div class="login-sub">
        CITAS Internship Monitoring &amp; Data Analytics System<br/>
        College of Information Technology and Applied Sciences
      </div>
      <div class="login-deco">
        <div class="login-deco-item">
          <div class="num">284</div>
          <div class="lbl">Enrolled Interns</div>
        </div>
        <div class="login-deco-item">
          <div class="num">47</div>
          <div class="lbl">Partner Companies</div>
        </div>
        <div class="login-deco-item">
          <div class="num">91%</div>
          <div class="lbl">Completion Rate</div>
        </div>
        <div class="login-deco-item">
          <div class="num">A.Y. 2025</div>
          <div class="lbl">Current Term</div>
        </div>
      </div>
    </div>
  </div>

  <!-- Right panel — login form -->
  <div class="login-right">
    <div class="login-form-wrap">
      <h2>Welcome back</h2>
      <p>Sign in to access the CITAS internship portal</p>

      <?php if ($loginError): ?>
        <div class="login-error"><?= e($loginError) ?></div>
      <?php endif; ?>

      <form method="POST" action="index.php">
        <div class="form-group">
          <label>Role</label>
          <select name="role">
            <option value="coordinator">Internship Coordinator</option>
            <option value="faculty">Faculty Supervisor</option>
            <option value="student">Student / Intern</option>
            <option value="admin">Department Admin</option>
          </select>
        </div>
        <div class="form-group">
          <label>Institutional Email</label>
          <input
            type="email"
            name="email"
            placeholder="yourname@institution.edu.ph"
            value="<?= e($_POST['email'] ?? '') ?>"
            required
          />
        </div>
        <div class="form-group">
          <label>Password</label>
          <input type="password" name="password" placeholder="••••••••" required/>
        </div>
        <button type="submit" class="btn-login">Sign In →</button>
      </form>

      <div class="login-footer">
        <strong>Demo:</strong> coordinator@citas.edu.ph / citas2025<br/>
        CITAS IMADAS &nbsp;·&nbsp; Academic Year 2024–2025 &nbsp;·&nbsp; v1.0.0
      </div>
    </div>
  </div>

</div>

</body>
</html>
