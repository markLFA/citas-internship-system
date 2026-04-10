<?php
$host = "localhost";
$user = "root";          // change later
$pass = "password";      // change later
$db   = "citas_db";      // change later

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>