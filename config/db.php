<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
?>
<?php

$host = "sql212.infinityfree.com";
$user = "if0_41451086";
$pass = "vg285V2bypuq"; 
$db   = "if0_41451086_XXX"; 

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>