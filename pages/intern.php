<?php
// ════════════════════════════════════════════════
//  pages/intern.php  |  Intern / Student Portal
//  Requires session — run from project root or
//  adjust require paths as needed.
// ════════════════════════════════════════════════
require_once '../includes/auth.php';
requireLogin();

$user      = currentUser();
$pageTitle = 'My Internship';

// ── Mock data (replace with PDO queries) ────────
// TODO: $stmt = $pdo->prepare('SELECT * FROM students WHERE user_id = ?');
$intern = [
    'name'           => $user['name'],
    'student_number' => '2021-10021',
    'program'        => 'BSIT',
    'year_level'     => 3,
    'section'        => 'A',
    'email'          => $user['email'],
    'contact'        => '+63 917 123 4567',
    'academic_year'  => '2024-2025',
    'semester'       => '2nd',
    'rendered_hours' => 380,
    'required_hours' => 480,
    'status'         => 'active',
    'supervisor'     => 'Prof. Ramon Dela Torre',
    'supervisor_email' => 'rdelatorre@citas.edu.ph',
];

// Company info — NULL means not yet set
$company = [
    'name'               => 'Accenture Philippines',
    'industry'           => 'IT Services / Consulting',
    'address'            => '6750 Ayala Avenue, Makati City',
    'city'               => 'Makati',
    'job_title'          => 'Software Development Intern',
    'start_date'         => '2025-01-13',
    'end_date'           => '2025-05-30',
    'supervisor_name'    => 'Ms. Patricia Reyes',
    'supervisor_email'   => 'p.reyes@accenture.com',
    'supervisor_contact' => '+63 918 876 5432',
    'supervisor_title'   => 'Senior Software Engineer',
];
// Set to null to show "not yet set" state: $company = null;

// Weekly reports
$reports = [
    ['id'=>1, 'week'=>8, 'title'=>'Week 8 Journal', 'type'=>'pdf',  'file'=>'week8_journal.pdf',  'submitted'=>'2025-03-23', 'status'=>'pending',  'remarks'=>''],
    ['id'=>2, 'week'=>7, 'title'=>'Week 7 Journal', 'type'=>'docx', 'file'=>'week7_journal.docx', 'submitted'=>'2025-03-16', 'status'=>'approved', 'remarks'=>'Good progress noted.'],
    ['id'=>3, 'week'=>6, 'title'=>'Week 6 Journal', 'type'=>'pdf',  'file'=>'week6_journal.pdf',  'submitted'=>'2025-03-09', 'status'=>'approved', 'remarks'=>''],
    ['id'=>4, 'week'=>5, 'title'=>'Week 5 Journal', 'type'=>'docx', 'file'=>'week5_journal.docx', 'submitted'=>'2025-03-02', 'status'=>'approved', 'remarks'=>''],
    ['id'=>5, 'week'=>4, 'title'=>'Week 4 Journal', 'type'=>'pdf',  'file'=>'week4_journal.pdf',  'submitted'=>'2025-02-23', 'status'=>'rejected', 'remarks'=>'Please revise — missing daily reflection.'],
];

// DTR records
$dtr = [
    ['month'=>'March 2025', 'days'=>18, 'hours'=>144, 'status'=>'pending'],
    ['month'=>'February 2025', 'days'=>20, 'hours'=>160, 'status'=>'approved'],
    ['month'=>'January 2025', 'days'=>10, 'hours'=>76,  'status'=>'approved'],
];

// Announcements (coordinator-posted)
$announcements = [
    [
        'id'       => 3,
        'priority' => 'urgent',
        'tag'      => 'Deadline',
        'title'    => 'Final Report Submission Deadline — May 15',
        'content'  => 'All interns must submit their final narrative report and employer evaluation by May 15, 2025. Late submissions will not be accepted. Please coordinate with your faculty supervisor for the submission checklist.',
        'author'   => 'Coord. Office',
        'initials' => 'CO',
        'date'     => 'Mar 24, 2025',
    ],
    [
        'id'       => 2,
        'priority' => 'normal',
        'tag'      => 'Reminder',
        'title'    => 'Mid-Term Evaluation Forms Now Available',
        'content'  => 'The mid-term evaluation forms have been distributed to all employer supervisors. Please remind your direct supervisor to complete the evaluation form and return it to the department office by March 31.',
        'author'   => 'Coord. Office',
        'initials' => 'CO',
        'date'     => 'Mar 20, 2025',
    ],
    [
        'id'       => 1,
        'priority' => 'info',
        'tag'      => 'Info',
        'title'    => 'Weekly Journal Submission Guidelines Updated',
        'content'  => 'A revised weekly journal template is now available on the portal. Starting Week 8, please use the new template. The updated format includes a self-assessment section and a photo documentation field.',
        'author'   => 'Coord. Office',
        'initials' => 'CO',
        'date'     => 'Mar 15, 2025',
    ],
];

// Recent time logs
$timeLogs = [
    ['date'=>'Mon, Mar 24', 'in'=>'8:02 AM', 'out'=>'5:04 PM', 'hours'=>'9h 02m'],
    ['date'=>'Fri, Mar 21', 'in'=>'8:00 AM', 'out'=>'5:00 PM', 'hours'=>'9h 00m'],
    ['date'=>'Thu, Mar 20', 'in'=>'7:58 AM', 'out'=>'5:10 PM', 'hours'=>'9h 12m'],
    ['date'=>'Wed, Mar 19', 'in'=>'8:05 AM', 'out'=>'5:00 PM', 'hours'=>'8h 55m'],
];

$pct     = round(($intern['rendered_hours'] / $intern['required_hours']) * 100);
$initials = initials($intern['name']);
$isCoordinator = in_array($user['role'], ['coordinator', 'admin']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>CITAS – <?= e($pageTitle) ?></title>
  <link href="https://fonts.googleapis.com/css2?family=EB+Garamond:ital,wght@0,400;0,600;0,700;1,400&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="../assets/css/style.css"/>
  <link rel="stylesheet" href="../assets/css/intern.css"/>
</head>
<body>

<?php require_once '../includes/sidebar.php'; ?>

<!-- Topbar -->
<header class="topbar">
  <div class="topbar-title"><?= e($pageTitle) ?></div>
  <div class="topbar-actions">
    <button class="notif-btn">🔔<span class="notif-dot"></span></button>
    <?php if ($isCoordinator): ?>
      <button class="btn btn-primary btn-sm" onclick="openModal('modal-post-announcement')">📢 Post Announcement</button>
    <?php endif; ?>
    <button class="btn btn-secondary btn-sm" onclick="openModal('modal-edit-profile')">✏️ Edit Profile</button>
  </div>
</header>

<main class="main">

  <!-- ══ HERO HEADER ══════════════════════════════ -->
  <div class="intern-hero">
    <div class="intern-avatar-lg" onclick="openModal('modal-edit-profile')" title="Edit profile">
      <?= e($initials) ?>
      <div class="intern-avatar-edit">✏️</div>
    </div>

    <div class="intern-hero-info">
      <h2><?= e($intern['name']) ?></h2>
      <div class="intern-hero-meta">
        <span>🎓 <?= e($intern['program']) ?> — <?= e($intern['year_level']) ?><?= ['','st','nd','rd'][$intern['year_level']] ?? 'th' ?> Year, Sec <?= e($intern['section']) ?></span>
        <span>·</span>
        <span>🪪 <?= e($intern['student_number']) ?></span>
        <span>·</span>
        <span>📅 A.Y. <?= e($intern['academic_year']) ?>, <?= e($intern['semester']) ?> Sem</span>
      </div>
      <div class="intern-hero-stats">
        <div class="hero-stat">
          <div class="hs-val"><?= $intern['rendered_hours'] ?><span style="font-size:13px;color:rgba(255,255,255,0.35)"> / <?= $intern['required_hours'] ?></span></div>
          <div class="hs-lbl">Hours Rendered</div>
        </div>
        <div class="hero-stat">
          <div class="hs-val"><?= $pct ?>%</div>
          <div class="hs-lbl">Completion</div>
        </div>
        <div class="hero-stat">
          <div class="hs-val"><?= $intern['required_hours'] - $intern['rendered_hours'] ?></div>
          <div class="hs-lbl">Hours Remaining</div>
        </div>
        <div class="hero-stat">
          <div class="hs-val"><?= count(array_filter($reports, fn($r) => $r['status'] === 'approved')) ?></div>
          <div class="hs-lbl">Reports Approved</div>
        </div>
      </div>
    </div>

    <div class="intern-hero-actions">
      <span class="badge badge-<?= e($intern['status']) ?>" style="font-size:12px;padding:5px 14px;">
        <?= $intern['status'] === 'active' ? '● Active' : ucfirst($intern['status']) ?>
      </span>
      <button class="btn btn-gold btn-sm" onclick="openModal('modal-upload-report')">⬆ Upload Report</button>
    </div>
  </div>

  <!-- ══ MAIN GRID ════════════════════════════════ -->
  <div style="display:grid;grid-template-columns:1fr 340px;gap:20px;align-items:start;">

    <!-- Left column -->
    <div>

      <!-- ── TABS ─────────────────────────────────── -->
      <div class="tabs">
        <button class="tab-btn active" onclick="switchTab('overview',   this)">Overview</button>
        <button class="tab-btn"        onclick="switchTab('reports',    this)">Weekly Reports</button>
        <button class="tab-btn"        onclick="switchTab('dtr',        this)">DTR</button>
        <button class="tab-btn"        onclick="switchTab('company',    this)">Company Info</button>
        <button class="tab-btn"        onclick="switchTab('announcements', this)">Announcements
          <span class="nav-badge" style="margin-left:6px;display:inline-block"><?= count($announcements) ?></span>
        </button>
      </div>

      <!-- ── OVERVIEW TAB ────────────────────────── -->
      <div class="tab-pane active" id="tab-overview">

        <!-- Hours progress -->
        <div class="card mb-4" style="margin-bottom:18px;">
          <div class="card-header">
            <div>
              <div class="card-title">Hours Progress</div>
              <div class="card-subtitle">A.Y. <?= e($intern['academic_year']) ?>, <?= e($intern['semester']) ?> Semester</div>
            </div>
            <span class="badge badge-<?= $pct >= 100 ? 'complete' : ($pct >= 50 ? 'active' : 'pending') ?>"><?= $pct ?>% Complete</span>
          </div>
          <div class="hours-overview">
            <div class="hours-big"><?= $intern['rendered_hours'] ?><sub> / <?= $intern['required_hours'] ?> hrs</sub></div>
            <div style="flex:1;">
              <div class="progress-wrap" style="height:12px;">
                <div class="progress-fill" style="width:<?= $pct ?>%"></div>
              </div>
              <div style="display:flex;justify-content:space-between;margin-top:6px;">
                <span class="text-sm text-muted"><?= $intern['required_hours'] - $intern['rendered_hours'] ?> hours remaining</span>
                <span class="text-sm text-muted">Required: <?= $intern['required_hours'] ?> hrs</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Supervisor & quick submissions -->
        <div class="grid-2" style="margin-bottom:18px;">
          <div class="card">
            <div class="card-header" style="margin-bottom:14px;">
              <div class="card-title">Faculty Supervisor</div>
            </div>
            <div class="supervisor-card">
              <div class="sup-avatar"><?= e(initials($intern['supervisor'])) ?></div>
              <div class="sup-info">
                <strong><?= e($intern['supervisor']) ?></strong>
                <span><?= e($intern['supervisor_email']) ?></span>
              </div>
            </div>
          </div>
          <div class="card">
            <div class="card-header" style="margin-bottom:14px;">
              <div class="card-title">Submission Summary</div>
            </div>
            <?php
              $counts = array_count_values(array_column($reports, 'status'));
            ?>
            <div style="display:flex;flex-direction:column;gap:8px;">
              <div style="display:flex;justify-content:space-between;align-items:center;font-size:13px;">
                <span class="text-muted">Approved</span>
                <span class="badge badge-active"><?= $counts['approved'] ?? 0 ?></span>
              </div>
              <div style="display:flex;justify-content:space-between;align-items:center;font-size:13px;">
                <span class="text-muted">Pending Review</span>
                <span class="badge badge-pending"><?= $counts['pending'] ?? 0 ?></span>
              </div>
              <div style="display:flex;justify-content:space-between;align-items:center;font-size:13px;">
                <span class="text-muted">Needs Revision</span>
                <span class="badge badge-rejected"><?= $counts['rejected'] ?? 0 ?></span>
              </div>
              <div style="display:flex;justify-content:space-between;align-items:center;font-size:13px;">
                <span class="text-muted">Total Reports</span>
                <span style="font-weight:600;color:var(--navy)"><?= count($reports) ?></span>
              </div>
            </div>
          </div>
        </div>

        <!-- Latest report status -->
        <div class="card">
          <div class="card-header">
            <div class="card-title">Recent Submissions</div>
            <button class="btn btn-secondary btn-sm" onclick="switchTab('reports', document.querySelectorAll('.tab-btn')[1])">View All</button>
          </div>
          <?php foreach (array_slice($reports, 0, 3) as $r): ?>
          <div class="report-item">
            <div class="report-icon <?= e($r['type']) ?>">
              <?= $r['type'] === 'pdf' ? '📄' : '📝' ?>
            </div>
            <div class="report-info">
              <div class="report-name"><?= e($r['title']) ?></div>
              <div class="report-meta">Submitted <?= e($r['submitted']) ?><?= $r['remarks'] ? ' · ' . e($r['remarks']) : '' ?></div>
            </div>
            <span class="badge badge-<?= e($r['status']) ?>"><?= ucfirst($r['status']) ?></span>
          </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- ── WEEKLY REPORTS TAB ──────────────────── -->
      <div class="tab-pane" id="tab-reports">
        <div class="card">
          <div class="card-header">
            <div>
              <div class="card-title">Weekly Journal Reports</div>
              <div class="card-subtitle">Submit one journal per week throughout the internship period.</div>
            </div>
            <button class="btn btn-gold btn-sm" onclick="openModal('modal-upload-report')">⬆ Upload New</button>
          </div>

          <?php if (empty($reports)): ?>
          <div style="text-align:center;padding:32px;color:var(--muted);">
            <div style="font-size:32px;margin-bottom:8px;">📭</div>
            <p>No reports submitted yet.</p>
          </div>
          <?php else: ?>
          <?php foreach ($reports as $r): ?>
          <div class="report-item">
            <div class="report-icon <?= e($r['type']) ?>">
              <?= $r['type'] === 'pdf' ? '📄' : '📝' ?>
            </div>
            <div class="report-info">
              <div class="report-name"><?= e($r['title']) ?></div>
              <div class="report-meta">
                <?= e($r['file']) ?> &nbsp;·&nbsp; Submitted <?= e($r['submitted']) ?>
                <?php if ($r['remarks']): ?>
                  <br/><span style="color:var(--red);">⚠ <?= e($r['remarks']) ?></span>
                <?php endif; ?>
              </div>
            </div>
            <div class="report-actions">
              <span class="badge badge-<?= e($r['status']) ?>"><?= ucfirst($r['status']) ?></span>
              <button class="btn btn-secondary btn-sm btn-icon" title="Download">⬇</button>
              <?php if ($r['status'] === 'rejected'): ?>
                <button class="btn btn-gold btn-sm" onclick="openModal('modal-upload-report')">Resubmit</button>
              <?php endif; ?>
            </div>
          </div>
          <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>

      <!-- ── DTR TAB ─────────────────────────────── -->
      <div class="tab-pane" id="tab-dtr">
        <div class="card">
          <div class="card-header">
            <div>
              <div class="card-title">Daily Time Record (DTR)</div>
              <div class="card-subtitle">Monthly DTR export generated from your time logs.</div>
            </div>
            <button class="btn btn-gold btn-sm" onclick="openModal('modal-upload-dtr')">⬆ Upload DTR</button>
          </div>
          <div class="table-wrap">
            <table>
              <thead><tr><th>Period</th><th>Days Rendered</th><th>Total Hours</th><th>Status</th><th>Action</th></tr></thead>
              <tbody>
                <?php foreach ($dtr as $d): ?>
                <tr>
                  <td><strong><?= e($d['month']) ?></strong></td>
                  <td><?= $d['days'] ?> days</td>
                  <td>
                    <strong style="font-family:'EB Garamond',serif;font-size:15px;"><?= $d['hours'] ?></strong> hrs
                  </td>
                  <td><span class="badge badge-<?= e($d['status']) ?>"><?= ucfirst($d['status']) ?></span></td>
                  <td>
                    <button class="btn btn-secondary btn-sm">⬇ Download</button>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <div class="divider"></div>
          <div style="font-size:12.5px;color:var(--muted);">
            💡 Your DTR is auto-generated from your time logs at the end of each month. You can also manually upload a signed DTR for coordinator approval.
          </div>
        </div>
      </div>

      <!-- ── COMPANY INFO TAB ────────────────────── -->
      <div class="tab-pane" id="tab-company">
        <?php if ($company): ?>
        <div class="company-info-card" style="margin-bottom:18px;">
          <div class="company-info-banner">
            <div class="company-logo-placeholder">🏢</div>
            <div class="company-banner-info">
              <h3><?= e($company['name']) ?></h3>
              <span><?= e($company['industry']) ?></span>
            </div>
            <button class="btn btn-secondary btn-sm" style="margin-left:auto;" onclick="openModal('modal-edit-company')">✏️ Edit</button>
          </div>
          <div class="company-info-body">
            <div class="company-detail-row">
              <span class="company-detail-label">📍 Address</span>
              <span class="company-detail-value"><?= e($company['address']) ?></span>
            </div>
            <div class="company-detail-row">
              <span class="company-detail-label">💼 Job Title</span>
              <span class="company-detail-value"><?= e($company['job_title']) ?></span>
            </div>
            <div class="company-detail-row">
              <span class="company-detail-label">📅 Start Date</span>
              <span class="company-detail-value"><?= e($company['start_date']) ?></span>
            </div>
            <div class="company-detail-row">
              <span class="company-detail-label">📅 End Date</span>
              <span class="company-detail-value"><?= e($company['end_date']) ?></span>
            </div>
          </div>
        </div>

        <!-- Employer Supervisor -->
        <div class="card">
          <div class="card-header" style="margin-bottom:14px;">
            <div class="card-title">Employer / Company Supervisor</div>
            <button class="btn btn-secondary btn-sm" onclick="openModal('modal-edit-company')">✏️ Edit</button>
          </div>
          <div class="supervisor-card">
            <div class="sup-avatar" style="background:var(--gold);color:var(--navy);">
              <?= e(initials($company['supervisor_name'])) ?>
            </div>
            <div class="sup-info">
              <strong><?= e($company['supervisor_name']) ?></strong>
              <span><?= e($company['supervisor_title']) ?></span>
              <span style="display:block;margin-top:2px;"><?= e($company['supervisor_email']) ?></span>
              <span><?= e($company['supervisor_contact']) ?></span>
            </div>
          </div>
        </div>

        <?php else: ?>
        <div class="company-not-set">
          <div class="icon">🏢</div>
          <p>You haven't set your internship company yet.<br/>Please fill in your company details to get started.</p>
          <button class="btn btn-primary" onclick="openModal('modal-edit-company')">+ Set Company Info</button>
        </div>
        <?php endif; ?>
      </div>

      <!-- ── ANNOUNCEMENTS TAB ───────────────────── -->
      <div class="tab-pane" id="tab-announcements">

        <?php if ($isCoordinator): ?>
        <div class="post-form-wrap" style="margin-bottom:20px;">
          <h4>📢 Post an Announcement</h4>
          <div class="form-group">
            <label>Title</label>
            <input type="text" class="form-control" placeholder="Announcement title…"/>
          </div>
          <div class="form-row" style="margin-bottom:16px;">
            <div class="form-group" style="margin:0;">
              <label>Priority</label>
              <select class="form-control">
                <option value="normal">Normal</option>
                <option value="urgent">Urgent</option>
                <option value="info">Info</option>
              </select>
            </div>
            <div class="form-group" style="margin:0;">
              <label>Tag</label>
              <input type="text" class="form-control" placeholder="e.g. Deadline, Reminder…"/>
            </div>
          </div>
          <div class="form-group">
            <label>Content</label>
            <textarea class="form-control" rows="3" placeholder="Write announcement content…"></textarea>
          </div>
          <div style="display:flex;justify-content:flex-end;">
            <button class="btn btn-primary">Post Announcement</button>
          </div>
        </div>
        <?php endif; ?>

        <div style="display:flex;flex-direction:column;gap:14px;">
          <?php foreach ($announcements as $ann): ?>
          <div class="announcement-card">
            <div class="announcement-priority <?= e($ann['priority']) ?>"></div>
            <div class="announcement-body">
              <span class="announcement-tag <?= e($ann['priority']) ?>"><?= e($ann['tag']) ?></span>
              <div class="announcement-title"><?= e($ann['title']) ?></div>
              <div class="announcement-content"><?= e($ann['content']) ?></div>
              <div class="announcement-meta">
                <div class="announcement-author">
                  <div class="announcement-author-dot"><?= e($ann['initials']) ?></div>
                  <span><?= e($ann['author']) ?></span>
                </div>
                <span><?= e($ann['date']) ?></span>
                <?php if ($isCoordinator): ?>
                  <button class="btn btn-secondary btn-sm" style="font-size:11px;padding:4px 10px;">Edit</button>
                <?php endif; ?>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>

    </div><!-- /left column -->

    <!-- Right column: time widget -->
    <div>
      <div class="time-widget">
        <div class="time-widget-header">
          <div class="tw-title">⏱ Time Tracker</div>
          <div class="tw-clock" id="live-clock">--:--:-- --</div>
        </div>
        <div class="time-widget-body">

          <div id="time-status" class="time-status-bar clocked-out">
            <div class="time-status-dot"></div>
            <span id="time-status-text">Not clocked in today</span>
          </div>

          <div style="font-size:12px;color:var(--muted);margin-bottom:10px;text-align:center;" id="today-label">
            <?= date('l, F j, Y') ?>
          </div>

          <div style="display:grid;grid-template-columns:1fr 1fr;gap:6px;margin-bottom:14px;font-size:12.5px;">
            <div style="background:var(--cream);border-radius:6px;padding:8px 10px;">
              <div style="color:var(--muted);font-size:11px;">Time In</div>
              <div id="display-time-in" style="font-weight:600;color:var(--green);">—</div>
            </div>
            <div style="background:var(--cream);border-radius:6px;padding:8px 10px;">
              <div style="color:var(--muted);font-size:11px;">Time Out</div>
              <div id="display-time-out" style="font-weight:600;color:var(--red);">—</div>
            </div>
          </div>

          <div class="time-action-row">
            <button class="time-btn time-btn-in"  id="btn-time-in"  onclick="doTimeIn()">▶ Time In</button>
            <button class="time-btn time-btn-out" id="btn-time-out" onclick="doTimeOut()" disabled>■ Time Out</button>
          </div>

          <div class="time-log-title">Recent Time Logs</div>
          <ul class="time-log-list">
            <?php foreach ($timeLogs as $tl): ?>
            <li class="time-log-item">
              <div>
                <div style="font-weight:600;font-size:12.5px;"><?= e($tl['date']) ?></div>
                <div>
                  <span class="time-log-in">IN <?= e($tl['in']) ?></span>
                  &nbsp;→&nbsp;
                  <span class="time-log-out">OUT <?= e($tl['out']) ?></span>
                </div>
              </div>
              <div class="tl-hours"><?= e($tl['hours']) ?></div>
            </li>
            <?php endforeach; ?>
          </ul>

        </div>
      </div>

      <!-- Quick profile card -->
      <div class="card" style="margin-top:18px;">
        <div class="card-header" style="margin-bottom:14px;">
          <div class="card-title">My Profile</div>
          <button class="btn btn-secondary btn-sm" onclick="openModal('modal-edit-profile')">✏️ Edit</button>
        </div>
        <div style="display:flex;flex-direction:column;gap:8px;font-size:13px;">
          <div class="company-detail-row" style="padding:7px 0;">
            <span class="company-detail-label text-muted">Email</span>
            <span class="company-detail-value"><?= e($intern['email']) ?></span>
          </div>
          <div class="company-detail-row" style="padding:7px 0;">
            <span class="company-detail-label text-muted">Contact</span>
            <span class="company-detail-value"><?= e($intern['contact']) ?></span>
          </div>
          <div class="company-detail-row" style="padding:7px 0;">
            <span class="company-detail-label text-muted">Program</span>
            <span class="company-detail-value"><?= e($intern['program']) ?></span>
          </div>
          <div class="company-detail-row" style="padding:7px 0;">
            <span class="company-detail-label text-muted">Section</span>
            <span class="company-detail-value"><?= $intern['year_level'] . e($intern['section']) ?></span>
          </div>
          <div class="company-detail-row" style="padding:7px 0;border:none;">
            <span class="company-detail-label text-muted">Supervisor</span>
            <span class="company-detail-value"><?= e($intern['supervisor']) ?></span>
          </div>
        </div>
      </div>

    </div><!-- /right column -->
  </div><!-- /grid -->
</main>


<!-- ════════════════════════════════════════════
     MODALS
     ════════════════════════════════════════════ -->

<!-- ── Edit Profile ── -->
<div class="modal-overlay" id="modal-edit-profile">
  <div class="modal">
    <div class="modal-header">
      <h3>Edit Profile</h3>
      <button class="modal-close" onclick="closeModal('modal-edit-profile')">✕</button>
    </div>
    <div class="modal-body">
      <div class="form-row">
        <div class="form-group">
          <label>First Name</label>
          <input type="text" class="form-control" value="<?= e(explode(' ', $intern['name'])[0]) ?>"/>
        </div>
        <div class="form-group">
          <label>Last Name</label>
          <input type="text" class="form-control" value="<?= e(explode(' ', $intern['name'])[1] ?? '') ?>"/>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Student Number</label>
          <input type="text" class="form-control" value="<?= e($intern['student_number']) ?>" readonly/>
        </div>
        <div class="form-group">
          <label>Program</label>
          <select class="form-control">
            <?php foreach (['BSIT','BSCS','BSISE','BSDA','BSIS'] as $prog): ?>
            <option value="<?= $prog ?>" <?= $prog === $intern['program'] ? 'selected' : '' ?>><?= $prog ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="form-row three">
        <div class="form-group">
          <label>Year Level</label>
          <select class="form-control">
            <?php for ($y=1; $y<=4; $y++): ?>
            <option value="<?= $y ?>" <?= $y == $intern['year_level'] ? 'selected' : '' ?>><?= $y ?></option>
            <?php endfor; ?>
          </select>
        </div>
        <div class="form-group">
          <label>Section</label>
          <input type="text" class="form-control" value="<?= e($intern['section']) ?>" maxlength="5"/>
        </div>
        <div class="form-group">
          <label>Semester</label>
          <select class="form-control">
            <option <?= $intern['semester']==='1st'    ? 'selected':'' ?>>1st</option>
            <option <?= $intern['semester']==='2nd'    ? 'selected':'' ?>>2nd</option>
            <option <?= $intern['semester']==='Summer' ? 'selected':'' ?>>Summer</option>
          </select>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Email Address</label>
          <input type="email" class="form-control" value="<?= e($intern['email']) ?>"/>
        </div>
        <div class="form-group">
          <label>Contact Number</label>
          <input type="text" class="form-control" value="<?= e($intern['contact']) ?>"/>
        </div>
      </div>
      <div class="form-group">
        <label>Change Password <span style="font-weight:400;text-transform:none;letter-spacing:0;color:var(--muted)">(leave blank to keep current)</span></label>
        <input type="password" class="form-control" placeholder="New password"/>
      </div>
      <div class="form-group">
        <label>Confirm Password</label>
        <input type="password" class="form-control" placeholder="Repeat new password"/>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" onclick="closeModal('modal-edit-profile')">Cancel</button>
      <button class="btn btn-primary">Save Changes</button>
    </div>
  </div>
</div>

<!-- ── Edit Company Info ── -->
<div class="modal-overlay" id="modal-edit-company">
  <div class="modal modal-lg">
    <div class="modal-header">
      <h3>Company & Internship Info</h3>
      <button class="modal-close" onclick="closeModal('modal-edit-company')">✕</button>
    </div>
    <div class="modal-body">
      <p style="font-size:13px;color:var(--muted);margin-bottom:18px;">
        Fill in your internship placement details. This will be reviewed by your coordinator.
      </p>

      <div style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:0.1em;color:var(--navy);margin-bottom:12px;padding-bottom:8px;border-bottom:1px solid var(--border);">
        Company Details
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Company Name</label>
          <input type="text" class="form-control" value="<?= e($company['name'] ?? '') ?>" placeholder="e.g. Accenture Philippines"/>
        </div>
        <div class="form-group">
          <label>Industry</label>
          <input type="text" class="form-control" value="<?= e($company['industry'] ?? '') ?>" placeholder="e.g. IT Services"/>
        </div>
      </div>
      <div class="form-group">
        <label>Complete Address</label>
        <input type="text" class="form-control" value="<?= e($company['address'] ?? '') ?>" placeholder="Street, Building, City"/>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Job Title / Position</label>
          <input type="text" class="form-control" value="<?= e($company['job_title'] ?? '') ?>" placeholder="e.g. Software Development Intern"/>
        </div>
        <div class="form-group">
          <label>City</label>
          <input type="text" class="form-control" value="<?= e($company['city'] ?? '') ?>" placeholder="e.g. Makati"/>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Start Date</label>
          <input type="date" class="form-control" value="<?= e($company['start_date'] ?? '') ?>"/>
        </div>
        <div class="form-group">
          <label>Expected End Date</label>
          <input type="date" class="form-control" value="<?= e($company['end_date'] ?? '') ?>"/>
        </div>
      </div>

      <div style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:0.1em;color:var(--navy);margin:20px 0 12px;padding-bottom:8px;border-bottom:1px solid var(--border);">
        Employer / Company Supervisor
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Supervisor Full Name</label>
          <input type="text" class="form-control" value="<?= e($company['supervisor_name'] ?? '') ?>" placeholder="Full name"/>
        </div>
        <div class="form-group">
          <label>Job Title</label>
          <input type="text" class="form-control" value="<?= e($company['supervisor_title'] ?? '') ?>" placeholder="e.g. Senior Engineer"/>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Email Address</label>
          <input type="email" class="form-control" value="<?= e($company['supervisor_email'] ?? '') ?>" placeholder="supervisor@company.com"/>
        </div>
        <div class="form-group">
          <label>Contact Number</label>
          <input type="text" class="form-control" value="<?= e($company['supervisor_contact'] ?? '') ?>" placeholder="+63 9XX XXX XXXX"/>
        </div>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" onclick="closeModal('modal-edit-company')">Cancel</button>
      <button class="btn btn-primary">Save Company Info</button>
    </div>
  </div>
</div>

<!-- ── Upload Weekly Report ── -->
<div class="modal-overlay" id="modal-upload-report">
  <div class="modal">
    <div class="modal-header">
      <h3>Upload Weekly Journal</h3>
      <button class="modal-close" onclick="closeModal('modal-upload-report')">✕</button>
    </div>
    <div class="modal-body">
      <div class="form-row">
        <div class="form-group">
          <label>Week Number</label>
          <select class="form-control" id="report-week">
            <?php for ($w = 1; $w <= 16; $w++): ?>
            <option value="<?= $w ?>" <?= $w === 9 ? 'selected' : '' ?>>Week <?= $w ?></option>
            <?php endfor; ?>
          </select>
        </div>
        <div class="form-group">
          <label>Report Type</label>
          <select class="form-control">
            <option value="journal">Weekly Journal</option>
            <option value="narrative">Narrative Report</option>
            <option value="reflection">Reflection Paper</option>
          </select>
        </div>
      </div>
      <div class="form-group">
        <label>Report Title</label>
        <input type="text" class="form-control" id="report-title" placeholder="e.g. Week 9 Journal — March 24–28"/>
      </div>
      <div class="form-group">
        <label>File</label>
        <div class="upload-zone" id="upload-zone-report" onclick="document.getElementById('file-report').click()">
          <input type="file" id="file-report" accept=".pdf,.doc,.docx" onchange="handleFileSelect(this,'upload-zone-report','file-report-label')"/>
          <div class="upload-zone-icon">📁</div>
          <div class="upload-zone-text" id="file-report-label">
            <strong>Click to upload</strong> or drag and drop<br/>
            <span>PDF, DOC, DOCX — max 10 MB</span>
          </div>
        </div>
      </div>
      <div class="form-group">
        <label>Remarks / Notes <span style="font-weight:400;text-transform:none;letter-spacing:0;color:var(--muted)">(optional)</span></label>
        <textarea class="form-control" rows="2" placeholder="Any notes for your supervisor…"></textarea>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" onclick="closeModal('modal-upload-report')">Cancel</button>
      <button class="btn btn-gold">⬆ Submit Report</button>
    </div>
  </div>
</div>

<!-- ── Upload DTR ── -->
<div class="modal-overlay" id="modal-upload-dtr">
  <div class="modal">
    <div class="modal-header">
      <h3>Upload DTR</h3>
      <button class="modal-close" onclick="closeModal('modal-upload-dtr')">✕</button>
    </div>
    <div class="modal-body">
      <div class="alert alert-info">ℹ Your DTR is auto-generated from time logs. Upload a signed paper DTR if required by your coordinator.</div>
      <div class="form-row">
        <div class="form-group">
          <label>Month / Period</label>
          <select class="form-control">
            <option>April 2025</option>
            <option>March 2025</option>
            <option>February 2025</option>
          </select>
        </div>
        <div class="form-group">
          <label>Total Hours</label>
          <input type="number" class="form-control" placeholder="e.g. 160"/>
        </div>
      </div>
      <div class="form-group">
        <label>Signed DTR File</label>
        <div class="upload-zone" onclick="document.getElementById('file-dtr').click()">
          <input type="file" id="file-dtr" accept=".pdf,.jpg,.png"/>
          <div class="upload-zone-icon">📋</div>
          <div class="upload-zone-text"><strong>Click to upload</strong> or drag and drop<br/><span>PDF, JPG, PNG</span></div>
        </div>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" onclick="closeModal('modal-upload-dtr')">Cancel</button>
      <button class="btn btn-primary">Submit DTR</button>
    </div>
  </div>
</div>

<!-- ── Post Announcement (Coordinator only) ── -->
<div class="modal-overlay" id="modal-post-announcement">
  <div class="modal">
    <div class="modal-header">
      <h3>Post Announcement</h3>
      <button class="modal-close" onclick="closeModal('modal-post-announcement')">✕</button>
    </div>
    <div class="modal-body">
      <div class="form-group">
        <label>Title</label>
        <input type="text" class="form-control" placeholder="Announcement title…"/>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Priority</label>
          <select class="form-control">
            <option value="normal">📌 Normal</option>
            <option value="urgent">🔴 Urgent</option>
            <option value="info">🔵 Info</option>
          </select>
        </div>
        <div class="form-group">
          <label>Tag / Category</label>
          <input type="text" class="form-control" placeholder="e.g. Deadline, Reminder…"/>
        </div>
      </div>
      <div class="form-group">
        <label>Content</label>
        <textarea class="form-control" rows="5" placeholder="Write the full announcement here…"></textarea>
      </div>
      <div class="form-group">
        <label>Visible To</label>
        <select class="form-control">
          <option>All Interns</option>
          <option>BSIT Only</option>
          <option>BSCS Only</option>
          <option>Specific Students</option>
        </select>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" onclick="closeModal('modal-post-announcement')">Cancel</button>
      <button class="btn btn-primary">📢 Post Now</button>
    </div>
  </div>
</div>


<!-- ════════════════════════════════════════════
     SCRIPTS
     ════════════════════════════════════════════ -->
<script src="../assets/js/charts.js"></script>
<script>
// ── Tabs ──────────────────────────────────────
function switchTab(name, btn) {
  document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));
  document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
  document.getElementById('tab-' + name).classList.add('active');
  if (btn) btn.classList.add('active');
}

// ── Modals ────────────────────────────────────
function openModal(id)  { document.getElementById(id).classList.add('open'); document.body.style.overflow = 'hidden'; }
function closeModal(id) { document.getElementById(id).classList.remove('open'); document.body.style.overflow = ''; }
document.querySelectorAll('.modal-overlay').forEach(el => {
  el.addEventListener('click', e => { if (e.target === el) closeModal(el.id); });
});
document.addEventListener('keydown', e => {
  if (e.key === 'Escape') document.querySelectorAll('.modal-overlay.open').forEach(m => closeModal(m.id));
});

// ── Live clock ────────────────────────────────
function updateClock() {
  const now = new Date();
  document.getElementById('live-clock').textContent =
    now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
}
updateClock();
setInterval(updateClock, 1000);

// ── Time In / Out ─────────────────────────────
let clockedIn = false;
let timeInValue = null;

function formatTime(d) {
  return d.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true });
}

function doTimeIn() {
  if (clockedIn) return;
  clockedIn = true;
  timeInValue = new Date();
  document.getElementById('display-time-in').textContent = formatTime(timeInValue);
  document.getElementById('display-time-out').textContent = '—';

  const bar = document.getElementById('time-status');
  bar.className = 'time-status-bar clocked-in';
  document.getElementById('time-status-text').textContent = 'Clocked in at ' + formatTime(timeInValue);

  document.getElementById('btn-time-in').disabled  = true;
  document.getElementById('btn-time-out').disabled = false;
  // TODO: POST to api/timelog.php { action: 'in', timestamp: timeInValue.toISOString() }
}

function doTimeOut() {
  if (!clockedIn) return;
  clockedIn = false;
  const timeOut = new Date();
  document.getElementById('display-time-out').textContent = formatTime(timeOut);

  const diffMs   = timeOut - timeInValue;
  const diffHrs  = Math.floor(diffMs / 3600000);
  const diffMins = Math.floor((diffMs % 3600000) / 60000);

  const bar = document.getElementById('time-status');
  bar.className = 'time-status-bar clocked-out';
  document.getElementById('time-status-text').textContent =
    `Clocked out · ${diffHrs}h ${diffMins}m rendered`;

  document.getElementById('btn-time-in').disabled  = true;  // only one session per day
  document.getElementById('btn-time-out').disabled = true;
  // TODO: POST to api/timelog.php { action: 'out', timestamp: timeOut.toISOString() }
}

// ── Upload zone drag/drop ─────────────────────
function handleFileSelect(input, zoneId, labelId) {
  const file  = input.files[0];
  const label = document.getElementById(labelId);
  if (file) label.innerHTML = `<strong>${file.name}</strong><br/><span>${(file.size/1024/1024).toFixed(2)} MB</span>`;
}
['upload-zone-report'].forEach(id => {
  const zone = document.getElementById(id);
  if (!zone) return;
  zone.addEventListener('dragover',  e => { e.preventDefault(); zone.classList.add('dragover'); });
  zone.addEventListener('dragleave', () => zone.classList.remove('dragover'));
  zone.addEventListener('drop', e => {
    e.preventDefault(); zone.classList.remove('dragover');
    const file = e.dataTransfer.files[0];
    if (file) {
      document.getElementById('file-report-label').innerHTML =
        `<strong>${file.name}</strong><br/><span>${(file.size/1024/1024).toFixed(2)} MB</span>`;
    }
  });
});
</script>

</body>
</html>
