<?php
//Routes for mobileid-message function

$app->post('/message', function () use ($app) {
	//example query : {"meta":{"purpose":"sendmessage"},"userinfo":{"nik":"1231230509890001","message":"Hello"}}
    
	$body = json_decode($app->request()->getBody());
	
	$controller = new CAcontroller();
	$error = $controller->messagereq($body);
	
    //construct response to RA
	header('Content-Type: application/json');
	echo $controller->messagereqoutput($error);
});
