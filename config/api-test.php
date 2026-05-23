<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();

require_once __DIR__ . '/functions.php';

header('Content-Type: application/json; charset=utf-8');
echo "USER " . $_SESSION['user']['name'] . " (ID: " . $_SESSION['user']['id'] . ", ROLE: " . $_SESSION['user']['role'] . ")\n";

try {
    echo json_encode([
        'success' => true,
        'data'    => getCoordinatorDocuments()
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}
?>