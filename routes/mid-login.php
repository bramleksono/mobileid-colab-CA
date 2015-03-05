<?php
//Routes for mobileid-login function
use Parse\ParseQuery;
$ca_userdb_que = new ParseQuery("ca_userdb");
$temp_register_que = new ParseQuery("ca_temp_register");

$app->post('/login', function () use ($app,$ca_userdb_que) {
	//example query : {"userinfo":{"nik":"1231230509890001"},"callback":"http://postcatcher.in/catchers/54f7074cc895880300002ba1"}
	global $SIlogin;
    $error=3;
    
    //construct message to user
    $message = "Login request for Mobile ID website";
    
	$body = json_decode($app->request()->getBody());
	$idnumber = $body->userinfo->nik;
	$callback = $body->callback;
	//echo "Login request to ".$idnumber;
	
	$user = new CAuser($idnumber);
	
	if ($user->isRegistered()) {
		$userinfo = $user->getUserInfo();
		$deviceid = $user->getUserDevice();
	    $error=0;
	} else {
		echo "No result";
        $error=1;
	}
	
	if ($error==0) {
		//send request to SI
		$req = (object) array("userinfo" => $userinfo, "deviceid" => $deviceid, "message" => $message, "callback" => $callback);
		$req = json_encode($req);
    	$result =sendjson($req,$SIlogin);

    	$result = json_decode($result);
        if ($result->success) {
            $error=0;
        }
        else {
            $error=2;
        }
	}
	//construct response
	header('Content-Type: application/json');
	switch ($error) {
	case 0:
		//send pubkey 
        echo json_encode(array(	'success' => true,
        						'PID' => $result->PID
		));
		break;
	case 1:
		echo json_encode(array(	'success' => false,
                                'reason' => "Cannot find user information"
		));
	default:
		echo json_encode(array(	'success' => false,
					'reason' => $result->reason
		));
		break;
	}
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