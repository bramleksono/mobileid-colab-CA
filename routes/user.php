<?php

$app->post('/user/reg', function () use($app) {
	global $SIuserreg;
	
	//echo "This is user registration section";	
	$body = json_decode($app->request()->getBody());
	
	//check purpose
	$purpose = $body->meta->purpose;
	
	$pin = $body->userinfo->pin;
	
	//initialize error
	$error = 3;
	
	if (checkregrequest($body)) {
		//check purpose
		switch ($purpose) {
		case "userreg":
			//echo "You want user reg";
			
			//send request to SI
			$data = constregtoSI($body);
			$data = json_encode($data);
			
			$response = sendjson($data,$SIuserreg);
			$response = json_decode($response);
			
			//check if error exist
			if ($response->success = true) {
				$error=0;
			}
			else
				//SI sent error message
				$error=1;
								
			break;
		default:
			$error = 2;
			break;
		}
	}
	
	//save userinfo to database
	if ($error == 0) {
        //use time as key
		$current_date = new DateTime("now");
		$time = $current_date->format('Y-m-d H:i:s');
		$key=getkey($time);
		//get user info
		$form = parseform($body);
		//encrypt user info
		$result = encryptdb(json_encode($form),$key);
		
		//Save to CA db
		//table: nik| userinfo| date created| IV
		$userinfo = $result[0];
		$iv = $result[1];
		
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
								'reason' => "SI message: ".$response->reason
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

function checkregrequest($data) {
	//TODO check every field
	if (!isset($data->meta->purpose))
		return false;
	if (!isset($data->userinfo->pin))
		return false;
	if (!isset($data->userinfo->nik))
		return false;
	return true;		
}
