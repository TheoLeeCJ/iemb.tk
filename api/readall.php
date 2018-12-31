<?php
ignore_user_abort(true);
set_time_limit(0);

if (empty($_POST['sessid']) || empty($_POST['messages'])) {
	header('HTTP/2.0 400 Bad Request');
	exit(json_encode([
		'success' => false,
		'error' => 'missing'
	]));
}
ob_start();

echo json_encode([
	'success' => true,
	'content' => 'processing'
]);
header('Connection: close');
header('Content-Length: '. ob_get_length());
ob_end_flush();
ob_flush();
flush();

$messages = json_decode($_POST['messages'], true);
$mh = curl_multi_init();
foreach ($messages as $message) {
	$ch = curl_init('https://iemb.hci.edu.sg/Board/content/' . $message['id'] . '?board=' . $message['board'] . '&isArchived=False');
	curl_setopt($ch, CURLOPT_HTTPHEADER, ['Cookie: ASP.NET_SessionId=' . $_POST['sessid']]);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,  2);
	curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
	curl_multi_add_handle($mh, $ch);
}

$active = null;
do $mrc = curl_multi_exec($mh, $active);
while ($mrc == CURLM_CALL_MULTI_PERFORM);

while ($active && $mrc == CURLM_OK) {
	if (curl_multi_select($mh) == -1) usleep(100);
	do $mrc = curl_multi_exec($mh, $active);
	while ($mrc == CURLM_CALL_MULTI_PERFORM);
}

curl_multi_close($mh);