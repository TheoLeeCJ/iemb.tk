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

$badschoolplatform = curl('https://iemb.hci.edu.sg/', 'GET', true);
$cowards = substr(explode(PHP_EOL, $badschoolplatform)[89], 82, -6);
$str_start = strpos($badschoolplatform, 'Set-Cookie: __RequestVerificationToken=') + 39;
$iemb = substr($badschoolplatform, $str_start, strpos($badschoolplatform, ';', $str_start) - $str_start);

foreach (explode(PHP_EOL, curl('https://iemb.hci.edu.sg/home/login', 'POST', true, false, ['username' => $_POST['username'], 'password' => $_POST['password'], '__RequestVerificationToken' => $cowards]), $iemb) as $value) {
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