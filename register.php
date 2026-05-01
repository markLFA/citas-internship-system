<?php
// ============================================================
//  register.php — Intern / Coordinator self-registration
//
//  What this does on POST:
//  1. Validates all fields
//  2. Inserts a row into `users`  (is_active = 0 for interns
//     so the coordinator must approve before they can log in)
//  3. If the role is intern, also inserts:
//       • `intern_profiles`  — school / course / year / phone
//       • `companies`        — placeholder row so the FK exists
//       • `internships`      — links intern ↔ company (empty dates)
// ============================================================

require_once __DIR__ . '/config/db.php';   // provides getDB()
session_start();

// Redirect if already logged in
if (isset($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}

// ── Helpers ──────────────────────────────────────────────────

function h(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

/** Return the POST value trimmed, or '' if not set. */
function post(string $key): string {
    return trim($_POST[$key] ?? '');
}

/** Map the form role value to the DB role enum. */
function map_role(string $formRole): string {
    return match($formRole) {
        'intern'      => 'intern',
        'coordinator' => 'coordinator',
        default       => 'intern',
    };
}

// ── Validation ────────────────────────────────────────────────

function validate(array $data): array {
    $errors = [];

    if (empty($data['name']))
        $errors[] = 'Full name is required.';

    if (empty($data['email']))
        $errors[] = 'Email address is required.';
    elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL))
        $errors[] = 'Please enter a valid email address.';

    if (empty($data['role']))
        $errors[] = 'Please select a role.';

    if (empty($data['password']))
        $errors[] = 'Password is required.';
    elseif (strlen($data['password']) < 6)
        $errors[] = 'Password must be at least 6 characters.';
    elseif ($data['password'] !== $data['confirm_password'])
        $errors[] = 'Passwords do not match.';

    // Extra fields required for interns
    if ($data['role'] === 'intern') {
        if (empty($data['course']))
            $errors[] = 'Course / Department is required for interns.';
    }

    return $errors;
}

// ── Registration logic ────────────────────────────────────────

/**
 * Insert the user account.
 * Interns are created with is_active = 0 so they cannot log in
 * until the coordinator approves them.
 */
function create_user(array $data): int {
    $db   = getDB();
    $role = map_role($data['role']);

    // Interns start inactive; coordinators are active immediately
    $active = 0;
    $hash   = password_hash($data['password'], PASSWORD_DEFAULT);

    $stmt = $db->prepare(
        'INSERT INTO users (name, email, password, role, is_active)
         VALUES (:name, :email, :password, :role, :is_active)'
    );
    $stmt->execute([
        ':name'      => $data['name'],
        ':email'     => $data['email'],
        ':password'  => $hash,
        ':role'      => $role,
        ':is_active' => $active,
    ]);

    return (int)$db->lastInsertId();
}

/**
 * Insert a placeholder company row.
 * Interns fill in their real company details later from their profile page.
 * Returns the new company ID.
 */
function create_placeholder_company(): int {
    $db   = getDB();
    $stmt = $db->prepare(
        'INSERT INTO companies (name, address) VALUES (:name, :address)'
    );
    $stmt->execute([':name' => 'Not yet assigned', ':address' => 'Enter company address here']);
    return (int)$db->lastInsertId();
}

/**
 * Insert the intern_profile row with the details from registration.
 * The rest (phone, joined_date) can be filled in later from the profile page.
 */
function create_intern_profile(int $userId, array $data): void {
    $db   = getDB();
    $stmt = $db->prepare(
        'INSERT INTO intern_profiles
           (user_id, course, year_level, phone, required_hours)
         VALUES
           (:user_id, :course, :year_level, :phone, :required_hours)'
    );
    $stmt->execute([
        ':user_id'       => $userId,
        ':course'        => $data['course']     ?: null,
        ':year_level'    => $data['year_level'] ?: null,
        ':phone'         => $data['phone']      ?: null,
        ':required_hours'=> 500,   // default; coordinator can adjust later
    ]);
}

/**
 * Link the intern to the placeholder company via the internships table.
 * All date / position fields are left NULL — filled in later.
 */
function create_internship(int $userId, int $companyId): void {
    $db   = getDB();
    $stmt = $db->prepare(
        'INSERT INTO internships (intern_id, company_id, status)
         VALUES (:intern_id, :company_id, :status)'
    );
    $stmt->execute([
        ':intern_id'  => $userId,
        ':company_id' => $companyId,
        ':status'     => 'active',
    ]);
}

/**
 * Check whether an email already exists in the users table.
 */
function email_taken(string $email): bool {
    $db   = getDB();
    $stmt = $db->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
    $stmt->execute([':email' => $email]);
    return (bool)$stmt->fetch();
}

// ── Handle POST ───────────────────────────────────────────────

$errors  = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $data = [
        'name'             => post('name'),
        'email'            => post('email'),
        'role'             => post('role'),
        'course'           => post('course'),
        'year_level'       => post('year_level'),
        'phone'            => post('phone'),
        'password'         => $_POST['password']         ?? '',
        'confirm_password' => $_POST['confirm_password'] ?? '',
    ];

    // Step 1 — validate fields
    $errors = validate($data);

    // Step 2 — check for duplicate email
    if (empty($errors) && email_taken($data['email'])) {
        $errors[] = 'An account with that email already exists.';
    }

    // Step 3 — insert records in a transaction so it's all-or-nothing
    if (empty($errors)) {
        try {
            $db = getDB();
            $db->beginTransaction();

            // Always create the user first
            $userId = create_user($data);

            // Extra tables only for interns
            if (map_role($data['role']) === 'intern') {
                $companyId = create_placeholder_company();
                create_intern_profile($userId, $data);
                create_internship($userId, $companyId);
            }

            $db->commit();

            $isIntern = map_role($data['role']) === 'intern';
            $success  = $isIntern
                ? 'Account created! Please wait for your coordinator to approve your account before logging in.'
                : 'Account created successfully! wait for Admin aproaval.';

            // Clear POST data so form resets
            $_POST = [];

        } catch (PDOException $e) {
            $db->rollBack();
            // Show a safe message; log the real error server-side
            error_log('Registration error: ' . $e->getMessage());
            $errors[] = 'Something went wrong. Please try again later.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>CITAS — Create Account</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;500;600;700;800&family=DM+Sans:wght@400;500&display=swap" rel="stylesheet">
  <style>
    :root {
      --o1: #FF6B00; --o2: #EA580C; --o3: #C2410C;
      --pale: #FFF7ED; --ring: rgba(234,88,12,.25);
      --text-dark: #1A0A00; --text-mid: #6B3A1F; --text-muted: #9A6647;
    }
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    body {
      min-height: 100vh; font-family: 'DM Sans', sans-serif;
      background: var(--o3);
      display: flex; flex-direction: column; align-items: center; justify-content: center;
      padding: 1.5rem 1rem 2rem; overflow-x: hidden; position: relative;
    }
    body::before, body::after { content:''; position:fixed; border-radius:50%; pointer-events:none; }
    body::before { width:520px;height:520px;top:-180px;right:-140px; background:radial-gradient(circle,rgba(255,140,0,.35) 0%,transparent 70%); }
    body::after  { width:400px;height:400px;bottom:-130px;left:-100px; background:radial-gradient(circle,rgba(255,100,0,.2) 0%,transparent 70%); }

    /* ── Banner ───────────────────────────────────────────── */
    .banner {
      width:100%; max-width:480px;
      background:rgba(255,255,255,.12); backdrop-filter:blur(8px);
      border:1px solid rgba(255,255,255,.2); border-radius:10px;
      padding:.6rem 1rem; margin-bottom:1rem;
      display:flex; align-items:center; gap:.6rem;
    }
    .dot { width:8px;height:8px;border-radius:50%;background:#FCD34D;flex-shrink:0;box-shadow:0 0 6px #FCD34D;animation:blink 2s infinite; }
    @keyframes blink { 0%,100%{opacity:1;transform:scale(1)}50%{opacity:.6;transform:scale(.85)} }
    .banner p { font-size:.78rem;color:rgba(255,255,255,.9);line-height:1.4; }
    .banner strong { color:#FCD34D; }

    /* ── Card ─────────────────────────────────────────────── */
    .card {
      width:100%; max-width:480px; background:#fff; border-radius:20px; overflow:hidden;
      box-shadow:0 24px 64px rgba(194,65,12,.18),0 4px 16px rgba(0,0,0,.08);
    }
    .card-head {
      background:linear-gradient(135deg,var(--o1) 0%,var(--o2) 60%,var(--o3) 100%);
      padding:1.75rem 2rem 1.6rem; position:relative; overflow:hidden;
    }
    .card-head::before { content:'';position:absolute;width:180px;height:180px;border-radius:50%;background:rgba(255,255,255,.08);top:-60px;right:-40px; }
    .logo-row { display:flex;align-items:center;gap:.75rem;margin-bottom:.9rem;position:relative;z-index:1; }
    .logo-icon { width:46px;height:46px;background:rgba(255,255,255,.2);border:1.5px solid rgba(255,255,255,.35);border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.5rem; }
    .logo-name { font-family:'Sora',sans-serif;font-size:1.15rem;font-weight:800;color:#fff; }
    .logo-sub  { font-size:.72rem;opacity:.8;color:#fff;margin-top:.1rem; }
    .card-head h1 { font-family:'Sora',sans-serif;font-size:1.4rem;font-weight:800;color:#fff;position:relative;z-index:1;letter-spacing:-.3px; }
    .card-head p  { color:rgba(255,255,255,.75);font-size:.83rem;margin-top:.25rem;position:relative;z-index:1; }

    .card-body { padding:1.75rem 2rem 2rem; }

    /* ── Alerts ───────────────────────────────────────────── */
    .alert {
      display:flex; align-items:flex-start; gap:.5rem;
      border-radius:10px; padding:.8rem 1rem; margin-bottom:1.1rem;
      font-size:.83rem; font-weight:500;
    }
    .alert ul { list-style:none; display:flex; flex-direction:column; gap:.25rem; }
    .alert li::before { content:'⚠ '; }
    .alert-error   { background:#FEF2F2; border:1px solid #FECACA; color:#991B1B; }
    .alert-success { background:#F0FDF4; border:1px solid #BBF7D0; color:#166534; }

    /* ── Section divider ──────────────────────────────────── */
    .section-label {
      font-size:.7rem; font-weight:700; text-transform:uppercase;
      letter-spacing:.08em; color:var(--o2);
      border-bottom:1px solid #FED7AA; padding-bottom:.4rem;
      margin:1.1rem 0 .85rem;
    }
    .section-label:first-child { margin-top:0; }

    /* ── Intern-only fields (hidden by default) ───────────── */
    #intern-fields { display:none; }

    /* ── Form ─────────────────────────────────────────────── */
    .field { margin-bottom:.95rem; }
    label  { display:block;font-size:.79rem;font-weight:600;color:var(--text-mid);margin-bottom:.35rem; }
    .hint  { font-size:.72rem;color:var(--text-muted);margin-top:.3rem; }

    .inp-wrap { position:relative; }
    .inp-icon { position:absolute;left:.85rem;top:50%;transform:translateY(-50%);font-size:.95rem;pointer-events:none;opacity:.4; }

    input[type="text"], input[type="email"],
    input[type="password"], input[type="tel"],
    select {
      display:block; width:100%;
      padding:.68rem .85rem .68rem 2.4rem;
      font-size:.875rem; font-family:'DM Sans',sans-serif;
      color:var(--text-dark); background:var(--pale);
      border:1.5px solid #FED7AA; border-radius:10px; outline:none;
      transition:border-color .15s,box-shadow .15s,background .15s;
      -webkit-appearance:none;
    }
    select { padding-left:.85rem; }   /* select has no icon */
    input::placeholder { color:#C4845A;opacity:.7; }
    input:focus, select:focus { border-color:var(--o2);background:#fff;box-shadow:0 0 0 3px var(--ring); }
    input.err, select.err { border-color:#EF4444;background:#FEF2F2; }

    /* Two-column row */
    .field-row { display:grid;grid-template-columns:1fr 1fr;gap:.75rem; }
    @media(max-width:480px){ .field-row{grid-template-columns:1fr;} }

    /* ── Submit ───────────────────────────────────────────── */
    .btn-submit {
      display:flex;align-items:center;justify-content:center;gap:.5rem;
      width:100%;padding:.8rem;margin-top:1.4rem;
      background:linear-gradient(135deg,var(--o1) 0%,var(--o2) 100%);
      color:#fff;font-family:'Sora',sans-serif;font-size:.95rem;font-weight:700;
      border:none;border-radius:10px;cursor:pointer;
      box-shadow:0 4px 14px rgba(234,88,12,.4);
      transition:filter .15s,transform .12s;
    }
    .btn-submit:hover  { filter:brightness(1.08);transform:translateY(-1px); }
    .btn-submit:active { transform:none; }

    .card-link { text-align:center;margin-top:1.1rem;font-size:.83rem;color:var(--text-muted); }
    .card-link a { color:var(--o2);font-weight:600;text-decoration:none; }
    .card-link a:hover { text-decoration:underline; }

    .page-foot { margin-top:1.5rem;text-align:center; }
    .page-foot p { font-size:.73rem;color:rgba(255,255,255,.5);line-height:1.9; }
    .page-foot strong { color:rgba(255,255,255,.75); }

    /* ── Role info callout ────────────────────────────────── */
    .role-info {
      background:var(--pale); border:1px solid #FED7AA; border-radius:8px;
      padding:.65rem .85rem; font-size:.78rem; color:var(--text-mid);
      margin-top:.5rem; display:none;
    }
  </style>
</head>
<body>

<div class="banner">
  <div class="dot"></div>
  <p><strong>Academic Project — </strong>Registration is for a <strong>Capstone Project (Academic Use Only)</strong>.</p>
</div>

<div class="card">

  <div class="card-head">
    <div class="logo-row">
      <div class="logo-icon">🎓</div>
      <div>
        <div class="logo-name">CITAS</div>
        <div class="logo-sub">Internship Monitoring System</div>
      </div>
    </div>
    <h1>Create your account</h1>
    <p>Fill in your details to get started</p>
  </div>

  <div class="card-body">

    <?php if (!empty($errors)): ?>
      <div class="alert alert-error">
        <ul>
          <?php foreach ($errors as $e): ?>
            <li><?= h($e) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
      <div class="alert alert-success">
        <span>✅</span>&nbsp;<?= h($success) ?>
      </div>
    <?php endif; ?>

    <form method="POST" action="" novalidate>

      <!-- ── Personal Information ──────────────────────────── -->
      <div class="section-label">Personal Information</div>

      <div class="field">
        <label for="name">Full Name</label>
        <div class="inp-wrap">
          <span class="inp-icon">👤</span>
          <input type="text" id="name" name="name"
            placeholder="e.g. Juan dela Cruz"
            value="<?= h(post('name')) ?>" required>
        </div>
      </div>

      <div class="field">
        <label for="email">School Email Address</label>
        <div class="inp-wrap">
          <span class="inp-icon">✉️</span>
          <input type="email" id="email" name="email"
            placeholder="you@samar.edu.ph"
            value="<?= h(post('email')) ?>" required autocomplete="email">
        </div>
        <div class="hint">Use your official school email if applicable.</div>
      </div>

      <!-- ── Account Setup ─────────────────────────────────── -->
      <div class="section-label">Account Setup</div>

      <div class="field">
        <label for="role">I am registering as…</label>
        <select id="role" name="role" required>
          <option value="" disabled <?= empty(post('role')) ? 'selected' : '' ?>>Select your role…</option>
          <option value="intern"      <?= post('role')==='intern'      ? 'selected' : '' ?>>🎒 Student Intern</option>
          <option value="coordinator" <?= post('role')==='coordinator' ? 'selected' : '' ?>>🗂 Internship Coordinator</option>
        </select>

        <!-- Shows below the select based on chosen role -->
        <div class="role-info" id="info-intern">
          ⏳ Intern accounts require coordinator approval before you can log in.
        </div>
        <div class="role-info" id="info-coordinator">
          ⏳ Coordinator accounts will be activated after Admin approaval.
        </div>
      </div>

      <!-- ── Replace the entire Internship Details section ───────── -->
      <div id="intern-fields">
        <div class="section-label">Internship Details</div>

        <div class="field-row">
          <div class="field">
            <label for="course">Course</label>
            <select id="course" name="course">
              <option value="" disabled <?= empty(post('course')) ? 'selected' : '' ?>>
                Select your course...
              </option>
              <option value="BSIT" <?= post('course') === 'BSIT' ? 'selected' : '' ?>>BSIT</option>
              <option value="BSCS" <?= post('course') === 'BSCS' ? 'selected' : '' ?>>BSCS</option>
              <option value="BSA"  <?= post('course') === 'BSA'  ? 'selected' : '' ?>>BSA</option>
              <option value="BSBA" <?= post('course') === 'BSBA' ? 'selected' : '' ?>>BSBA</option>
              <option value="BEED" <?= post('course') === 'BEED' ? 'selected' : '' ?>>BEED</option>
              <option value="BSED" <?= post('course') === 'BSED' ? 'selected' : '' ?>>BSED</option>
            </select>
          </div>

          <div class="field">
            <label for="year_level">Year Level</label>
            <select id="year_level" name="year_level">
              <option value="" disabled <?= empty(post('year_level')) ? 'selected' : '' ?>>
                Select year level...
              </option>
              <option value="1st Year" <?= post('year_level') === '1st Year' ? 'selected' : '' ?>>1st Year</option>
              <option value="2nd Year" <?= post('year_level') === '2nd Year' ? 'selected' : '' ?>>2nd Year</option>
              <option value="3rd Year" <?= post('year_level') === '3rd Year' ? 'selected' : '' ?>>3rd Year</option>
              <option value="4th Year" <?= post('year_level') === '4th Year' ? 'selected' : '' ?>>4th Year</option>
            </select>
          </div>
        </div>

        <div class="field">
          <label for="phone">
            Phone Number
            <span style="font-weight:400;opacity:.6">(optional)</span>
          </label>
          <div class="inp-wrap">
            <span class="inp-icon">📱</span>
            <input
              type="tel"
              id="phone"
              name="phone"
              placeholder="+63 9xx xxx xxxx"
              value="<?= h(post('phone')) ?>"
            >
          </div>
        </div>

        <p style="font-size:.75rem;color:#9A6647;margin-bottom:.5rem">
          💡 Company and supervisor details can be filled in later from your profile page.
        </p>
      </div>

      <!-- ── Password ──────────────────────────────────────── -->
      <div class="section-label">Password</div>

      <div class="field-row">
        <div class="field">
          <label for="password">Create Password</label>
          <div class="inp-wrap">
            <span class="inp-icon">🔒</span>
            <input type="password" id="password" name="password"
              placeholder="Min. 6 characters" required autocomplete="new-password">
          </div>
          <div class="hint">Minimum 6 characters.</div>
        </div>
        <div class="field">
          <label for="confirm_password">Confirm Password</label>
          <div class="inp-wrap">
            <span class="inp-icon">🔑</span>
            <input type="password" id="confirm_password" name="confirm_password"
              placeholder="Repeat password" required autocomplete="new-password">
          </div>
        </div>
      </div>

      <button class="btn-submit" type="submit">Create Account →</button>

    </form>

    <div class="card-link">
      Already have an account? <a href="index.php">Sign in here</a>
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
// ── Update JavaScript ────────────────────────────────────────
const roleSelect   = document.getElementById('role');
const internFields = document.getElementById('intern-fields');
const infoIntern   = document.getElementById('info-intern');
const infoCoord    = document.getElementById('info-coordinator');
const courseInput  = document.getElementById('course');
const yearInput    = document.getElementById('year_level');

function updateRoleUI() {
  const role = roleSelect.value;

  internFields.style.display = role === 'intern' ? 'block' : 'none';
  infoIntern.style.display   = role === 'intern' ? 'block' : 'none';
  infoCoord.style.display    = role === 'coordinator' ? 'block' : 'none';

  courseInput.required = role === 'intern';
  yearInput.required   = role === 'intern';
}

roleSelect.addEventListener('change', updateRoleUI);
updateRoleUI();
</script>
</body>
</html>