<?php
if (empty($_POST['username']) || empty($_POST['password'])) {
	header('HTTP/2.0 400 Bad Request');
	exit(json_encode([
		'success' => false,
		'error' => 'missing'
	]));
}

require 'curl.php';

$badschoolplatform = curl('https://iemb.hci.edu.sg/', 'GET', true);
preg_match("/<input name=\"__RequestVerificationToken\" .+? value=\"(.+?)\"/", $badschoolplatform, $cowards);
preg_match("/Set-Cookie: __RequestVerificationToken=(.*?);/", $badschoolplatform, $iemb);


if (preg_match("/Set-Cookie: ASP.NET_SessionId=(.*?);/", curl('https://iemb.hci.edu.sg/home/login', 'POST', true, false, ['username' => $_POST['username'], 'password' => $_POST['password'], '__RequestVerificationToken' => $cowards[1], 'submitbut' => 'Submit'], $iemb[1]), $sessid) !== false) {
	echo json_encode([
		'success' => true,
		'content' => [
			'sessid' => $sessid[1],
			'csrf' => $iemb[1] == NULL ? $_COOKIE['csrf'] : $iemb[1]
		]
	]);
}
else echo json_encode([
		'success' => false,
		'error' => 'credentials'
	]);