<?php
//Aplikasi Mobile ID - CA untuk kolaborasi internet.

require 'vendor/autoload.php';

//twig init
$loader = new Twig_Loader_Filesystem('templates');
$twig = new Twig_Environment($loader);

//slim init
$app = new \Slim\Slim();

class ResourceNotFoundException extends Exception {}

//Config
$configfile = 'config.json';
$addressfile = 'config/address.json';

//Parse Backend
use Parse\ParseObject;
use Parse\ParseClient;
use Parse\ParseQuery;
$app_id = "";
$rest_key = "";
$master_key= "";
ParseClient::initialize( $app_id, $rest_key, $master_key );
$temp_register_obj = new ParseObject("ca_temp_register");
$temp_register_que = new ParseQuery("ca_temp_register");
$ca_userdb_obj = new ParseObject("ca_userdb");
$ca_userdb_que = new ParseQuery("ca_userdb");
$ca_obs_userdb_obj = new ParseObject("ca_obs_userdb");
$ca_obs_userdb_que = new ParseQuery("ca_obs_userdb");

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
