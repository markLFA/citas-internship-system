<?php
// ============================================================
//  register.php — Intern / Coordinator self-registration
// ============================================================

require_once __DIR__ . '/config/db.php';
session_start();

if (isset($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}

function h(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

function post(string $key): string {
    return trim($_POST[$key] ?? '');
}

function map_role(string $formRole): string {
    return match($formRole) {
        'intern'      => 'intern',
        'coordinator' => 'coordinator',
        default       => 'intern',
    };
}

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

    if ($data['role'] === 'intern') {
        if (empty($data['course']))
            $errors[] = 'Course / Department is required for interns.';

        if (empty($data['coordinator_id']))
            $errors[] = 'Coordinator is required for interns.';
    }

    if (empty($data['terms'])) {
        $errors[] = 'You must read and agree to the Terms and Services to register.';
    }

    return $errors;
}

function create_user(array $data): int {
    $db   = getDB();
    $role = map_role($data['role']);
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

function create_placeholder_company(): int {
    $db   = getDB();
    $stmt = $db->prepare(
        'INSERT INTO companies (name, address) VALUES (:name, :address)'
    );
    $stmt->execute([':name' => 'Not yet assigned', ':address' => 'Enter company address here']);
    return (int)$db->lastInsertId();
}

function create_intern_profile(int $userId, array $data): void {
    $db   = getDB();
    $stmt = $db->prepare(
        'INSERT INTO intern_profiles
           (user_id, course, year_level, phone, coordinator_id, required_hours)
         VALUES
           (:user_id, :course, :year_level, :phone, :coordinator_id, :required_hours)'
    );
    $stmt->execute([
        ':user_id'       => $userId,
        ':course'        => $data['course']     ?: null,
        ':year_level'    => $data['year_level'] ?: null,
        ':phone'         => $data['phone']      ?: null,
        ':coordinator_id'=> $data['coordinator_id'] ?: null,
        ':required_hours'=> 500,
    ]);
}

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

function email_taken(string $email): bool {
    $db   = getDB();
    $stmt = $db->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
    $stmt->execute([':email' => $email]);
    return (bool)$stmt->fetch();
}

$errors  = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $data = [
        'name'             => post('name'),
        'email'            => post('email'),
        'role'             => post('role'),
        'course'           => post('course'),
        'year_level'       => post('year_level'),
        'phone'            => post('phone'),
        'coordinator_id'   => post('coordinator_id'),
        'password'         => $_POST['password']         ?? '',
        'confirm_password' => $_POST['confirm_password'] ?? '',
        'terms'            => post('terms'),
    ];

    $errors = validate($data);

    if (empty($errors) && email_taken($data['email'])) {
        $errors[] = 'An account with that email already exists.';
    }

    if (empty($errors)) {
        try {
            $db = getDB();
            $db->beginTransaction();

            $userId = create_user($data);

            if (map_role($data['role']) === 'intern') {
                $companyId = create_placeholder_company();
                create_intern_profile($userId, $data);
                create_internship($userId, $companyId);
            }

            $db->commit();

            $isIntern = map_role($data['role']) === 'intern';
            
            $_SESSION['flash_success'] = $isIntern
                ? 'Account created! Please wait for your coordinator to approve your account before logging in.'
                : 'Account created successfully! Please wait for Admin approval.';

            header('Location: index.php');
            exit;

        } catch (PDOException $e) {
            $db->rollBack();
            error_log('Registration error: ' . $e->getMessage());
            $errors[] = 'Something went wrong. Please try again later.';
        }
    }
}

function getCoordinators(): array
{
    $pdo = getDB();
    $sql = "SELECT id, name, email FROM users WHERE role = :role ORDER BY name ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':role' => 'coordinator']);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
$coordinators = getCoordinators();
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
      min-height: 100vh; font-family: 'DM Sans', sans-serif; background: var(--o3);
      display: flex; flex-direction: column; align-items: center; justify-content: center;
      padding: 1.5rem 1rem 2rem; overflow-x: hidden; position: relative;
    }
    body::before, body::after { content:''; position:fixed; border-radius:50%; pointer-events:none; }
    body::before { width:520px;height:520px;top:-180px;right:-140px; background:radial-gradient(circle,rgba(255,140,0,.35) 0%,transparent 70%); }
    body::after  { width:400px;height:400px;bottom:-130px;left:-100px; background:radial-gradient(circle,rgba(255,100,0,.2) 0%,transparent 70%); }

    .banner {
      width:100%; max-width:480px; background:rgba(255,255,255,.12); backdrop-filter:blur(8px);
      border:1px solid rgba(255,255,255,.2); border-radius:10px; padding:.6rem 1rem; margin-bottom:1rem;
      display:flex; align-items:center; gap:.6rem;
    }
    .dot { width:8px;height:8px;border-radius:50%;background:#FCD34D;flex-shrink:0;box-shadow:0 0 6px #FCD34D;animation:blink 2s infinite; }
    @keyframes blink { 0%,100%{opacity:1;transform:scale(1)}50%{opacity:.6;transform:scale(.85)} }
    .banner p { font-size:.78rem;color:rgba(255,255,255,.9);line-height:1.4; }
    .banner strong { color:#FCD34D; }

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

    .alert { display:flex; align-items:flex-start; gap:.5rem; border-radius:10px; padding:.8rem 1rem; margin-bottom:1.1rem; font-size:.83rem; font-weight:500; }
    .alert ul { list-style:none; display:flex; flex-direction:column; gap:.25rem; }
    .alert li::before { content:'⚠ '; }
    .alert-error   { background:#FEF2F2; border:1px solid #FECACA; color:#991B1B; }

    .section-label {
      font-size:.7rem; font-weight:700; text-transform:uppercase; letter-spacing:.08em; color:var(--o2);
      border-bottom:1px solid #FED7AA; padding-bottom:.4rem; margin:1.1rem 0 .85rem;
    }
    .section-label:first-child { margin-top:0; }

    #intern-fields { display:none; }

    .field { margin-bottom:.95rem; }
    label  { display:block;font-size:.79rem;font-weight:600;color:var(--text-mid);margin-bottom:.35rem; }
    .hint  { font-size:.72rem;color:var(--text-muted);margin-top:.3rem; }

    .inp-wrap { position:relative; }
    .inp-icon { position:absolute;left:.85rem;top:50%;transform:translateY(-50%);font-size:.95rem;pointer-events:none;opacity:.4; z-index:2; }
    
    .toggle-pass {
      position: absolute; right: .85rem; top: 50%; transform: translateY(-50%);
      background: none; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center;
      padding: 0; margin: 0; opacity: 0.4; transition: opacity 0.15s; z-index: 5;
    }
    .toggle-pass:hover { opacity: 0.8; }
    .toggle-pass svg { width: 18px; height: 18px; fill: #6B3A1F; }

    /* FIX: Standardized generalized input wrappers to eliminate layout breakages */
    .inp-wrap input, select {
      display:block; width:100%; padding:.68rem .85rem .68rem 2.4rem;
      font-size:.875rem; font-family:'DM Sans',sans-serif; color:var(--text-dark); background:var(--pale);
      border:1.5px solid #FED7AA; border-radius:10px; outline:none; transition:border-color .15s,box-shadow .15s,background .15s;
      -webkit-appearance:none;
    }
    .inp-wrap input { padding-right: 2.5rem; }
    select { padding-left:.85rem; }
    input::placeholder { color:#C4845A;opacity:#.7; }
    input:focus, select:focus { border-color:var(--o2);background:#fff;box-shadow:0 0 0 3px var(--ring); }
    input.err, select.err { border-color:#EF4444;background:#FEF2F2; }

    .terms-field { display: flex; align-items: flex-start; gap: .6rem; margin: 1.25rem 0 .5rem; }
    .terms-field input[type="checkbox"] { accent-color: var(--o2); width: 16px; height: 16px; margin-top: 2px; cursor: pointer; flex-shrink: 0; }
    .terms-field label { font-size: .8rem; font-weight: 500; color: var(--text-dark); cursor: pointer; line-height: 1.4; }
    .terms-field a { color: var(--o2); font-weight: 600; text-decoration: none; }
    .terms-field a:hover { text-decoration: underline; }

    .field-row { display:grid;grid-template-columns:1fr 1fr;gap:.75rem; }
    @media(max-width:480px){ .field-row{grid-template-columns:1fr;} }

    .btn-submit {
      display:flex;align-items:center;justify-content:center;gap:.5rem; width:100%;padding:.8rem;margin-top:1.4rem;
      background:linear-gradient(135deg,var(--o1) 0%,var(--o2) 100%); color:#fff;font-family:'Sora',sans-serif;font-size:.95rem;font-weight:700;
      border:none;border-radius:10px;cursor:pointer; box-shadow:0 4px 14px rgba(234,88,12,.4); transition:filter .15s,transform .12s;
    }
    .btn-submit:hover  { filter:brightness(1.08);transform:translateY(-1px); }
    .btn-submit:active { transform:none; }

    .card-link { text-align:center;margin-top:1.1rem;font-size:.83rem;color:var(--text-muted); }
    .card-link a { color:var(--o2);font-weight:600;text-decoration:none; }
    .card-link a:hover { text-decoration:underline; }

    .page-foot { margin-top:1.5rem;text-align:center; }
    .page-foot p { font-size:.73rem;color:rgba(255,255,255,.5);line-height:1.9; }
    .page-foot strong { color:rgba(255,255,255,.75); }

    .role-info { background:var(--pale); border:1px solid #FED7AA; border-radius:8px; padding:.65rem .85rem; font-size:.78rem; color:var(--text-mid); margin-top:.5rem; display:none; }
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

    <form method="POST" action="" novalidate>

      <div class="section-label">Personal Information</div>

      <div class="field">
        <label for="name">Full Name</label>
        <div class="inp-wrap">
          <span class="inp-icon">👤</span>
          <input type="text" id="name" name="name" placeholder="e.g. Juan dela Cruz" value="<?= h(post('name')) ?>" required>
        </div>
      </div>

      <div class="field">
        <label for="email">School Email Address</label>
        <div class="inp-wrap">
          <span class="inp-icon">✉️</span>
          <input type="email" id="email" name="email" placeholder="you@samar.edu.ph" value="<?= h(post('email')) ?>" required autocomplete="email">
        </div>
        <div class="hint">Use your official school email if applicable.</div>
      </div>

      <div class="section-label">Account Setup</div>

      <div class="field">
        <label for="role">I am registering as…</label>
        <select id="role" name="role" required>
          <option value="" disabled <?= empty(post('role')) ? 'selected' : '' ?>>Select your role…</option>
          <option value="intern"      <?= post('role')==='intern'      ? 'selected' : '' ?>>🎒 Student Intern</option>
          <option value="coordinator" <?= post('role')==='coordinator' ? 'selected' : '' ?>>🗂 Internship Coordinator</option>
        </select>

        <div class="role-info" id="info-intern">⏳ Intern accounts require coordinator approval before you can log in.</div>
        <div class="role-info" id="info-coordinator">⏳ Coordinator accounts will be activated after Admin approval.</div>
      </div>

      <div id="intern-fields">
        <div class="section-label">Internship Details</div>

        <div class="field-row">
          <div class="field">
            <label for="course">Course</label>
            <select id="course" name="course">
              <option value="" disabled <?= empty(post('course')) ? 'selected' : '' ?>>Select your course...</option>
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
              <option value="" disabled <?= empty(post('year_level')) ? 'selected' : '' ?>>Select year level...</option>
              <option value="1st Year" <?= post('year_level') === '1st Year' ? 'selected' : '' ?>>1st Year</option>
              <option value="2nd Year" <?= post('year_level') === '2nd Year' ? 'selected' : '' ?>>2nd Year</option>
              <option value="3rd Year" <?= post('year_level') === '3rd Year' ? 'selected' : '' ?>>3rd Year</option>
              <option value="4th Year" <?= post('year_level') === '4th Year' ? 'selected' : '' ?>>4th Year</option>
            </select>
          </div>
        </div>

        <div class="field">
          <label for="coordinator_id">Coordinator</label>
          <select id="coordinator_id" name="coordinator_id">
            <option value="" disabled <?= empty(post('coordinator_id')) ? 'selected' : '' ?>>Select coordinator...</option>
            <?php foreach ($coordinators as $coordinator): ?>
              <option value="<?= $coordinator['id'] ?>" <?= post('coordinator_id') == $coordinator['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($coordinator['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="field">
          <label for="phone">Phone Number <span style="font-weight:400;opacity:.6">(optional)</span></label>
          <div class="inp-wrap">
            <span class="inp-icon">📱</span>
            <input type="tel" id="phone" name="phone" placeholder="+63 9xx xxx xxxx" value="<?= h(post('phone')) ?>">
          </div>
        </div>

        <p style="font-size:.75rem;color:#9A6647;margin-bottom:.5rem">
          💡 Company and supervisor details can be filled in later from your profile page.
        </p>
      </div>

      <div class="section-label">Password</div>

      <div class="field-row">
        <div class="field">
          <label for="password">Create Password</label>
          <div class="inp-wrap">
            <span class="inp-icon">🔒</span>
            <input type="password" id="password" name="password" placeholder="Min. 6 characters" required autocomplete="new-password">
            <button type="button" class="toggle-pass" data-target="password" aria-label="Toggle visibility">
              <svg class="eye-open" viewBox="0 0 24 24"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg>
            </button>
          </div>
          <div class="hint">Minimum 6 characters.</div>
        </div>
        <div class="field">
          <label for="confirm_password">Confirm Password</label>
          <div class="inp-wrap">
            <span class="inp-icon">🔑</span>
            <input type="password" id="confirm_password" name="confirm_password" placeholder="Repeat password" required autocomplete="new-password">
            <button type="button" class="toggle-pass" data-target="confirm_password" aria-label="Toggle visibility">
              <svg class="eye-open" viewBox="0 0 24 24"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg>
            </button>
          </div>
        </div>
      </div>

      <div class="terms-field">
        <input type="checkbox" id="terms" name="terms" value="yes" <?= post('terms') === 'yes' ? 'checked' : '' ?> required>
        <label for="terms">
          I read and agree to the <a href="#" onclick="alert('Terms of Service:\n\nThis application is strictly for academic and capstone evaluation deployment purposes. All logged data including profiles, attendance, logs, and information uploaded will safely map into the project platform databases.'); return false;">Terms and Services</a> and data privacy guidelines for academic evaluation.
        </label>
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
const roleSelect   = document.getElementById('role');
const internFields = document.getElementById('intern-fields');
const infoIntern   = document.getElementById('info-intern');
const infoCoord    = document.getElementById('info-coordinator');
const courseInput  = document.getElementById('course');
const yearInput    = document.getElementById('year_level');
const coordinatorInput    = document.getElementById('coordinator_id');

function updateRoleUI() {
  const role = roleSelect.value;
  internFields.style.display = role === 'intern' ? 'block' : 'none';
  infoIntern.style.display   = role === 'intern' ? 'block' : 'none';
  infoCoord.style.display    = role === 'coordinator' ? 'block' : 'none';

  courseInput.required = role === 'intern';
  yearInput.required   = role === 'intern';
  coordinatorInput.required = role === 'intern';
}

roleSelect.addEventListener('change', updateRoleUI);
updateRoleUI();

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