<?php

require_once __DIR__ . '/../includes/db_connection.php';

echo "<h1>Capstone Project - Stage Plotter</h1>";
echo "<p>PHP is working - yay!</p>";

$result = mysqli_query($connection, "SHOW TABLES");

if($result) {
  echo "<h2>Database Connection Successful</h2>";

  echo "<h3>Database Schema</h3>";
  echo "<p><a href='https://dbdiagram.io/d/StagePlotter-dev-6980d7d8bd82f5fce262143a' target='_blank'>View schema on dbdiagram.io</a></p>";
  
  echo "<h3>Tables in database:</h3>";
  echo "<ul>";
  while($row = mysqli_fetch_array($result)) {
    echo "<li>" . htmlspecialchars($row[0]) . "</li>";
  }
  echo "</ul>";
} else {
  echo "<p>Error: " . htmlspecialchars(mysqli_error($connection)) . "</p>";
}

mysqli_close($connection);
