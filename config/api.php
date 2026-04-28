<?php
session_start();
header('Content-Type: application/json');

require 'functions.php';

$data = json_decode(file_get_contents("php://input"), true);
$action = $data['action'] ?? '';

switch ($action) {
  case 'getUsers':
    echo json_encode(getUsers());
    break;

  default:
    echo json_encode(["error" => "Invalid action"]);
}
