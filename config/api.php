<?php
session_start();
header('Content-Type: application/json');

require 'functions.php';

$data = json_decode(file_get_contents("php://input"), true);
$action = $data['action'] ?? '';

switch ($action) {
  case 'getInternProfile':
    echo json_encode(getInternProfile());
    break;

  default:
    echo json_encode(["error" => "Invalid action"]);
}
