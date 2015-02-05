<?php
//Routes for mobileid-login function

$app->post('/login/req', function () use ($app) {
	$body = $app->request()->getBody();
	$postedmessage = json_decode($body);
	//var_dump($postedmessage);
	
	$message =  $postedmessage->message;
	$idnumber =  $postedmessage->idnumber;
	
	//check if idnumber already registered
	
});
