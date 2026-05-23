<?php
// ============================================================
//  upload_report.php — Weekly report multi-file upload
//
//  POST  multipart/form-data
//  Fields:
//    week_label   string   required
//    week_start   date     required  (YYYY-MM-DD)
//    description  string   optional
//    files[]      file[]   required  (one or more)
// ============================================================

ini_set('display_errors', 0);
ini_set('log_errors',     1);
ini_set('error_log',      __DIR__ . '/error_log.txt');
error_reporting(E_ALL);

session_start();
header('Content-Type: application/json');

// db.php is in the same directory as this file
require_once __DIR__ . '/db.php';

// Use define() not const — const with expressions requires PHP 8+
define('UPLOAD_DIR', dirname(__DIR__) . '/uploads/reports/');
define('MAX_BYTES',    10485760);    // 10 MB = 10 * 1024 * 1024
define('ALLOWED_EXTS', ['pdf','doc','docx','png','jpg','jpeg']);


// ── Auth ──────────────────────────────────────────────────────
if (empty($_SESSION['user']['id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Not logged in.']);
    exit;
}
if ($_SESSION['user']['role'] !== 'intern') {
    http_response_code(403);
    echo json_encode(['error' => 'Only interns can submit reports.']);
    exit;
}
$userId = (int) $_SESSION['user']['id'];


// ── Method check ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed.']);
    exit;
}


// ── Form field validation ─────────────────────────────────────
$weekLabel   = trim($_POST['week_label']  ?? '');
$weekStart   = trim($_POST['week_start']  ?? '');
$description = trim($_POST['description'] ?? '');

// FIX: Corrected structural ternary syntax from colon (:) to question mark (?)
$coordinatorId = isset($_POST['coordinator_id']) ? $_POST['coordinator_id'] : null;

// Fallback: If coordinator_id isn't provided by the form, find it from the intern profile automatically
if (empty($coordinatorId)) {
    try {
        $db = getDB();
        $profileStmt = $db->prepare("SELECT coordinator_id FROM intern_profiles WHERE user_id = ? LIMIT 1");
        $profileStmt->execute([$userId]);
        $profileRow = $profileStmt->fetch();
        if ($profileRow && !empty($profileRow['coordinator_id'])) {
            $coordinatorId = $profileRow['coordinator_id'];
        }
    } catch (Throwable $t) {
        error_log('upload_report fallback fetching error: ' . $t->getMessage());
    }
}

if ($weekLabel === '') {
    echo json_encode(['error' => 'Week label is required.']);
    exit;
}

$dateObj = DateTime::createFromFormat('Y-m-d', $weekStart);
if (!$dateObj || $dateObj->format('Y-m-d') !== $weekStart) {
    echo json_encode(['error' => 'Invalid week start date.']);
    exit;
}


// ── File validation ───────────────────────────────────────────
if (empty($_FILES['files']['name'][0])) {
    echo json_encode(['error' => 'Please attach at least one file.']);
    exit;
}

$fileCount = count($_FILES['files']['name']);
$validated = [];

for ($i = 0; $i < $fileCount; $i++) {
    $name = $_FILES['files']['name'][$i];
    $tmp  = $_FILES['files']['tmp_name'][$i];
    $size = (int) $_FILES['files']['size'][$i];
    $err  = (int) $_FILES['files']['error'][$i];

    if ($err !== UPLOAD_ERR_OK) {
        $msgs =