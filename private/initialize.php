<?php 

require_once('db_connection.php');

// Autoload class definitions
function my_autoload($class)
{
  if(preg_match('/\A\w+\Z/', $class)) {
    include('classes/' . strtolower($class) . '.class.php');
  }
}
spl_autoload_register('my_autoload');

// Use this if autoload fails:
// Manually loads all files in classes folder with a loop
foreach (glob(__DIR__ . '/classes/*.class.php') as $file) {
  require_once($file);
}

DatabaseObject::set_database($db);
$session = new Session;
