<?php
  // I am using Docker and ddev as my local production environment instead of xampp. 
  // In ddev, each project runs in separate Docker containers.
  // The database container ($host) is named 'db' on the internal Docker network.
  // For simplicity in regards to testing the production environment, I am using ddev's $username and $password defaults ('db') so I do not have to redefine these variables in the server every time testing the mysql dump in a new database. 

  $host = 'db';
  $username = 'db';
  $password = 'db';
  $dbname = 'stage_plotter';

//  Save this for later: 
//   define("DB_SERVER", "db");
//   define("DB_USER", "db_user");
//   define("DB_PASS", "db_pass"); 
//   define("DB_NAME", "stage_plotter");

  $connection = mysqli_connect($host, $username, $password, $dbname);

  if(!$connection) {
    die("Database connection failed: " . mysqli_connect_error());
  }
