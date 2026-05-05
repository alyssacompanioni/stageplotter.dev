<?php
/**
 * login.php
 * Displays and processes the user login form.
 */

require_once __DIR__ . "/../private/initialize.php";

$errors = [];
$username = "";
$password = "";

// If already logged in, redirect
if ($session->is_logged_in()) {
	redirect_by_role($session->get_role());
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
	$username = trim($_POST["username"] ?? "");
	$password = $_POST["password"] ?? "";

	// Server-side validations
	if (empty($username)) {
		$errors[] = "Username cannot be blank.";
	} elseif (empty($password)) {
		$errors[] = "Password cannot be blank.";
	} else {
		// Query the database
		$stmt = $db->prepare("SELECT id_usr,
                                 username_usr,
                                 password_hash_usr,
                                 first_name_usr,
                                 role_usr
                           FROM user_usr
                           WHERE username_usr = ? 
                            AND is_active_usr = 1");
		$stmt->execute([$username]);
		$user = $stmt->fetch();

		if ($user && password_verify($password, $user["password_hash_usr"])) {
			//Successful login - set session variables
			$session->login($user);
			redirect_by_role($user["role_usr"]);
		} else {
			// Intentionally vague - don't reveal which field is wrong for security measures
			$errors[] = "Invalid username or password.";
		}
	}
}

/**
 * Redirects the user to the correct dashboard based on their role
 *
 * @param string $role The user's role ('member', 'admin', or 'super_admin').
 * @return void
 */

function redirect_by_role(string $role): void {
	$destinations = [
		"super_admin" => "/super_admin/dashboard.php",
		"admin" => "/admin/dashboard.php",
		"member" => "/stage-plotter.php",
	];

	header("Location: " . ($destinations[$role] ?? "/index.php"));
	exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <title>Log In | Stage Plotter</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Log in to Stage Plotter to access your stage plots and manage your account.">
  <link rel="stylesheet" href="css/styles.css">
  <script src="/js/forms.js" defer></script>
</head>

<body>
  <?php require_once __DIR__ . "/includes/header.php"; ?>
  <div class="wrapper">
    <main class="login-main">
      <h1>Log In</h1>

      <?php if (!empty($errors)) { ?>
        <p class="error" role="alert"><?php foreach ($errors as $error) {
        	echo esc($error) . "<br>";
        } ?></p>
      <?php } ?>

      <form action="login.php" method="post" id="login-form">
        <label for="username">Username <span class="required" aria-label="required">*</span></label><br>
        <input type="text" id="username" name="username" value="<?= esc($username) ?>" required autocomplete="off"><br>

        <label for="password">Password <span class="required" aria-label="required">*</span></label><br>
        <input type="password" id="password" name="password" required autocomplete="current-password"><br>

        <input type="submit" value="Log in">
      </form>

      <p>Don't have an account? <a href="/register.php">Sign Up</a></p>
    </main>
  </div>
  <?php require_once __DIR__ . "/includes/footer.php"; ?>
</body>

</html>
