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

//Parse Backend
use Parse\ParseClient;
$app_id = "OVPsA58Uck3NCqpnrW7KTZJtThk8bIZJ11aLxlI6";
$rest_key = "wP9kY83dL9X8JwzeLehDfz6Rv2FNSz64dTcrdOum";
$master_key= "DukY9kv5TIfLpRDUhIAwhKPj1jFBYWcg8QdQaxPj";
ParseClient::initialize( $app_id, $rest_key, $master_key );

//Lib
require 'lib/CAcontroller.class.php';  // Handling Controller Class

//Routes
require 'routes/mid-CAsignature.php';
require 'routes/mid-user.php';
require 'routes/mid-login.php';
require 'routes/mid-message.php';
require 'routes/mid-verify.php';

//Time
date_default_timezone_set("Asia/Jakarta"); 

$app->run();
