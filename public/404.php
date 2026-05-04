<?php require_once __DIR__ . '/../private/initialize.php'; ?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>404 Not Found</title>
  <meta name="description" content="The page you're looking for couldn't be found.">
  <link rel="stylesheet" href="css/styles.css">
</head>

<body>
  <?php require_once 'includes/header.php'; ?>
  <main class="not-found">
    <div class="wrapper">
      <h1>Uh oh!</h1>
      <p>Sorry, the page you are looking for does not exist.</p>
      <div>
        <!-- <video autoplay loop muted playsinline>
          <source src="assets/brand/logo-animated.mp4" type="video/mp4">
        </video> -->
        <video autoplay loop muted playsinline>
          <source src="assets/brand/logo-animated2.mp4" type="video/mp4">
        </video>
      </div>
      <p><a href="index.php">Return to Home Page</a></p>
    </div>
  </main>
  <?php require_once 'includes/footer.php'; ?>
</body>

</html>
