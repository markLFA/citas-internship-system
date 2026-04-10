<?php
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', 'error_log.txt');
error_reporting(E_ALL);
?>
<?php

$host = "sql212.infinityfree.com";
$user = "if0_41451086";
$pass = "vg285V2bypuq"; 
$db   = "if0_41451086_citas_internship"; 

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>