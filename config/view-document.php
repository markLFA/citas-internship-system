<?php

require_once __DIR__ . '/supabase.php';
require_once __DIR__ . '/db.php';

if (empty($_GET['file'])) {
    http_response_code(400);
    exit('File not specified.');
}

// Prevent directory traversal
$filePath = str_replace('\\', '/', $_GET['file']);
$filePath = ltrim($filePath, '/');

if (strpos($filePath, '..') !== false) {
    http_response_code(400);
    exit('Invalid file path.');
}

// Make sure the user is logged in
session_start();
/*
if (empty($_SESSION['user']['id'])) {
    http_response_code(401);
    exit('Unauthorized.');
}
*/
/*
 * TODO:
 * Check here whether the logged-in user is actually allowed
 * to access this particular file.
 *
 * For example, check your documents table and verify that
 * the document belongs to this intern/coordinator/etc.
 */

// Generate a signed URL
$signedUrl = createSupabaseSignedUrl($filePath, 300); // 5 minutes

if (!$signedUrl) {
    http_response_code(500);
    exit('Unable to generate file access URL.');
}

// Redirect the browser to the temporary Supabase URL
header('Location: ' . $signedUrl);
exit;