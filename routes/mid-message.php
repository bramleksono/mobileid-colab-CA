<?php
//Routes for mobileid-message function
$app->post('/message', function () use ($app) {
	//example query : {"userinfo":{"nik":"1231230509890001","message":"Hello"}}
    global $SImessaging;
    $error=3;
    
	$body = json_decode($app->request()->getBody());
	$idnumber = $body->userinfo->nik;
	$user = new CAuser($idnumber);
	
	if ($user->isRegistered()) {
		$deviceid = $user->getUserDevice();
	    $error=0;
	} else {
        $error=1;
	}
	
	if ($error==0) {
	    $reg = (object) array("userinfo" => (object) array("deviceid" => $deviceid, "message" => $body->userinfo->message));
    	$reg = constmessagetoSI($reg);
    	$reg = json_encode($reg);
    	$result =sendjson($reg,$SImessaging);
    	$result = json_decode($result);
        if ($result->success) {
            $error=0;
        }
        else {
            $error=2;
        }
	}
	
    //construct response to RA
	header('Content-Type: application/json');
	switch ($error) {
	case 0:
		//send pubkey 
        echo json_encode(array(	'success' => true
		));
		break;
	case 1:
		echo json_encode(array(	'success' => false,
                                'reason' => "Cannot find user information"
		));
		break;
	case 2:
        echo json_encode(array(	'success' => false,
                                'reason' => $result->reason
		));
		break;
	default:
		echo json_encode(array(	'success' => false,
					'reason' => "Unknown error"
		));
		break;
	}
	
});

function constmessagetoSI($data) {
    $form = array(
    				'meta' => array(
    								'purpose' => 'sendmessage'),
    				'userinfo' => array(
    								'deviceid' => $data->userinfo->deviceid,
    								'message' => $data->userinfo->message)
                );
    return $form;
}