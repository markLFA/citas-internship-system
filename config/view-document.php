<?php

session_start();

require_once __DIR__ . '/supabase.php';
require_once __DIR__ . '/db.php';

// 1. Authenticate user
if (empty($_SESSION['user']['id'])) {
    http_response_code(401);
    exit('Unauthorized.');
}

$userId = $_SESSION['user']['id'];
$requestedFile = $_GET['file'] ?? '';

if (empty($requestedFile)) {
    http_response_code(400);
    exit('File not specified.');
}

// 2. Sanitize file path
$filePath = ltrim(str_replace('\\', '/', $requestedFile), '/');

if (strpos($filePath, '..') !== false) {
    http_response_code(400);
    exit('Invalid file path.');
}

// 3. Authorize user access against database
$stmt = $pdo->prepare('SELECT id FROM documents WHERE file_path = :path AND user_id = :user_id LIMIT 1');
$stmt->execute([
    'path' => $filePath,
    'user_id' => $userId
]);

$document = $stmt->fetch();

if (!$document) {
    http_response_code(403);
    exit('Forbidden: You do not have access to this file.');
}

// 4. Generate signed URL and redirect
$signedUrl = createSupabaseSignedUrl($filePath, 300); // 5-minute expiry

if (!$signedUrl) {
    http_response_code(500);
    exit('Unable to generate file access URL.');
}

header('Location: ' . $signedUrl);
exit;