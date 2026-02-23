<?php
  // Database Connection File.
  // Detects environment (local DDEV vs production) and connects accordingly.
  // Alyssa Companioni - Feb 2026

  // ---- Envvironment Detection ----
  //DDEV sets an env variable we can check for:
  define('ENVIRONMENT', getenv('IS_DDEV_PROJECT') ? 'development' : 'production');

  // ---- Error Reporting ----
  if(ENVIRONMENT === 'development') {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
  } else {
    ini_set('display_errors', 0); 
    error_reporting(0);
    ini_set('log_errors', 1);
    ini_set('error_log', dirname(__DIR__) . '/logs/error.log'); //outside web root: /var/www/html/logs/error.log
  }

  // ---- Credentials ----
  if (ENVIRONMENT === 'development') {
    // DDEV Docker internal network
    define('DB_SERVER', 'db');
    define('DB_USER',   'db'); 
    define('DB_PASS',   'db');
    define('DB_NAME',   'stage_plotter');
  } else {
    // Production server
    define('DB_SERVER', 'localhost');
    define('DB_USER',   'uikf2chfhtzuu'); 
    define('DB_PASS',   'aia693z7vr29');
    define('DB_NAME',   'dblwajnfpvyban');
  }

  // ---- Connection ----
  // Data Source Name:
  $dsn = 'mysql:host=' . DB_SERVER . ';dbname=' . DB_NAME . ';charset=utf8mb4';

  $options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,   // throw exceptions on errors
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,         // fetch as associative arrays by default
    PDO::ATTR_EMULATE_PREPARES   => false,                    // use real prepared statements to prevent SQL injection at the driver level rather than PHP emulating it
  ];

  try {
    $db = new PDO($dsn, DB_USER, DB_PASS, $options);
  } catch (PDOException $e) {
    if(ENVIRONMENT === 'development') {
      die('<pre>Database Connection failed: ' . $e->getMessage() . '</pre>');
    } else {
      error_log('DB Connection Error: ' . $e->getMessage());
      die('A database error occurred. Please try again later.');
    }
  }


  mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT); // throw exceptions on errors

  try {
    $connection = new mysqli(DB_SERVER, DB_USER, DB_PASS, DB_NAME);
  } catch (mysqli_sql_exception $e) {
    if (ENVIRONMENT === 'development') {
      
    }
  }
