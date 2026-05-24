<?php
// view-document.php

// 1. (Optional) Add your session check here to ensure only logged-in users/coordinators can see it
// session_start();
// if (!isset($_SESSION['user_id'])) { die('Unauthorized'); }

if (empty($_GET['file'])) {
    die('File not specified.');
}

// Clean the filename to prevent directory traversal attacks (security baseline)
$fileName = basename($_GET['file']); 

// Map out to your true root folder where the files live
$accountRoot = dirname($_SERVER['DOCUMENT_ROOT'], 2); 
$filePath = $accountRoot . '/uploads/' . $fileName;

if (!file_exists($filePath)) {
    header("HTTP/1.1 404 Not Found");
    die('File not found.');
}

// 2. Automatically detect content type (PDF, PNG, Word doc, etc.)
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mimeType = $finfo->file($filePath);

// 3. Stream the file data safely to the browser
header("Content-Type: " . $mimeType);
header("Content-Length: " . filesize($filePath));

// Change 'inline' to 'attachment' if you want to force download instead of viewing
header("Content-Disposition: inline; filename=\"" . $fileName . "\""); 
header("Cache-Control: private, max-age=86400");

readfile($filePath);
exit;