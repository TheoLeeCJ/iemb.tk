<?php
	require 'curl.php';
	if (empty($_POST['sessid'])) {
		header('HTTP/2.0 400 Bad Request');
		exit(json_encode([
			'success' => false,
			'error' => 'missing'
		]));
	}
	$res = trim(strtok(curl('https://iemb.hci.edu.sg/Board/BoardList', 'HEAD', true, $_POST['sessid'], null, 2), PHP_EOL));
	if ($res === 'HTTP/1.1 200 OK') 
		echo json_encode([
			'success' => true,
			'content' => true
		]);
	elseif ($res === 'HTTP/1.1 302 Found') 
		echo json_encode([
			'success' => true,
			'content' => false
		]);
	else 
		echo json_encode([
			'success' => false,
			'error' => $res
		]);
?>