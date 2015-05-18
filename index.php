<?php
//Aplikasi Mobile ID - CA untuk kolaborasi internet.

require 'vendor/autoload.php';

//slim init
$app = new \Slim\Slim();
//surpress Slim error
//error_reporting(0);

class ResourceNotFoundException extends Exception {}

//Config
$configfile = 'config.json';
$addressfile = 'config/address.json';

//Lib
require 'lib/CAcontroller.class.php';  // Handling CA controller class
require 'config/parse.php';  // Initialize parse database

//Routes
require 'routes/mid-user.php';
require 'routes/mid-login.php';
require 'routes/mid-message.php';
require 'routes/mid-verify.php';
require 'routes/mid-document.php';

//Time
date_default_timezone_set("Asia/Jakarta"); 

$app->run();
