<?php
require 'db.php';

function getUsers() {
  global $conn;

  $stmt = $conn->query("SELECT id, username FROM users");
  return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
