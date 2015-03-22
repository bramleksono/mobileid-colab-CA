<?php
//Routes for mobileid-registration function

//Parse Backend
use Parse\ParseObject;
use Parse\ParseQuery;

$temp_register_obj = new ParseObject("ca_temp_register");
$temp_register_que = new ParseQuery("ca_temp_register");
$ca_userdb_obj = new ParseObject("ca_userdb");
$ca_userdb_que = new ParseQuery("ca_userdb");
$ca_obs_userdb_obj = new ParseObject("ca_obs_userdb");

$app->post('/user/initial', function () use($app,$temp_register_obj) {
   //example query : {"userinfo":{"nik":"1231230509890001"}}
	$body = json_decode($app->request()->getBody(), true);
	$controller = new CAcontroller();
	$error = $controller->userinitial($body);
	
	//construct response
	header('Content-Type: application/json');
	echo $controller->userinitialoutput($error);	
});

$app->post('/user/reg', function () use($app,$temp_register_obj) {
	global $SIuserreg;
	
	//echo "This is user registration section";	
	$body = json_decode($app->request()->getBody());
	//check purpose
	$purpose = $body->meta->purpose;

	//initialize error
	$error = 3;
	
	if (checkregrequest($body)) {
		//check purpose
		switch ($purpose) {
		case "userreg":
			//use time as key
			$current_date = new DateTime("now");
			$time = $current_date->format('Y-m-d H:i:s');
			$key=getkey($time);
			//get user info
			$form = parseform($body);
	
			//encrypt user info
			$result = encryptdb(json_encode($form),$key);
			
			$userinfo = $result[0];
			$iv = $result[1];
	
			$temp_register_obj->set("nik", $body->userinfo->nik);
			$temp_register_obj->set("userinfo", utf8_encode($userinfo));
			$temp_register_obj->set("created", $time);
			$temp_register_obj->set("iv", utf8_encode($iv));
			
			try {
			  $temp_register_obj->save();
			  //retrieve registration code
			  $regcode = $temp_register_obj->getObjectId();
			  //echo 'New object created with objectId: ' . $regcode;
			  $error=0;
			} catch (ParseException $ex) {  
			  // Execute any logic that should take place if the save fails.
			  // error is a ParseException object with an error code and message.
			  // echo 'Failed to create new object, with error message: ' + $ex->getMessage();
			  $error=1;
			}				
			break;
		default:
			$error = 2;
			break;
		}
	}

	//construct response to RA
	header('Content-Type: application/json');
	switch ($error) {
	case 0:
		//send pubkey 
		echo json_encode(array(	'success' => true,
					'regcode' => $regcode
		));
		break;
	case 1:
		echo json_encode(array(	'success' => false,
					'reason' => "Cannot contacting database"
		));
		break;
	case 2:
		echo json_encode(array(	'success' => false,
					'reason' => "CA Message : "."Invalid Purpose"
		));
		break;
	default:
		echo json_encode(array(	'success' => false,
					'reason' => "CA Message : "."Request not complete"
		));
		break;
	}
});

$app->get('/user/regcheck', function () use($app,$temp_register_que) {
	//echo "Hello";
	$regcode = $_GET['regcode'];
	
	//init error
	$error = 1;
	
	try {
		$regfield = $temp_register_que->get($regcode);
		
		if ($regfield) {
			 //echo "Process request";
			 $error = 0;
		} else {
	      // else throw exception
	      throw new ResourceNotFoundException();
		}
	} catch (ResourceNotFoundException $e) {
	    // return 404 server error
	    $app->response()->status(404);
	  } catch (Exception $e) {
	    $app->response()->status(400);
	    $app->response()->header('X-Status-Reason', $e->getMessage());
	  }
    
    if ($error == 0) {
		//get temp file
		$idnumber = $regfield->get('nik');
		$userinfo = utf8_decode($regfield->get('userinfo'));
		$iv =  utf8_decode($regfield->get('iv'));
		$key=getkey($regfield->get('created'));
		
		//decode and show userinfo
		echo decryptdb($userinfo,$iv,$key);
    }
    
    switch ($error) {
	case 0:
		break;
	default:
		echo json_encode(array(	'success' => false,
					'reason' => "Cannot contacting database"
		));
		break;
	}
});


$app->post('/user/regconfirm', function () use($app,$temp_register_que,$ca_userdb_obj,$ca_userdb_que,$ca_obs_userdb_obj) {
	global $SIuserreg;
	global $CAmessaging;
	
	//echo "Hello";
	$body = json_decode($app->request()->getBody());
	$regcode = $body->RegCode;
	
	//init error
	$error = 3;
	
	try {
		$regfield = $temp_register_que->get($regcode);
		
		if ($regfield) {
			 //echo "Process request";
			 $error = 0;
		} else {
	      // else throw exception
	      throw new ResourceNotFoundException();
		}
	} catch (ResourceNotFoundException $e) {
	    // return 404 server error
	    $app->response()->status(404);
	  } catch (Exception $e) {
	    $app->response()->status(400);
	    $app->response()->header('X-Status-Reason', $e->getMessage());
	  }
    
    if ($error == 0) {
		//decode and show userinfo
		$idnumber = $regfield->get('nik');
		$encrypted = utf8_decode($regfield->get('userinfo'));
		$iv =  utf8_decode($regfield->get('iv'));
		$key=getkey($regfield->get('created'));
		
		$userinfo = json_decode(decryptdb($encrypted,$iv,$key), true);
        $userinfo["signature"] = $body->Signature;
		$result = encryptdb(json_encode($userinfo),$key);
		
		$userinfo = $result[0];
		$iv = $result[1];
		
		//send to SI and retrieve public key
		$pin = $body->PIN;
		$reg = (object) array("userinfo" => (object) array("nik" => $idnumber, "pin" => $pin));
		$reg = constregtoSI($reg);
		$reg = json_encode($reg);
		try {
			$response = sendjson($reg,$SIuserreg);
			if (!empty($response)) {
				$response=json_decode($response);
				$error=0;
			} else {
				$error=2;
				throw new ResourceNotFoundException();
			}
		} catch (ResourceNotFoundException $e) {
			// return 404 server error
			$app->response()->status(404);
		} catch (Exception $e) {
			$app->response()->status(400);
			$app->response()->header('X-Status-Reason', $e->getMessage());
		}
    	
    }
	
	if ($error == 0) {
		//save to database
		//move existing data to old database
		$ca_userdb_que->equalTo("nik", $idnumber);
		$results = $ca_userdb_que->find();
		//echo "Successfully retrieved " . count($results) . " scores.";
		// Do something with the returned ParseObject values
		for ($i = 0; $i < count($results); $i++) {
			$object = $results[$i];
			$ca_obs_userdb_obj->set("nik", $object->nik);
			$ca_obs_userdb_obj->set("pubkey", $object->pubkey);
			$ca_obs_userdb_obj->set("created", $object->created);
			$ca_obs_userdb_obj->save();
			//$ca_obs_userdb_obj->getObjectId();
			$object->destroy();
		}
		
		//table: nik| userinfo| date created| IV| publickey | deviceid
		$ca_userdb_obj->set("nik", $idnumber);
		$ca_userdb_obj->set("userinfo", utf8_encode($userinfo));
		$ca_userdb_obj->set("created", $regfield->get('created'));
		$ca_userdb_obj->set("iv", utf8_encode($iv));
		$ca_userdb_obj->set("pubkey", utf8_encode($response->pubkey));
		$ca_userdb_obj->set("deviceid", $body->GCMAddress);
		try {
			$ca_userdb_obj->save();
			//retrieve registration code
			//$regcode = $ca_userdb_obj->getObjectId();
			//echo 'New object created with objectId: ' . $regcode;
			//send success message
			$reg = (object) array("userinfo" => (object) array("nik" => $idnumber, "message" => "You are now registered to MobileID"));
    		$reg = constmessagetoCA($reg);
    		$reg = json_encode($reg);
    		$result =sendjson($reg,$CAmessaging);
			$error=0;
		} catch (ParseException $ex) {
			// Execute any logic that should take place if the save fails.
			// error is a ParseException object with an error code and message.
			// echo 'Failed to create new object, with error message: ' + $ex->getMessage();
			$error=2;
		}
    }
    
    switch ($error) {
	case 0:
		echo json_encode(array(	'success' => true
		));
		break;
	case 1:
		echo json_encode(array(	'success' => false,
					'reason' => "Cannot connect to SI"
		));
		break;
	case 2:
		echo json_encode(array(	'success' => false,
					'reason' => "Cannot saving to database"
		));
		break;
	default:
		echo json_encode(array(	'success' => false,
					'reason' => "Cannot contacting database"
		));
		break;
	}
});

function parseform($data) {
	$form= array(
			'nik' => $data->userinfo->nik,
			'nama' => $data->userinfo->nama,
			'ttl' => $data->userinfo->ttl,
			'jeniskelamin' => $data->userinfo->jeniskelamin,
			'goldarah' => $data->userinfo->goldarah,
			'alamat' => $data->userinfo->alamat,
			'rtrw' => $data->userinfo->rtrw,
			'keldesa' => $data->userinfo->keldesa,
			'kecamatan' => $data->userinfo->kecamatan,
			'agama' => $data->userinfo->agama,
			'statperkawinan' => $data->userinfo->statperkawinan,
			'pekerjaan' => $data->userinfo->pekerjaan,
			'kewarganegaraan' => $data->userinfo->kewarganegaraan,
			'berlaku' => $data->userinfo->berlaku
	);
	
	return $form;
}

function constregtoSI($data) {
	$form = array(
					'meta' => array(
									'purpose' => 'userreg'),
					'userinfo' => array(
									'nik' => $data->userinfo->nik,
									'pin' => $data->userinfo->pin)
	);
	return $form;
}

function constmessagetoCA($data) {
    $form = array(
    				'meta' => array(
    								'purpose' => 'sendmessage'),
    				'userinfo' => array(
    								'nik' => $data->userinfo->nik,
    								'message' => $data->userinfo->message)
                );
    return $form;
}

function checkregrequest($data) {
	//TODO check every field
	if (!isset($data->meta->purpose))
		return false;
	if (!isset($data->userinfo->nik))
		return false;
	return true;		
}