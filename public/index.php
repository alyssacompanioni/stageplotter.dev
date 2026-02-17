<?php

require_once __DIR__ . '/../includes/db_credentials.php';

echo "<h1>Capstone Project - Stage Plotter</h1>";
echo "<p>PHP is working - yay!</p>";

$result = mysqli_query($connection, "SHOW TABLES");

if($result) {
  echo "<h2>Database Connection Successful</h2>";
  echo "<p>Tables in database:</p>";
  echo "<ul>";
  while($row = mysqli_fetch_array($result)) {
    echo "<li>" . htmlspecialchars($row[0]) . "</li>";
  }
  echo "</ul>";
} else {
  echo "<p>Error: " . htmlspecialchars(mysqli_error($connection)) . "</p>";
}

mysqli_close($connection);
