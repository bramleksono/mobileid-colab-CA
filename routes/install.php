<?php

$app->get('/install', function () use($app, $twig) {

	$configfile = 'config.json';
	if (file_exists($configfile)) {
		echo "Aplikasi sudah dikonfigurasi";
		exit(1);
	}

	$login=array(
		'notcomplete' => TRUE,
	    'pagetitle' => 'Pemasangan - MobileID CA',
	    'heading' => 'Aplikasi MobileID CA',
		'subheading' => 'Masukkan alamat RA untuk memulai proses pemasangan aplikasi',
		'license' => 'Aplikasi MobileID CA',
		'year' => '2015',
		'author' => 'Bramanto Leksono',
	);
		
	echo $twig->render('install.tmpl',$login );
});

$app->post('/install', function () use($app, $twig) {
	$configfile = 'config.json';
	if (file_exists($configfile)) {
		echo "Aplikasi sudah dikonfigurasi";
		exit(1);
	}

	$RAaddr = $app->request()->post("RAaddress");
	
	if (!isset($RAaddr))
		//RA address cannot be empty
		$app->redirect('/install');

	//get random number
	$i=12;
	$bytes = openssl_random_pseudo_bytes($i, $cstrong);
	$hex   = bin2hex($bytes);
	//TODO get address from RA

    
	$config = array("config" => array(
									"random" => $hex,
									"RAaddr" => $RAaddr,
									));
	$config = json_encode($config);

	file_put_contents($configfile, $config);

	$login=array(
	    'pagetitle' => 'Pemasangan - MobileID CA',
	    'heading' => 'Aplikasi MobileID CA',
		'subheading' => 'Pemasangan selesai. CA siap digunakan.',
		'license' => 'Aplikasi MobileID CA',
		'year' => '2015',
		'author' => 'Bramanto Leksono',
	);
		
	echo $twig->render('install.tmpl',$login );
});
