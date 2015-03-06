<?php
//require '../lib/crypt.php';  // Handling cryptographic function

$app->post('/signature/install', function () {
    echo "Hello";
    
    //generate CA key pair
    $keygen = new CASignature();
    $keypair = $keygen->keygenerator();
    //var_dump($keygen->getrsakeypair());
});

$app->post('/signature/create', function () use ($app) {
    //example query: {"data":{"callback":"http://postcatcher.in/catchers/54f7074cc895880300002ba1","PID":"625ae82c6b5502a08195389c93be6263f1c65185"}}
    $body = json_decode($app->request()->getBody());
    $data = json_encode($body->data);
    
    $sig = new CASignature();
    $sig->getconfig();
    $signature = $sig->createsig($data);

    //construct response
	header('Content-Type: application/json');
	if ($signature) {
	    $response = array(	'success' => true,
        				'signature' => $signature
        				);	    
	} else {
	    $response = array(	'success' => false,
        				);	  	    
	}
    echo json_encode($response);
});

$app->post('/signature/verify', function () use ($app) {
    //example query : {"data":{"callback":"http://postcatcher.in/catchers/54f7074cc895880300002ba1","PID":"625ae82c6b5502a08195389c93be6263f1c65185"},"signature":"`?v`Ó¤Ï;¡çãf=ß`\u0004Ë_¦~f\u000fÅnÉ!:ï\u0015\u001b\u001f\rtI,\u001bEf\u0011\fï\u001d^MóÇª«4]Ú4Ô6¢¦)Ñyæ:ÞÓ©¼Îá;'\u001eCDµÿ·¸è|Ã\u0010´îê«\f\u0010ö\u0015*\u000fcKà\u0007=ß<ÉiTH,\u000e@¿ú|ýT}Z!GBË$|#\r)]bó¿³}OQ ±±\u001döKkÁ8Å!Ì§`îãÙ\u0012C¡9\u0007ßX«Í¾ÌhP ­·e£OU&óy9\u0011ù\tò·án!Æ;÷½\u0010íÖüzðSã\u001b!_m\u0007)\u000f:;.@\u0019`1ÍS\u0001ô@Áeâª(ÿ\r2ômú_ÇJ1\u0017-üK1ïá@µ\u001e·ÜÐ\u001c}3]È³sÅ\u0003 Ú[gj&{Ãw\u000fÉÄòÎ\t\u0006\rÿ\u001fV\u0007Ë\t+>f^û{jár\u000eø\u0012<ÕµGrVOÅêjNË'³4®×üÿidQâ\u0018\u0015&Uøg§6êð'Gæû\u0006\u0002\u001cCmÿ/ö¾¶öN2õñq?¦Â7¼\u0013\ná\u000eôøa¡q\b\tf\u0001ÔM\u001c6%²£Ç-8\u0019ì.¯CHöà\u0000+Réù§QÌxD»f\u001czsg\u0016ýr4kO«æk\u0010\u0005­ø\u0016:.Û´lg\t&¢³\u001b'Tõ*½@(ßSü^}\u0006v,åb\u001b¶êÔ-·^\\¨=à"}
    $body = json_decode($app->request()->getBody());
    $data = json_encode($body->data);
    $signature = $body->signature;

    $sig = new CASignature();
    $sig->getconfig();
    $signature = $sig->verifysig($data,$signature);
    
    //construct response
	header('Content-Type: application/json');
	if ($signature) {
	    $response = array(	'success' => true
        				);	    
	} else {
	    $response = array(	'success' => false,
        				);	  	    
	}
    echo json_encode($response);    
});


//Parse Backend
use Parse\ParseObject;
use Parse\ParseQuery;
    
class CASignature{
    public function CASignature() {
        $this->config = array(
    		"digest_alg" => "sha512",
    		"private_key_bits" => 4096,
    		"private_key_type" => OPENSSL_KEYTYPE_RSA,
    	);
    } 
    
    private function getpassphrase() {
        $bytes = openssl_random_pseudo_bytes(128);
        return bin2hex($bytes);
    }
    
    private function getrsakeypair() {
        $pphrase = $this->getpassphrase();
    	
    	$res=openssl_pkey_new($this->config);
    	// Get private key
    	openssl_pkey_export($res, $privkey, $pphrase );
    	//var_dump($privkey);
    	
    	// Get public key
    	$pubkey=openssl_pkey_get_details($res);
    	//var_dump($pubkey);
    	$pubkey=$pubkey["key"];
        
    	//return as array. [passphrase,private,public]
    	return array($pphrase,$privkey,$pubkey);
    }
    
    private function removeexistingconfig() {
        $configfile = new ParseQuery("mobileid_config");
        $configfile->equalTo("service", "CA");
		$results = $configfile->find();
		//echo "Successfully retrieved " . count($results) . " scores.";
		// Do something with the returned ParseObject values
		for ($i = 0; $i < count($results); $i++) {
			$object = $results[$i];
			//echo "Object ".$object->getObjectId()." deleted.";
			$object->destroy();
			echo "Removing old config";
		}
    }
    
    public function getconfig() {
        $configfile = new ParseQuery("mobileid_config");
        $configfile->equalTo("service", "CA");
		$results = $configfile->find();
		if ($results) {
		     $decrypted = $this->decryptconfig($results[0]);
		     $this->configfile = $decrypted;
		    return true;
		} else {
		    $this->configfile = null;
		    return false;
		}
    }

	private function decryptconfig($encrypted) {
	    $data = utf8_decode($encrypted->get('data'));
		$iv = utf8_decode($encrypted->get('iv'));
		$key = getkey($encrypted->get('created'));
		$decrypted = decryptdb($data,$iv,$key);
		return json_decode($decrypted);
	}
	
    private function saveconfig($data) {
        $this->removeexistingconfig();
        $configfile_obj = new ParseObject("mobileid_config");
        
        //use time as key
		$current_date = new DateTime("now");
		$time = $current_date->format('Y-m-d H:i:s');
		$key=getkey($time);
		
		//encrypt config
		$encrypted = encryptdb($data,$key);
		
        $configfile_obj->set("service", "CA");
		$configfile_obj->set("data", utf8_encode($encrypted[0]));
		$configfile_obj->set("iv", utf8_encode($encrypted[1]));
		$configfile_obj->set("created", $time);
		
		try {
			$configfile_obj->save();
			$result=1;
			//retrieve registration code
			//$regcode = $si_userdb_obj->getObjectId();
			//echo 'New object created with objectId: ' . $regcode;
		} catch (ParseException $ex) {
			// Execute any logic that should take place if the save fails.
			// error is a ParseException object with an error code and message.
			// echo 'Failed to create new object, with error message: ' + $ex->getMessage();
			$result=0;
		}
		return $result;
    }
    
    public function keygenerator() {
        $pair = $this->getrsakeypair();
        $pair = (object) array( "passphrase" => $pair[0],
                                "privatekey" => utf8_encode($pair[1]),
                                "publickey" => utf8_encode($pair[2])
                );
        $jsonpair = json_encode($pair);
        
        $r = $this->saveconfig($jsonpair);
        if ($r) echo "Saving config success";
        else echo "Saving config failed";
    }
    
    public function createsig($data) {
        $configfile = $this->configfile;

        $passphrase = $configfile->passphrase;
        $privatekey = utf8_decode($configfile->privatekey);
            
        $key = openssl_pkey_get_private($privatekey, $passphrase);
        openssl_sign($data, $signature, $key, OPENSSL_ALGO_SHA512);
        return utf8_encode($signature);
    }
    
    public function verifysig($data,$signature) {
        $configfile = $this->configfile;

        $publickey = utf8_decode($configfile->publickey);
        $signature = utf8_decode($signature);
            
        $pub = openssl_pkey_get_public($publickey);
        $r = openssl_verify($data, $signature, $pub, "sha512WithRSAEncryption");
        if ($r) {
            return true;
        }
        else {
            echo "signature is not valid";
            return null;
        }        
    }
}