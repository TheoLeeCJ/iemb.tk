<?php

function curl($url, $method, $headers, $sessid = false, $postfields = null, $anticompetitivebehavior = null) {
	$ch = curl_init($url);
	$header = [];
	switch ($method) {
		case 'get':
		case 'GET':
			break;

		case 'post':
		case 'POST':
			curl_setopt($ch, CURLOPT_POST, true);
			$header[] = 'Content-type: application/x-www-form-urlencoded';
			curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postfields));
			break;

		case 'head':
		case 'HEAD':
			curl_setopt($ch, CURLOPT_NOBODY, true);
			break;
	}
	if ($headers) curl_setopt($ch, CURLOPT_HEADER, true);
	if ($url != 'https://iemb.hci.edu.sg/') {
		if ($anticompetitivebehavior) {
			if (!empty($_COOKIE['authtoken']))
				$header[] = 'Cookie: ASP.NET_SessionId=' . $sessid . '; __RequestVerificationToken=' . $anticompetitivebehavior . '; AuthenticationToken=' . $_COOKIE['authtoken'];
			else
				$header[] = 'Cookie: ASP.NET_SessionId=' . $sessid . '; __RequestVerificationToken=' . $anticompetitivebehavior;
		}
		else {
			if (!empty($_COOKIE['authtoken'])) {
				$header[] = "Cookie: ASP.NET_SessionId=$sessid;AuthenticationToken={$_COOKIE['authtoken']}";
			}
			else
				$header[] = 'Cookie: ASP.NET_SessionId=' . $sessid;
		}
	}
	if (count($header) > 0) curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
	curl_setopt($ch, CURLOPT_REFERER, "https://iemb.hci.edu.sg");
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
	curl_setopt($ch, CURLOPT_VERBOSE, true);

	$return = curl_exec($ch);
	curl_close($ch);
	return $return;
}
