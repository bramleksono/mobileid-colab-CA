<?php

$app->post('/document/', function () use ($app) {
   //example query : {"callback":"tes","description":"Tes","documentname":"Dokumen 3","filehash":"6659b399f20a5ec968741b2659a4c209417b12ca9ef20404d91c13aff31872a7","fileurl":"http://files.parsetfss.com/ec7e7074-b676-4984-92ba-13c0c26c2d0d/tfss-c45261a4-45ab-4846-9a42-90e990ed10cf-ProgLan-23213321-Tugas1.pdf","signerid":"1231230509890001","projectname":"Tes 1"}
	$body = json_decode($app->request()->getBody(), true);
	
	$body["message"] = "Signing request for ".$body["documentname"]." document from ".$body["projectname"]." project with description ".$body["description"];
	
	$controller = new CAcontroller();
	$error = $controller->documentreq($body);
	
	//construct response
	header('Content-Type: application/json');
	echo $controller->documentreqoutput($error);
});

$app->post('/document/confirm', function () use ($app) {
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

$app->post('/document/verify', function () use($app) {
	//example query : {"signature":"lftadtwwszA9DN7s6VDXOzHRPRowUV6AFRH4mWeKY//GPQ1mDulI1Wesrf4AzniN53W7+mwehjAF4gXLTV5MG68xUwoVKFKN2fK90kLSsnBmxPAJ1nxRVMmizZV3MbYZOLYyHj6IvIpaO00b7PgThTsqCncIH7hnHIdSpEx2ugp6Y3dcpAjqR9h/bRGO+btvRSsDsnuBMbajmRcKoUCGuj0S3G7QZGLmx6ifHLFqfl9Rzm+7wtfskKUbG93UqXM0DdnuiswMtwtQ4/LfHoOCLZezF5R4uVjd2sj/aga9J74W2zFhv5GMGba7Tmc6rC8ilVoUJsk2dQDkV0x/PAjcffMXz+Hiil9B/utM54hsZAa9nfVk3nhX30rkgRjUimdrwquRtfJrZUQitvO27WYx4TPie8wQMHj92l+XyLgPmC8sf7EiVKWBf13JTUA5eCOGZA9/txVb8ItTAn65vMokARzjEJqhEdihRFTfu+zjUErznMAzJD+Qk3wHTLM/PTbXI8lI6aOb+d7H6FSU+rca5/WbuyRkMIxcwgb1X1r79Zk7vD3QLykGV0v52ogxuRwa2CFRgX1Dt/eivollcQyAEYuxcX2evaPhlUkHMtLOKgyMPoi2rwdFU/+4DdzMimHlQ3dzgAYvOc2/XIYkX9DBSztaBgOF1LdFg4fIjm1XkBM=","signedtime":"2015-03-28 07:15:29","signedhash":"1a1f05a1f6cf823fab41f69c0a00f64a64d5874d08265b9ffd5624092b5534a9","idnumber":"1231230509890001"}
	$body = json_decode($app->request()->getBody(), true);
	$controller = new CAcontroller();
	$error = $controller->documentverify($body);
	
	//construct response
	header('Content-Type: application/json');
	echo $controller->documentverifyoutput($error);	
});