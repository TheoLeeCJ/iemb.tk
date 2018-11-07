<?php
if (empty($_POST['sessid']) || empty($_POST['board']) || empty($_POST['message'])) {
	header('HTTP/2.0 400 Bad Request');
	exit(json_encode([
		'success' => false,
		'error' => 'missing'
	]));
}

require 'curl.php';

$result = curl('https://iemb.hci.edu.sg/board/ProcessResponse', 'POST', true, $_POST['sessid'], [
	'boardid' => $_POST['board'],
	'topic' => $_POST['message'],
	'replyto' => isset($_POST['replyto']) ? $_POST['replyto'] : 0,
	'replyContent' => isset($_POST['comment']) ? $_POST['comment'] : '',
	'UserRating' => isset($_POST['selection']) ? $_POST['selection'] : '',
	'PostMessage' => 'Post+Reply'
]);
if (substr($result, 84, 28) === 'Location: /board/Detail/1050') 
	echo json_encode([
		'success' => true,
	]);
else 
	echo json_encode([
		'success' => false,
		'error' => 'expired'
	]);