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
*/

  $host = "fdb1034.awardspace.net";
  $user = "4753482_capstone";
  $pass = "Pa_787898_;"; 

  $db   = "4753482_capstone"; 



    $dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";

    $options = [
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, // throw errors
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, // default fetch mode
      PDO::ATTR_EMULATE_PREPARES => false, // use real prepared statements
    ];

    $pdo = new PDO($dsn, $user, $pass, $options);
  }

  return $pdo;
}
?>
