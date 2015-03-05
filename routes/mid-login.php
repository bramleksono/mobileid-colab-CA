<?php
//Routes for mobileid-login function

$app->post('/login', function () use ($app) {
	//example query : {"userinfo":{"nik":"1231230509890001"},"callback":"http://postcatcher.in/catchers/54f7074cc895880300002ba1"}
	$body = json_decode($app->request()->getBody());

	$sendlogin = new CAcontroller();
	$error = $sendlogin->loginreq($body);

	//construct response
	header('Content-Type: application/json');
	echo $sendlogin->loginreqoutput($error);
});

$app->post('/login/confirm', function () use ($app) {
	//example query : {"callback":"http://postcatcher.in/catchers/54f7074cc895880300002ba1","PID":"625ae82c6b5502a08195389c93be6263f1c65185"}
	$body = json_decode($app->request()->getBody());
	$callback = $body->callback;
	$PID = $body->PID;
	$form = json_encode(array(	'success' => true,
        				'PID' => $PID
        				));
    $result = sendjson($form,$callback);
    //output {"PID":"625ae82c6b5502a08195389c93be6263f1c65185","success":true}
});