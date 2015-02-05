<?php
//Routes untuk memperbarui address

$app->get('/checkaddress', function () use($twig) {
	global $configfile;
	//ambil address RA dari file config
	$config = json_decode(file_get_contents($configfile));
	
	//ambil daftar alamat dari RA
	$clientRAaddr = file_get_contents($config->config->RAclientcheck);
	//simpan daftar di file
	global $addressfile;
	$clientaddr = file_get_contents($addressfile);
	
	$pageinfo=array(
			'pagetitle' => 'Cek Alamat Client - MobileID CA',
			'heading' => 'Aplikasi MobileID CA',
			'license' => 'Aplikasi MobileID CA',
			'year' => '2015',
			'author' => 'Bramanto Leksono',
	);
	if (strcmp($clientRAaddr,$clientaddr) != 0) {
		echo "File not equal";
		file_put_contents($addressfile, $clientRAaddr);
		//tampilkan hasil
		$pageinfo["subheading"] = 'Alamat Client diperbarui dengan informasi CA.';
			
		echo $twig->render('install.tmpl',$pageinfo );
	}
	else {
		$pageinfo["subheading"] = 'Alamat client sudah benar.';
		echo $twig->render('install.tmpl',$pageinfo );
	}
});
