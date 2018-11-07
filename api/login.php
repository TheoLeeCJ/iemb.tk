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

foreach (explode(PHP_EOL, curl('https://iemb.hci.edu.sg/home/login', 'POST', true, false, ['username' => $_POST['username'], 'password' => $_POST['password']])) as $value) {
	if (strpos($value, 'Set-Cookie') === 0) 
		if (substr($value, 12, 17) === 'ASP.NET_SessionId') 
			$sessid = substr($value, 30, 24);
}

if ($sessid === 'fail') 
	echo json_encode([
		'success' => false,
		'error' => 'credentials'
	]);
else echo json_encode([
	'success' => true,
	'content' => $sessid
]);