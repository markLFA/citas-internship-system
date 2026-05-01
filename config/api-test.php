<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/functions.php';

header('Content-Type: application/json; charset=utf-8');

try {
    echo json_encode([
        'success' => true,
        'data'    => getPendingInterns()
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}
?>