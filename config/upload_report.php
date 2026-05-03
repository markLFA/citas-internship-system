<?php
// ============================================================
//  upload_report.php
//  Handles weekly report submission with multiple file uploads.
//
//  Method : POST  (multipart/form-data)
//  Fields :
//    week_label   string   required  e.g. "Week 14 (Apr 21–25)"
//    week_start   date     required  e.g. "2026-04-21"
//    description  string   optional
//    files[]      file[]   required  one or more files
//
//  Response: JSON  { success, report_id, files[] }  or  { error }
// ============================================================

ini_set('display_errors', 0);   // never expose errors to client
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error_log.txt');
error_reporting(E_ALL);

session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/config/db.php';   // provides getDB()


// ── Auth guard ────────────────────────────────────────────────
if (empty($_SESSION['user']['id']) || $_SESSION['user']['role'] !== 'intern') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized.']);
    exit;
}

$userId = (int) $_SESSION['user']['id'];


// ── Config ────────────────────────────────────────────────────
const UPLOAD_DIR   = __DIR__ . '/uploads/reports/';
const MAX_FILE_SIZE = 10 * 1024 * 1024;   // 10 MB per file
const ALLOWED_EXTS  = ['pdf', 'doc', 'docx', 'png', 'jpg', 'jpeg'];
const ALLOWED_MIME  = [
    'application/pdf',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'image/png',
    'image/jpeg',
];


// ── Helpers ───────────────────────────────────────────────────

function json_error(string $msg, int $status = 400): void {
    http_response_code($status);
    echo json_encode(['error' => $msg]);
    exit;
}

/** Validate and sanitise a date string; return null if invalid. */
function parse_date(string $raw): ?string {
    $d = DateTime::createFromFormat('Y-m-d', trim($raw));
    return ($d && $d->format('Y-m-d') === trim($raw)) ? trim($raw) : null;
}

/** Generate a unique filename that is safe to store on disk. */
function safe_filename(string $originalName): string {
    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    return uniqid('rpt_', true) . '.' . $ext;
}


// ── Validate request method ───────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('Method not allowed.', 405);
}


// ── Validate form fields ──────────────────────────────────────
$weekLabel   = trim($_POST['week_label']   ?? '');
$weekStartRaw= trim($_POST['week_start']   ?? '');
$description = trim($_POST['description']  ?? '');

if ($weekLabel === '') {
    json_error('Week label is required.');
}

$weekStart = parse_date($weekStartRaw);
if ($weekStart === null) {
    json_error('Invalid week start date. Use YYYY-MM-DD format.');
}


// ── Validate uploaded files ───────────────────────────────────
//  $_FILES['files'] is populated when the input is named files[]
if (empty($_FILES['files']['name'][0])) {
    json_error('At least one file is required.');
}

// Normalise PHP's multi-file array into a flat list
$uploaded = [];
$fileCount = count($_FILES['files']['name']);

for ($i = 0; $i < $fileCount; $i++) {
    $name  = $_FILES['files']['name'][$i];
    $tmp   = $_FILES['files']['tmp_name'][$i];
    $size  = $_FILES['files']['size'][$i];
    $error = $_FILES['files']['error'][$i];
    $mime  = $_FILES['files']['type'][$i];

    if ($error !== UPLOAD_ERR_OK) {
        $errors = [
            UPLOAD_ERR_INI_SIZE   => 'File too large (server limit).',
            UPLOAD_ERR_FORM_SIZE  => 'File too large (form limit).',
            UPLOAD_ERR_PARTIAL    => 'File upload was interrupted.',
            UPLOAD_ERR_NO_FILE    => 'No file was uploaded.',
        ];
        json_error('File "' . htmlspecialchars($name) . '": ' . ($errors[$error] ?? 'Upload error.'));
    }

    if ($size > MAX_FILE_SIZE) {
        json_error('File "' . htmlspecialchars($name) . '" exceeds the 10 MB limit.');
    }

    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    if (!in_array($ext, ALLOWED_EXTS, true)) {
        json_error('File type ".' . $ext . '" is not allowed. Allowed: ' . implode(', ', ALLOWED_EXTS));
    }

    // Verify mime type using fileinfo (more reliable than browser-provided type)
    $finfo    = new finfo(FILEINFO_MIME_TYPE);
    $realMime = $finfo->file($tmp);
    if (!in_array($realMime, ALLOWED_MIME, true)) {
        json_error('File "' . htmlspecialchars($name) . '" has an invalid file type.');
    }

    $uploaded[] = [
        'original_name' => $name,
        'tmp_path'      => $tmp,
        'size'          => $size,
        'mime'          => $realMime,
        'safe_name'     => safe_filename($name),
    ];
}


// ── Ensure upload directory exists ───────────────────────────
if (!is_dir(UPLOAD_DIR)) {
    if (!mkdir(UPLOAD_DIR, 0755, true)) {
        error_log('upload_report.php: could not create upload dir ' . UPLOAD_DIR);
        json_error('Server error: upload directory could not be created.', 500);
    }
}


// ── Insert records in a transaction ──────────────────────────
$pdo = getDB();

try {
    $pdo->beginTransaction();

    // 1. Insert the weekly_report row
    $stmt = $pdo->prepare("
        INSERT INTO weekly_reports
          (intern_id, week_label, week_start, description, status)
        VALUES
          (:intern_id, :week_label, :week_start, :description, 'pending')
    ");
    $stmt->execute([
        ':intern_id'   => $userId,
        ':week_label'  => $weekLabel,
        ':week_start'  => $weekStart,
        ':description' => $description ?: null,
    ]);

    $reportId = (int) $pdo->lastInsertId();

    // 2. Move each file to the upload directory and insert a file row
    $savedFiles = [];

    foreach ($uploaded as $file) {
        $destPath = UPLOAD_DIR . $file['safe_name'];

        if (!move_uploaded_file($file['tmp_path'], $destPath)) {
            throw new RuntimeException(
                'Failed to move file "' . $file['original_name'] . '" to upload directory.'
            );
        }

        $stmt = $pdo->prepare("
            INSERT INTO weekly_report_files
              (report_id, file_path, file_name, file_size, mime_type)
            VALUES
              (:report_id, :file_path, :file_name, :file_size, :mime_type)
        ");
        $stmt->execute([
            ':report_id' => $reportId,
            ':file_path' => $file['safe_name'],
            ':file_name' => $file['original_name'],
            ':file_size' => $file['size'],
            ':mime_type' => $file['mime'],
        ]);

        $savedFiles[] = [
            'file_name' => $file['original_name'],
            'file_size' => $file['size'],
        ];
    }

    $pdo->commit();

    // ── Success ───────────────────────────────────────────────
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
    // Clean up any files already moved before the error
    foreach ($uploaded as $file) {
        $dest = UPLOAD_DIR . $file['safe_name'];
        if (file_exists($dest)) unlink($dest);
    }

    error_log('upload_report.php: ' . $e->getMessage());
    json_error('Submission failed. Please try again.', 500);
}
