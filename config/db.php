<?php
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', 'error_log.txt');
error_reporting(E_ALL);

function getDB() {
  static $pdo = null;

  if ($pdo === null) {

    /*
    $host = "localhost";
    $user = "root";
    $pass = "";
    $db   = "your_database";
    */

    /*
    $host = "fdb1034.awardspace.net";
    $user = "4753482_capstone";
    $pass = "Pa_787898_;"; 
    $db   = "4753482_capstone"; 
    */

    $host = "localhost";
    $user = "u537499572_internship";
    $pass = "Pa537499572"; 
    $db   = "u537499572_internship"; 

    // Set PHP timezone
    date_default_timezone_set('Asia/Manila');

    $dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";

    $options = [
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
      PDO::ATTR_EMULATE_PREPARES => false,
    ];

    $pdo = new PDO($dsn, $user, $pass, $options);

    // Set MySQL session timezone
    $pdo->exec("SET time_zone = '+08:00'");
  }

  return $pdo;
}
?>
