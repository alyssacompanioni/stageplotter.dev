<php?
-->
<!-- In ddev, each project runs in separate Docker containers. One container runs your web server (PHP), and another container runs your database (MySQL).  -->
<!-- The database container is named db on the internal Docker network. So when your PHP code needs to connect to MySQL, it connects to a host called db — thats the hostname of the database container. -->
<!-- Its similar to how you might use localhost in XAMPP, but instead of everything running on one machine, ddev has the database in its own container with the network name db. -->

  $host = 'db';
  $username = 'db_user';
  $password = 'db_pass';
  $dbname = 'stage_plotter';

  <!-- Save this for later: 
  define("DB_SERVER", "db");
  define("DB_USER", "db_user");
  define("DB_PASS", "db_pass"); 
  define("DB_NAME", "stage_plotter"); -->

  $connection = mysqli_connect($host, $username, $password, $dbname);

  if(!$connection) {
    die("Database connection failed: " . mysqli_connect_error());
  }
