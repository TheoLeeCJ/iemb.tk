<?php
require 'curl.php';
if (empty($_POST['sessid'])) {
	header('HTTP/2.0 400 Bad Request');
	exit(json_encode([
		'success' => false,
		'error' => 'missing'
	]));
}
curl('https://iemb.hci.edu.sg/home/logout', 'HEAD', true, $_POST['sessid']);
echo json_encode([
	'success' => true
]);
?>