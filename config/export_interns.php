<?php
// ============================================================
//  export_interns.php
//  Generates a downloadable CSV of all intern records.
//  Replaces manual Excel encoding by the coordinator.
//
//  GET  export_interns.php              → all active interns
//  GET  export_interns.php?dept=BSIT   → filter by department
//  GET  export_interns.php?status=all  → include pending interns
// ============================================================

ini_set('display_errors', 0);
ini_set('log_errors',     1);
ini_set('error_log',      __DIR__ . '/error_log.txt');
error_reporting(E_ALL);

session_start();
require_once __DIR__ . '/db.php';

// ── Auth: coordinators and admins only ────────────────────────
if (empty($_SESSION['user']['id'])) {
    http_response_code(403);
    die('Not logged in.');
}
if (!in_array($_SESSION['user']['role'], ['coordinator', 'admin'], true)) {
    http_response_code(403);
    die('Access denied.');
}


// ── Optional filters from query string ───────────────────────
$deptFilter   = trim($_GET['dept']   ?? '');
$statusFilter = trim($_GET['status'] ?? 'active');  // 'active' | 'all' | 'pending'


// ── Build query ───────────────────────────────────────────────
$pdo    = getDB();
$params = [];
$where  = [];

// Role filter — always interns only
$where[] = "u.role = 'intern'";

// Active status filter
if ($statusFilter === 'pending') {
    $where[] = "u.is_active = 0";
} elseif ($statusFilter === 'active') {
    $where[] = "u.is_active = 1";
}
// 'all' → no filter on is_active

// Department filter
if ($deptFilter !== '') {
    $where[]  = "ip.course = ?";
    $params[] = $deptFilter;
}

$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$sql = "
    SELECT
        u.id,
        u.name                      AS intern_name,
        u.email,
        u.is_active,
        u.created_at                AS registered_on,

        ip.school,
        ip.course,
        ip.year_level,
        ip.phone,
        ip.required_hours,
        ip.joined_date,

        c.name                      AS company_name,
        c.address                   AS company_address,
        c.phone                     AS company_phone,
        c.email                     AS company_email,

        i.position,
        i.supervisor,
        i.supervisor_phone,
        i.start_date,
        i.end_date,
        i.status                    AS internship_status,
        i.total_hours,
        i.days_present,
        i.reports_submitted

    FROM users u

    LEFT JOIN intern_profiles ip
        ON ip.user_id = u.id

    LEFT JOIN internships i
        ON i.intern_id = u.id
        -- get the most recent internship row per intern
        AND i.id = (
            SELECT id FROM internships
            WHERE intern_id = u.id
            ORDER BY created_at DESC
            LIMIT 1
        )

    LEFT JOIN companies c
        ON c.id = i.company_id

    $whereSQL

    ORDER BY u.name ASC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);


// ── Build filename ─────────────────────────────────────────────
$date     = date('Y-m-d');
$suffix   = $deptFilter ? '_' . preg_replace('/[^a-z0-9]/i', '', $deptFilter) : '';
$filename = "CITAS_Interns_{$date}{$suffix}.csv";


// ── Stream CSV headers to browser ─────────────────────────────
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: no-cache, no-store');
header('Pragma: no-cache');

// UTF-8 BOM — makes Excel open the file with correct encoding
echo "\xEF\xBB\xBF";

$out = fopen('php://output', 'w');


// ── Column headers ─────────────────────────────────────────────
fputcsv($out, [
    // Personal
    'ID',
    'Full Name',
    'Email',
    'Phone',
    'School',
    'Course / Dept',
    'Year Level',
    'Account Status',
    'Registered On',

    // Internship dates
    'Joined Date',
    'Start Date',
    'End Date',

    // Company
    'Company Name',
    'Company Address',
    'Company Phone',
    'Company Email',

    // Position
    'Position',
    'Supervisor',
    'Supervisor Phone',
    'Internship Status',

    // Progress
    'Hours Rendered',
    'Required Hours',
    'Completion %',
    'Days Present',
    'Reports Submitted',
]);


// ── Data rows ──────────────────────────────────────────────────
foreach ($rows as $r) {
    $totalHours    = (float) ($r['total_hours']    ?? 0);
    $requiredHours = (int)   ($r['required_hours'] ?? 500);
    $completionPct = $requiredHours > 0
        ? round(($totalHours / $requiredHours) * 100, 1) . '%'
        : '—';

    $accountStatus = $r['is_active'] ? 'Active' : 'Pending Approval';

    fputcsv($out, [
        $r['id'],
        $r['intern_name']          ?? '',
        $r['email']                ?? '',
        $r['phone']                ?? '',
        $r['school']               ?? '',
        $r['course']               ?? '',
        $r['year_level']           ?? '',
        $accountStatus,
        $r['registered_on']        ?? '',

        $r['joined_date']          ?? '',
        $r['start_date']           ?? '',
        $r['end_date']             ?? '',

        $r['company_name']         ?? '',
        $r['company_address']      ?? '',
        $r['company_phone']        ?? '',
        $r['company_email']        ?? '',

        $r['position']             ?? '',
        $r['supervisor']           ?? '',
        $r['supervisor_phone']     ?? '',
        $r['internship_status']    ?? '',

        $totalHours,
        $requiredHours,
        $completionPct,
        $r['days_present']         ?? 0,
        $r['reports_submitted']    ?? 0,
    ]);
}

fclose($out);
exit;
