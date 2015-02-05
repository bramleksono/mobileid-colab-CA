<?php
//Aplikasi Mobile ID - CA untuk kolaborasi internet.

require 'vendor/autoload.php';
require 'lib/redbean/rb.php';

//twig init
$loader = new Twig_Loader_Filesystem('templates');
$twig = new Twig_Environment($loader);

//slim init
$app = new \Slim\Slim();

//Config
$configfile = 'config.json';
$addressfile = 'config/address.json';

//Lib
require 'lib/crypt.php';  // Handling cryptographic function
require 'lib/addstruct.php';  // Construct client address
require 'lib/sending.php';  // Handling sending http request function

//Routes
require 'routes/install.php';
//require 'routes/register.php';
//require 'routes/response.php';
require 'routes/checkaddress.php';
require 'routes/user.php';
require 'routes/mid-login.php';

//Time
date_default_timezone_set("Asia/Jakarta"); 

$app->run();
