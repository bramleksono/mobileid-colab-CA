<?php
require 'CApid.class.php'; // Handling CA PID interaction with database
require 'CAuser.class.php';  // Handling User Class
require 'crypt.php';  // Handling cryptographic function
require 'addstruct.php';  // Construct client address
require 'sending.php';  // Handling sending http request function

class CAcontroller {
    // Message Section
    
    private function constmessagetoSI($data) {
        return array(
        				'meta' => array(
        								'purpose' => 'sendmessage'),
        				'userinfo' => array(
        								'deviceid' => $data->userinfo->deviceid,
        								'message' => $data->userinfo->message)
                    );
    }
    
    public function messagereq($request) {
        global $SImessaging;
        $error = 3;
        
        $idnumber = $request->userinfo->nik;
        $user = new CAuser($idnumber);
        if ($user->isRegistered()) {
    		$deviceid = $user->getUserDevice();
    	    $error=0;
    	} else {
            $error=1;
    	}
    	
    	if ($error==0) {
    	    $reg = (object) array("userinfo" => (object) array("deviceid" => $deviceid, "message" => $request->userinfo->message));
        	$reg = $this->constmessagetoSI($reg);
        	$reg = json_encode($reg);
        	$result =sendjson($reg,$SImessaging);
        	$result = json_decode($result);
            if ($result->success) {
                $error=0;
            }
            else {
                $this->reason = $result->reason;
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
    
    // Login Section
    
    public function loginreq($request) {
        global $SIlogin;
        $error=3;
        //construct message to user
        $message = "Login request for Mobile ID website";
        $idnumber = $request->userinfo->nik;
	    $callback = $request->callback;
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
        	if ($result) {
                if ($result->success) {
                    $PID = $result->PID;
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
    
    public function loginreqoutput($error) {
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

    public function loginconfirmoutput($request) {
        $error=2;
        
       // check if PID exist
	    $PID = $request->PID;
        
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
    	    return json_encode(array(	'success' => true,
            						    'PID' => $PID,
    		));
    	} else {
    	    return null;
    	}
    }
    
    // Verification Section
    
    public function verifyreq($request) {
        global $SIverify;
        $error=3;
        
        $idnumber = $request->userinfo->nik;
	    $callback = $request->callback;
	    $message = $request->message;
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
        	$result =sendjson($req,$SIverify);
        	
        	$result = json_decode($result);
        	if ($result) {
                if ($result->success) {
                    $PID = $result->PID;
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
            echo json_encode(array(	'success' => true,
            						'PID' => $this->PID
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
    }
    
    public function verifyconfirmoutput($request) {
        $error=2;
        
       // check if PID exist
	    $PID = $request->PID;
        $idnumber = $request->userinfo->nik;
        
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
    	    $data = json_decode($piddb->getPID());
    	    return json_encode(array(	'success' => true,
            						    'PID' => $PID,
            						    'userinfo' => $data->userinfo
    		));
    	} else {
    	    return null;
    	}
    }
}