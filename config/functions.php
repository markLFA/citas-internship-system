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
  // make sure session is started BEFORE calling this function
  if (!isset($_SESSION['user']['id'])) {
    return null; // or throw error if you prefer
  }

  $pdo = getDB(); // assuming you’re using the getDB() pattern

  $stmt = $pdo->prepare("
    SELECT *
    FROM intern_profiles
    WHERE user_id = ?
    LIMIT 1
  ");

  $stmt->execute([$_SESSION['user']['id']]);
  echo "Executed query for user_id: " . $_SESSION['user']['id']; // Debugging line

  return $stmt->fetch(PDO::FETCH_ASSOC);
}
