<?php
require 'db.php';

function getUsers() {
  global $conn;
  $user = $_SESSION['user'];
  $userId = $user['id'];
  $stmt = $conn->query("SELECT id, username FROM intern_profiles");
  return $stmt->fetchAll(PDO::FETCH_ASSOC);
  
}
function getInternProfile() {
    if (!isset($_SESSION['user']['id'])) {
        return null;
    }

    $pdo = getDB();

    $stmt = $pdo->prepare("
        SELECT *
        FROM intern_profiles
        WHERE user_id = ?
        LIMIT 1
    ");

    $stmt->execute([$_SESSION['user']['id']]);

    return $stmt->fetch(PDO::FETCH_ASSOC);
}
