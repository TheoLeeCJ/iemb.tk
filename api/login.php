<?php
if (empty($_POST['username']) || empty($_POST['password'])) {
	header('HTTP/2.0 400 Bad Request');
	exit(json_encode([
		'success' => false,
		'error' => 'missing'
	]));
}

require 'curl.php';

$username = urlencode($_POST['username']);
$password = urlencode($_POST['password']);

$loginPageResponse = curl('https://iemb.hci.edu.sg/', 'GET', true);
preg_match("/<input name=\"__RequestVerificationToken\" .+? value=\"(.+?)\"/", $loginPageResponse, $tokenMatches);
preg_match("/Set-Cookie: __RequestVerificationToken=(.+?);/", $loginPageResponse, $cookieTokenMatches);
$veriToken = $tokenMatches[1];
$veriTokenCookie = $cookieTokenMatches[1];

$curl = curl_init();

$postData = "UserName=$username&Password=$password&__RequestVerificationToken=".urlencode($veriToken).'&submitbut=Submit';
$length = strlen($postData);

curl_setopt_array($curl, array(
	CURLOPT_URL => 'https://iemb.hci.edu.sg/home/login',
	CURLOPT_SSL_VERIFYPEER => 0,
	CURLOPT_SSL_VERIFYHOST => 2,
	CURLOPT_RETURNTRANSFER => 1,
	CURLOPT_FOLLOWLOCATION => 0,
	CURLOPT_POST => 1,
	CURLOPT_POSTFIELDS => $postData,
	CURLOPT_HEADER => 1,
	CURLOPT_USERAGENT => "User-Agent:  Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_2) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/13.0.4 Safari/605.1.15",
	CURLOPT_HTTPHEADER => array(
		"Referer:  https://iemb.hci.edu.sg/",
		"Origin:  https://iemb.hci.edu.sg",
		"Content-Type: application/x-www-form-urlencoded",
		"Accept:  text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8",
		"User-Agent:  Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_2) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/13.0.4 Safari/605.1.15",
		"Content-Length: $length",
		"Cookie: __RequestVerificationToken=$veriTokenCookie;.ASPXBrowserOverride=;"
	)
));

$redirectResponse = curl_exec($curl);
if ($redirectResponse === false)
	$redirectResponse = curl_error($curl);

curl_close($curl);

if (preg_match("/Set-Cookie: ASP.NET_SessionId=(.*?);/", $redirectResponse, $sessid) !== false) {
	if (preg_match("/Set-Cookie: AuthenticationToken=(.*?);/", $redirectResponse, $auth_token) !== false) {
		echo json_encode([
			'success' => true,
			'content' => [
				'sessid' => $sessid[1],
				'authtoken' => $auth_token[1]
			]
		]);
	}
}
else echo json_encode([
		'success' => false,
		'error' => 'credentials'
	]);