<?php

require_once __DIR__ . '/../private/initialize.php';

// echo "<h1>Welcome to StagePlotter.dev</h1>";
// echo "<p>PHP is working - yay!</p>";

// $result = $db->query("SHOW TABLES");

// if ($result) {
//   echo "<h2>Database Connection Successful</h2>";

//   echo "<h3>Database Schema</h3>";
//   echo "<p><a href='https://dbdiagram.io/d/StagePlotter-dev-6980d7d8bd82f5fce262143a' target='_blank'>View schema on dbdiagram.io</a></p>";

//   echo "<h3>Tables in database:</h3>";
//   echo "<ul>";
//   while ($row = $result->fetch(PDO::FETCH_NUM)) {
//     echo "<li>" . htmlspecialchars($row[0]) . "</li>";
//   }
//   echo "</ul>";
// } else {
//   echo "<p>Error: Query failed.</p>";
// }

$db = null;
?>

<a href="dashboard.php">Go to Dashboard</a>
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Stage Plotter</title>
  <link rel="stylesheet" href="/css/styles.css">
</head>

<body>
  require_once __DIR__ . '/includes/header.php';
  <main>
    <h1>Welcome to StagePlotter!</h1>
    <h2>Browse and Search Existing Stage Plots</h2>
    <!--  Insert PHP loop to display existing stage plots here -->
    <!-- Features: search bar, table with table headings of title, venue, user, gig date -->
  </main>

  <!-- Move "About" section above main -->
  <section>
    <h2>About StagePlotter</h2>
    <p>StagePlotter helps musicians and sound engineers create, save, and share professional stage plots for live performances. Drag and drop instruments and equipment onto your stage, label each input, then share a link or print a PDF — all in minutes. Whether you're a solo artist or a full band, StagePlotter takes the guesswork out of show day.</p>
    <p>Unfortunately, the drag-and-drop feature of this application makes it difficult to use on mobile devices or screens smaller than 600px wide. We recommend using a desktop or laptop computer to access the full functionality of StagePlotter.</p>
  </section>
  require_once __DIR__ . '/includes/footer.php';
</body>
