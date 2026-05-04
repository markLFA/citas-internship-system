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
define('UPLOAD_DIR',   __DIR__ . '/uploads/reports/');
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
        $msgs = [
            UPLOAD_ERR_INI_SIZE  => 'exceeds server upload limit.',
            UPLOAD_ERR_FORM_SIZE => 'exceeds form size limit.',
            UPLOAD_ERR_PARTIAL   => 'was only partially uploaded.',
        ];
        $msg = $msgs[$err] ?? 'upload error (' . $err . ').';
        echo json_encode(['error' => "\"$name\" $msg"]);
        exit;
    }

    if ($size > MAX_BYTES) {
        echo json_encode(['error' => "\"$name\" exceeds the 10 MB limit."]);
        exit;
    }

    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    if (!in_array($ext, ALLOWED_EXTS, true)) {
        echo json_encode(['error' => "File type \".$ext\" is not allowed."]);
        exit;
    }

    // Detect real mime type if finfo is available; fall back gracefully
    $mime = 'application/octet-stream';
    if (function_exists('finfo_open')) {
        $fi   = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($fi, $tmp);
        finfo_close($fi);
    } elseif (!empty($_FILES['files']['type'][$i])) {
        $mime = $_FILES['files']['type'][$i];
    }

    $validated[] = [
        'original' => $name,
        'tmp'      => $tmp,
        'size'     => $size,
        'mime'     => $mime,
        'stored'   => uniqid('rpt_', true) . '.' . $ext,
    ];
}


// ── Make sure upload directory exists ─────────────────────────
if (!is_dir(UPLOAD_DIR) && !mkdir(UPLOAD_DIR, 0755, true)) {
    error_log('upload_report.php: cannot create ' . UPLOAD_DIR);
    echo json_encode(['error' => 'Server error: cannot create upload folder.']);
    exit;
}


// ── DB transaction ────────────────────────────────────────────
$pdo = getDB();

try {
    $pdo->beginTransaction();

    // Insert weekly_reports row
    $stmt = $pdo->prepare("
        INSERT INTO weekly_reports
            (intern_id, week_label, week_start, description, status)
        VALUES (?, ?, ?, ?, 'pending')
    ");
    $stmt->execute([
        $userId,
        $weekLabel,
        $weekStart,
        $description !== '' ? $description : null,
    ]);

    $reportId   = (int) $pdo->lastInsertId();
    $savedFiles = [];

    foreach ($validated as $file) {
        $dest = UPLOAD_DIR . $file['stored'];

        if (!move_uploaded_file($file['tmp'], $dest)) {
            throw new RuntimeException('Could not save file: ' . $file['original']);
        }

        $stmt = $pdo->prepare("
            INSERT INTO weekly_report_files
                (report_id, file_path, file_name, file_size, mime_type)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $reportId,
            $file['stored'],
            $file['original'],
            $file['size'],
            $file['mime'],
        ]);

        $savedFiles[] = [
            'file_name' => $file['original'],
            'file_size' => $file['size'],
        ];
    }

    $pdo->commit();

    echo json_encode([
        'success'   => true,
        'report_id' => $reportId,
        'week'      => $weekLabel,
        'files'     => $savedFiles,
        'message'   => 'Report submitted successfully.',
    ]);

} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    // Clean up any files already moved before the crash
    foreach ($validated as $file) {
        $dest = UPLOAD_DIR . $file['stored'];
        if (file_exists($dest)) unlink($dest);
    }

    error_log('upload_report.php: ' . $e->getMessage());
    echo json_encode(['error' => 'Submission failed: ' . $e->getMessage()]);
}