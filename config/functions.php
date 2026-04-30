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
function getAllInternData() {
    if (!isset($_SESSION['user']['id'])) {
        return null;
    }

    $pdo = getDB();

    $stmt = $pdo->prepare("
        SELECT 
            u.id AS user_id,
            u.name,
            u.email,
            u.phone,

            ip.id AS profile_id,
            ip.course,
            ip.year_level,
            ip.skills,
            ip.bio,

            i.id AS internship_id,
            i.position,
            i.start_date,
            i.end_date,
            i.status AS internship_status,

            c.id AS company_id,
            c.name AS company_name,
            c.address AS company_address,
            c.email AS company_email

        FROM users u
        LEFT JOIN intern_profiles ip ON ip.user_id = u.id
        LEFT JOIN internships i ON i.user_id = u.id
        LEFT JOIN companies c ON c.id = i.company_id
        WHERE u.id = ?
        LIMIT 1
    ");

    $stmt->execute([$_SESSION['user']['id']]);

    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    return $result ?: null;
}
