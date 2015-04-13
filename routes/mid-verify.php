<?php

$app->post('/verify/', function () use ($app) {
   //example query : {"userinfo":{"nik":"1231230509890001"},"callback":"http://postcatcher.in/catchers/54f7074cc895880300002ba1","message":"Verification request from user 1231230509890005"}
	$body = json_decode($app->request()->getBody(), true);
	$body["message"] = "Identity request for Mobile ID website verification";
    
	$controller = new CAcontroller();
	$error = $controller->verifyreq($body);
	
	//construct response
	header('Content-Type: application/json');
	echo $controller->verifyreqoutput($error);
});

$app->post('/verify/confirm', function () use ($app) {
	//example query : {"callback":"http://postcatcher.in/catchers/54f7074cc895880300002ba1","PID":"625ae82c6b5502a08195389c93be6263f1c65185","userinfo":{"nik":"1231230509890001"}}
	$body = json_decode($app->request()->getBody(), true);
	$callback = $body["callback"];
	$controller = new CAcontroller();
	
	//construct response
	header('Content-Type: application/json');
	$form = $controller->verifyconfirmoutput($body);
	if ($form) {
		$result = sendjson($form,$callback);
	}
});