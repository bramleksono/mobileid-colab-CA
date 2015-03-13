<?php
//Routes for mobileid-login function

/*
$app->post('/login', function () use ($app) {
	//example query : {"userinfo":{"nik":"1231230509890001"},"callback":"http://postcatcher.in/catchers/54f7074cc895880300002ba1"}
	$body = json_decode($app->request()->getBody());

	$controller = new CAcontroller();
	$error = $controller->loginreq($body);

	//construct response
	header('Content-Type: application/json');
	echo $controller->loginreqoutput($error);
});

$app->post('/login/confirm', function () use ($app) {
	//example query : {"callback":"http://postcatcher.in/catchers/54f7074cc895880300002ba1","PID":"625ae82c6b5502a08195389c93be6263f1c65185"}
	$body = json_decode($app->request()->getBody());
	$callback = $body->callback;
	
	$controller = new CAcontroller();
	$form = $controller->loginconfirmoutput($body);

	if ($form) {
		$result = sendjson($form,$callback);
	}
    //output {"PID":"625ae82c6b5502a08195389c93be6263f1c65185","success":true}
});

*/

$app->post('/login/', function () use ($app) {
	//try using verify function
   //example query : {"userinfo":{"nik":"1231230509890001"},"callback":"http://postcatcher.in/catchers/54f7074cc895880300002ba1"}
	$body = json_decode($app->request()->getBody(), true);
	$body["message"] = "Login request for Mobile ID website";
	$controller = new CAcontroller();
	$error = $controller->verifyreq($body);
	
	//construct response
	header('Content-Type: application/json');
	echo $controller->verifyreqoutput($error);
});

$app->post('/login/confirm', function () use ($app) {
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

