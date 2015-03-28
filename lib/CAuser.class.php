<?php

//Parse Backend
use Parse\ParseObject;
use Parse\ParseQuery;

class CAuser {
    public function CAuser($id){
		$this->ids = $id;
		$this->fetchUserDB();
	}
	
	private function fetchUserDB() {
	    $idnumber = $this->ids;
	    $ca_userdb_que = new ParseQuery("ca_userdb");
	    $ca_userdb_que->equalTo("nik", $idnumber);
	    $results = $ca_userdb_que->find();
	    if ($results) {
	    	$this->userDB = $results[0];
	    }
	    else $this->userDB = null;
	}
	
	private function decryptUserDB() {
	    $encrypted = $this->userDB;
	    $userinfo = utf8_decode($encrypted->get('userinfo'));
		$iv = utf8_decode($encrypted->get('iv'));
		$key = getkey($encrypted->get('created'));
		return decryptdb($userinfo,$iv,$key);
	}
	
	public function isRegistered() {
	    $result = $this->userDB;
	    if (empty($result)) {
	        return false;
	    } else {
	        return true;
	    }
	}
	
	public function getUserInfo() {
        $userinfo = json_decode($this->decryptUserDB(),true);
		unset($userinfo["signature"]);
		return $userinfo;
	}
	
	public function getUserInfowithSignature() {
        $userinfo = json_decode($this->decryptUserDB(),true);
		return $userinfo;
	}
	
	public function getUserDevice() {
		return $this->userDB->deviceid;
	}
	
	public function getPublicKey() {
		return $this->userDB->pubkey;
	}	
}