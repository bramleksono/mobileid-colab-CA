<?php
//Aplikasi Mobile ID - CA untuk kolaborasi internet.

require 'vendor/autoload.php';
//twig init
$loader = new Twig_Loader_Filesystem('templates');
$twig = new Twig_Environment($loader);

//slim init
$app = new \Slim\Slim();
//surpress Slim error
//error_reporting(0);

class ResourceNotFoundException extends Exception {}

//Config
$configfile = 'config.json';
$addressfile = 'config/address.json';

//Parse Backend
use Parse\ParseClient;
$app_id = "OVPsA58Uck3NCqpnrW7KTZJtThk8bIZJ11aLxlI6";
$rest_key = "wP9kY83dL9X8JwzeLehDfz6Rv2FNSz64dTcrdOum";
$master_key= "ebTqQ5LbSHU9yxl2rXx9nUL0cdFtNmaevAcmz5BX";
ParseClient::initialize( $app_id, $rest_key, $master_key );

//Lib
require 'lib/crypt.php';  // Handling cryptographic function
require 'lib/addstruct.php';  // Construct client address
require 'lib/sending.php';  // Handling sending http request function
require 'lib/CAuser.class.php';  // Handling User Class

//Routes
require 'routes/install.php';
//require 'routes/register.php';
//require 'routes/response.php';
require 'routes/checkaddress.php';
require 'routes/mid-user.php';
require 'routes/mid-login.php';
require 'routes/mid-message.php';
require 'routes/mid-verify.php';

//Time
date_default_timezone_set("Asia/Jakarta"); 

$app->run();
