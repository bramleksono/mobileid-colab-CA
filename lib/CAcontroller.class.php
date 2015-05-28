<?php
require 'CApid.class.php'; // Handling CA PID interaction with database
require 'CAuser.class.php';  // Handling User Class
require 'crypt.php';  // Handling cryptographic function
require 'addstruct.php';  // Construct client address
require 'sending.php';  // Handling sending http request function

class CAcontroller {
    
    private function constmessagetoSI($data) {
        return array(
        				'meta' => array(
        								'purpose' => 'sendmessage'),
        				'userinfo' => array(
        								'deviceid' => $data->userinfo->deviceid,
        								'message' => $data->userinfo->message)
                    );
    }
    
    private function get_starred($str){
        $len = strlen($str);
        return substr($str, 0,1). str_repeat('*',$len - 2) . substr($str, $len - 1 ,1);
    }
    
    // User Intital Section
    public function userinitial($request) {
        $error = 3;
        
        $idnumber = $request["userinfo"]["nik"];
        $user = new CAuser($idnumber);
        if ($user->isRegistered()) {
    		$deviceid = $user->getUserDevice();
    	    $error=0;
    	} else {
            $error=1;
    	}
    	
    	if ($error==0) {
    	    $userinfo = $user->getUserInfo();
    	    if ($userinfo) {
        	    $name = $userinfo["nama"];
        	    $name = $this->get_starred($name);
        	    $this->initial = $name;    	        
    	    } else {
    	        $error=2;
    	    }
    	}
    	return $error;
    }

    public function userinitialoutput($error) {
        switch ($error) {
    	case 0:
    		//send pubkey 
            return json_encode(array(	'success' => true,
                        'initial' => $this->initial
    		));
    		break;
    	case 1:
    		return json_encode(array(	'success' => false,
    					'reason' => "User is not registered"
    		));
    		break;
    	case 2:
    		return json_encode(array(	'success' => false,
    					'reason' => "Cannot read database"
    		));
    		break;
        default:
    		return json_encode(array(	'success' => false,
    					'reason' => "Unknown Error"
    		));
    		break;
    	}
    }
    
    // Message Section
    public function messagereq($request) {
        global $SImessaging;
        $error = 3;
        
        $idnumber = $request["userinfo"]["nik"];
        $user = new CAuser($idnumber);
        if ($user->isRegistered()) {
    		$deviceid = $user->getUserDevice();
    	    $error=0;
    	} else {
            $error=1;
    	}
    	
    	if ($error==0) {
    	    $reg = (object) array("userinfo" => (object) array("deviceid" => $deviceid, "message" => $request["userinfo"]["message"]));
        	$reg = $this->constmessagetoSI($reg);
        	$reg = json_encode($reg);
        	$result =sendjson($reg,$SImessaging);
        	$result = json_decode($result, true);
            if ($result["success"]) {
                $error=0;
            }
            else {
                $this->reason = $result["reason"];
                $error=2;
            }
    	}
    	return $error;
    }
    
    public function messagereqoutput($error) {
        switch ($error) {
    	case 0:
    		//send pubkey 
            return json_encode(array(	'success' => true
    		));
    		break;
    	case 1:
    		return json_encode(array(	'success' => false,
                                    'reason' => "Cannot find user information"
    		));
    		break;
    	case 2:
            return json_encode(array(	'success' => false,
                                    'reason' => $this->reason
    		));
    		break;
    	default:
    		return json_encode(array(	'success' => false,
    					'reason' => "Unknown error"
    		));
    		break;
    	}
    }
    
    // Login and Verification Section
    
    public function verifyreq($request) {
        global $SIverify;
        $error=3;
        
        $idnumber = $request["userinfo"]["nik"];
	    $callback = $request["callback"];
	    $message = $request["message"];
        
        if (isset($request["projectid"])) {
            $projectid = $request["projectid"];
        } else {
            $projectid = "";
        }
	    
	    //echo "Login request to ".$idnumber;
	    $user = new CAuser($idnumber);
	    
	    if ($user->isRegistered()) {
    		$userinfo = $user->getUserInfo();
    		$deviceid = $user->getUserDevice();
    	    $error=0;
    	} else {
            $error=1;
    	}
    	
    	if (empty($userinfo)) {
            $this->reason = "Cannot read user information.";
            $error=2;
        } 
    	
    	if ($error==0) {
    		//send request to SI
    		$req = (object) array("userinfo" => $userinfo, "deviceid" => $deviceid, "message" => $message, "projectid" => $projectid, "callback" => $callback);
    		$req = json_encode($req);
        	$result =sendjson($req,$SIverify);
        	$result = json_decode($result, true);
        	if ($result) {
                if ($result["success"]) {
                    $PID = $result["PID"];
                    $this->PID = $PID;
                    
                    //save PID to DB
                    $current_date = new DateTime("now");
                	$time = $current_date->format('Y-m-d H:i:s');
                	$key=getkey($time);
                	//encrypt PID
            	    $result = encryptdb(json_encode($req),$key);
                	$data = $result[0];
                	$iv = $result[1];
                	
                    $piddb = new CApid($PID);
                    $pidresult = $piddb->storePIDDB($PID,$data,$time,$iv);
                    
                    $error=0;
                }
                else {
                    $this->reason = $result->reason;
                    $error=2;
                }        	    
        	} else {
        	    $this->reason = "Cannot connect to SI";
        	    $error = 2;
        	}

    	}
    	return $error;
    }
    
    public function verifyreqoutput($error) {
       switch ($error) {
    	case 0:
    		//send pubkey 
            return json_encode(array(	'success' => true,
            						'PID' => $this->PID
    		));
    		break;
    	case 1:
    		return json_encode(array(	'success' => false,
                                    'reason' => "Cannot find user information"
    		));
    	default:
    		return json_encode(array(	'success' => false,
    					'reason' => $this->reason
    		));
    		break;
    	}
    }
    
    public function verifyconfirmoutput($request) {
        $error=2;
        
       // check if PID exist
	    $PID = $request["PID"];
        $idnumber = $request["userinfo"]["nik"];
        
        $piddb = new CApid($PID);
        $piddb->fetchPIDDB();
        
        if ($piddb->isExist()) {
            $error = 0;
        } else {
            $error = 2;
            $this->reason = "cannot find PID";
        }
        
        //send response to SI
       switch ($error) {
    	case 0:
            echo json_encode(array(	'success' => true,
            						'PID' => $PID
    		));
    		break;
    	case 1:
    		echo json_encode(array(	'success' => false,
                                    'reason' => "Cannot find user information"
    		));
    	default:
    		echo json_encode(array(	'success' => false,
    					'reason' => $this->reason
    		));
    		break;
    	}
    	
    	//return form
    	if ($error == 0) {
    	    $data = json_decode($piddb->getPID(),true);
    	    return json_encode(array(	'success' => true,
            						    'PID' => $PID,
                                        'projectid' => $data["projectid"],
            						    'userinfo' => $data["userinfo"]
    		));
    	} else {
    	    return null;
    	}
    }
    
    public function documentreq($request) {
        global $SIdocument;
        $error=3;    
        
        $idnumber = $request["signerid"];
        
        //get user info
        $user = new CAuser($idnumber);
	    
	    if ($user->isRegistered()) {
	        $userinfo = $user->getUserInfowithSignature();
    		$deviceid = $user->getUserDevice();
    	    $error=0;
    	} else {
            $error=1;
    	}
    	
    	if ($error==0) {
    		//send request to SI
    		$document = array("fileurl" => $request["fileurl"], "filehash" => $request["filehash"], "documentname" => $request["documentname"]);
    		
    		$req = (object) array("userinfo" => $userinfo, "deviceid" => $deviceid, "message" => $request["message"], "callback" => $request["callback"], "documentnumber" => $request["documentnumber"], "document" => $document);
    		$req = json_encode($req);
        	$result = sendjson($req,$SIdocument);
        	
        	$result = json_decode($result, true);
        	if ($result) {
                if ($result["success"]) {
                    $PID = $result["PID"];
                    $this->PID = $PID;
                    
                    //save PID to DB
                    $current_date = new DateTime("now");
                	$time = $current_date->format('Y-m-d H:i:s');
                	$key=getkey($time);
                	//encrypt PID
            	    $result = encryptdb(json_encode($req),$key);
                	$data = $result[0];
                	$iv = $result[1];
                	
                    $piddb = new CApid($PID);
                    $pidresult = $piddb->storePIDDB($PID,$data,$time,$iv);
                    
                    $error=0;
                }
                else {
                    $this->reason = $result->reason;
                    $error=2;
                }        	    
        	} else {
        	    $this->reason = "Cannot connect to SI";
        	    $error = 2;
        	}
    	}
    	return $error;
    }

    public function documentreqoutput($error) {
       switch ($error) {
    	case 0:
    		//send pubkey 
            return json_encode(array(	'success' => true,
            						'PID' => $this->PID
    		));
    		break;
    	case 1:
    		return json_encode(array(	'success' => false,
                                    'reason' => "Cannot find user information"
    		));
    	default:
    		return json_encode(array(	'success' => false,
    					'reason' => $this->reason
    		));
    		break;
    	}
    }
    
    public function documentverify($request) {
        $error = 3;
        
        $idnumber = $request["idnumber"];
    	$signedhash = $request["signedhash"];
    	$signedtime = $request["signedtime"];
    	$signature = base64_decode($request["signature"]);
    	
        $user = new CAuser($idnumber);
        if ($user->isRegistered()) {
            $publickey = $user->getPublicKey();
            $message = "hash: ".$signedhash." time: ".$signedtime." WIB";
            $pub = openssl_pkey_get_public($publickey);
    	} else {
            $this->reason = "User is not registered";
    	}
    	
        if (isset($pub)) {
            $r = openssl_verify($message, $signature, $pub, "sha256WithRSAEncryption");
            if ($r) {
                 $this->result = "Data ".$signedhash." signed by ".$idnumber." at time: ".$signedtime." WIB";
    	        $error=0;    
            } else {
                $this->reason = "Signature is not valid";
            }
        }
    	
    	return $error;
    }

    public function documentverifyoutput($error) {
        switch ($error) {
    	case 0:
    		//send pubkey 
            return json_encode(array(	'success' => true,
                        'result' => $this->result
    		));
    		break;
        default:
    		return json_encode(array(	'success' => false,
    					'reason' => $this->reason
    		));
    		break;
    	}
    }
}