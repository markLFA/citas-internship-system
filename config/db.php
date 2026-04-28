<?php
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', 'error_log.txt');
error_reporting(E_ALL);
?>
<?php

$host = "fdb1034.awardspace.net";
$user = "4753482_capstone";
$pass = "787898;"; 
$db   = "4753482_capstone"; 

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
