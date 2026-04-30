<?php
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', 'error_log.txt');
error_reporting(E_ALL);

session_start();
header('Content-Type: application/json');

require 'functions.php';

$data = json_decode(file_get_contents("php://input"), true);
$action = $data['action'] ?? '';

switch ($action) {
    case 'logout':
        logout();
        break;
    case 'getInternProfile':
        echo json_encode(getInternProfile());
        break;
    case 'getAllInternData':
        echo json_encode(getAllInternData());
        break;
    default:
        echo json_encode([
            "error" => "Invalid action"
        ]);
        break;
}
?>