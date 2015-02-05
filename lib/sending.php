<?php

function sendjson($data,$url) {
	//curl less method
	$options = array(
		'http' => array(
			'header'  => "Content-type: application/json\r\n",
			'method'  => 'POST',
			'content' => $data,
		),
	);

	$context  = stream_context_create($options);
	return $result = @file_get_contents($url, false, $context);
}
