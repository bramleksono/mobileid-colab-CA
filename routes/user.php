<?php

$app->post('/user/reg', function () use($app) {
	global $SIuserreg;
	
	//echo "This is user registration section";	
	$body = json_decode($_POST['content']);
	
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
		
		//Save to temp file
		//table: nik| userinfo| date created| IV| publickey
		$userinfo = $result[0];
		$iv = $result[1];
		$current_date = new DateTime("now");
		
		//utf8 conversion, because json_encode silently error when use with binary
		$table = array(	'nik' => $body->userinfo->nik,
				'userinfo' => utf8_encode($userinfo),
				'created' => $time,
				'iv' => utf8_encode($iv),
				'publickey' => utf8_encode($response->pubkey)
		);
		
		$table = json_encode($table);
		
		//generate registration code
		$length = 8;
		$regcode = bin2hex(openssl_random_pseudo_bytes($length));
		$filename = "tmp/". $regcode . ".reg.tmp";
		//save table to temp file. 
		file_put_contents($filename, $table);
		//save signature image
		$target_dir = "tmp/". $regcode.".sig.jpg";
		if (move_uploaded_file($_FILES['file_contents']['tmp_name'], $target_dir)) {
			//echo $message = "The file ". $target_dir . " has been uploaded.";
		} else {
			//echo $message = "Sorry, there was an error uploading your file.";
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

$app->get('/user/regconfirm', function () use($app) {
	echo "Hello";
	$regcode = $_GET['regcode'];
	
	//get temp file
	$filename = "tmp/". $regcode . ".reg.tmp";
	$postreg = json_decode(file_get_contents($filename), true);
	$idnumber = $postreg['nik'];
	$userinfo = utf8_decode($postreg['userinfo']);
	$iv =  utf8_decode($postreg['iv']);
	$key=getkey($postreg['created']);
	
	//decode and show userinfo
	echo decryptdb($userinfo,$iv,$key);
	
	//save device id
	$deviceid = array('deviceid' => $_GET['deviceid']);
	$table = array_merge($postreg,$deviceid);
	
	//utf8 decode
	$table['userinfo'] = utf8_decode($table['userinfo']);
	$table['iv'] = utf8_decode($table['userinfo']);
	$table['publickey'] = utf8_decode($table['publickey']);
	
	//save to database
	//table: nik| userinfo| date created| IV| publickey | deviceid
	
	//var_dump($table);
	
	//delete temp file
	unlink($filename = "tmp/". $regcode . ".reg.tmp");
	rename($filename = "tmp/". $regcode . ".sig.jpg", "data/signature/". $idnumber . ".sig.jpg");
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
