<?php

$app->post('/verify/', function () use ($app) {
   //example query : {"userinfo":{"nik":"1231230509890001"},"callback":"http://postcatcher.in/catchers/54f7074cc895880300002ba1","message":"Verification request from user 1231230509890005"}
	$body = json_decode($app->request()->getBody());
	
	$sendverify = new CAcontroller();
	$error = $sendverify->verifyreq($body);
	
	//construct response
	header('Content-Type: application/json');
	echo $sendverify->verifyreqoutput($error);
});

$app->post('/verify/confirm', function () use ($app) {
   echo "Hello";
});