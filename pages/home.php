<?php
session_start();
echo "Welcome to CITAS Internship System!";
$userid = $_SESSION["user_id"];
$name = $_SESSION["name"];
$role = $_SESSION["role"];
echo "Hello, $name! You are logged in as $role.";
?>