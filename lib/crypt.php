<?php

function strtohex($x) {
	$s='';
    foreach (str_split($x) as $c) $s.=sprintf("%02X",ord($c));
    return($s);
} 

function getkey($pin) {
	$host= gethostname();
	$ip = gethostbyname($host);
	
	$configfile = 'config.json';
	$config = file_get_contents($configfile);
	$config = json_decode($config, true);
	$hex = $config["config"]["random"];
	
	$pphrase = $host.$ip.$hex.$pin;
	return hash('sha256', $pphrase, true);
}

function encryptdb($source,$key) {
	$enc = MCRYPT_RIJNDAEL_128;
	$mode = MCRYPT_MODE_CBC;
	$iv = mcrypt_create_iv(mcrypt_get_iv_size($enc, $mode), MCRYPT_DEV_URANDOM);
	
	$encrypted = mcrypt_encrypt($enc, $key, $source, $mode, $iv);
	$decrypted = mcrypt_decrypt($enc, $key, $encrypted, $mode, $iv);

	return array($encrypted,$iv);
}

function decryptdb($source,$iv,$key) {
	$enc = MCRYPT_RIJNDAEL_128;
	$mode = MCRYPT_MODE_CBC;
	$decrypted = mcrypt_decrypt($enc, $key, $source, $mode, $iv);

	return $decrypted;
}
