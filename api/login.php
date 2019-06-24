<?php
if (empty($_POST['username']) || empty($_POST['password'])) {
	header('HTTP/2.0 400 Bad Request');
	exit(json_encode([
		'success' => false,
		'error' => 'missing'
	]));
}

require 'curl.php';

$sessid = 'fail';
$curlresult = curl('https://iemb.hci.edu.sg/home/login', 'POST', true, false, ['username' => $_POST['username'], 'password' => $_POST['password']], 3);

foreach (explode(PHP_EOL, $curlresult) as $value) {
	if (strpos($value, 'Set-Cookie') === 0) {
		if (substr($value, 12, 17) === 'ASP.NET_SessionId') 
			$sessid = substr($value, 30, 24);
	}
	
	if (strpos($value, 'Location') === 0) {
		if (strpos($value, 'Failed') !== false) 
			$sessid = 'incorrect';
	}
}

if ($sessid === 'fail') 
	echo json_encode([
		'success' => false,
		'error' => 'server'
	]);
elseif ($sessid === 'incorrect')
	echo json_encode([
		'success' => false,
		'error' => 'credentials'
	]);
else echo json_encode([
	'success' => true,
	'content' => $sessid
]);