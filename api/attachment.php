<?php
if (empty($_GET['board']) || empty($_GET['message']) || empty($_GET['name'])) {
	header('HTTP/2.0 400 Bad Request');
	exit(json_encode([
		'success' => false,
		'error' => 'missing'
	]));
}
if (empty($_GET['sessid']) && empty($_COOKIE['sessid'])) {
	header('HTTP/2.0 400 Bad Request');
	exit(json_encode([
		'success' => false,
		'error' => 'missing'
	]));
}
$sessid = empty($_GET['sessid']) ? $_COOKIE['sessid'] : $_GET['sessid'];

require 'curl.php';

$file = curl('https://iemb.hci.edu.sg/Board/ShowFile?t=2&ctype=1&id=' . $_GET['message'] . '&file=' . urlencode($_GET['name']) . '&boardId=' . $_GET['board'], 'GET', false, $sessid);

header('Content-Description: File Transfer');
header('Content-Type: ' . (new finfo(FILEINFO_MIME_TYPE))->buffer($file));
header('Content-Disposition: attachment; filename="' . $_GET['name'] . '"');
header('Content-Length: ' . strlen($file));
header('Cache-Control: must-revalidate');
header('Expires: 0');
echo $file;