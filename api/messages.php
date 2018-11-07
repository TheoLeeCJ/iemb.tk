<?php
if (empty($_GET['board'])) {
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
$dom = new DOMDocument();

$result = curl('https://iemb.hci.edu.sg/Board/Detail/' . $_GET['board'], 'GET', true, $sessid);

if (substr($result, 9, 9) === '302 Found') 
	exit(json_encode([
		'success' => false,
		'error' => 'expired'
	]));

libxml_use_internal_errors(true);
$dom->loadHTML('<!DOCTYPE html>' . substr($result, 349));
libxml_clear_errors();

$messages = [];
$message_count = 0;
foreach ($dom->getElementById('tab_table')->getElementsByTagName('tr') as $key => $row) {
	if ($key === 0) continue;
	if (trim($row->getElementsByTagName('td')->item(0)->textContent) === 'No Record Found!') break;
	$header = $row->getElementsByTagName('td')->item(2);
	$messages[$message_count]['title'] = trim($header->textContent);
	$messages[$message_count]['attachments'] = $header->getElementsByTagName('i')->item(0) ? true : false;
	$messages[$message_count]['id'] = substr($header->getElementsByTagName('a')->item(0)->getAttribute('href'), 15, -28);
	$messages[$message_count]['sender'] = trim(substr($row->getElementsByTagName('td')->item(1)->textContent, 0, -112));
	$date = trim($row->getElementsByTagName('td')->item(0)->textContent);
	$messages[$message_count]['date'] = substr($date, 0, 2) . ' ' . substr($date, 3, 3) . ' 20' . substr($date, 7, 2);
	$messages[$message_count]['unread'] = true;
	$messages[$message_count]['board'] = $_GET['board'];
	$message_count++;
}

foreach ($dom->getElementById('tab_table1')->getElementsByTagName('tr') as $key=>$row) {
	if ($key === 0) continue;
	if (trim($row->getElementsByTagName('td')->item(0)->textContent) === 'No Record Found!') break;
	$header = $row->getElementsByTagName('td')->item(2);
	$messages[$message_count]['title'] = trim($header->textContent);
	$messages[$message_count]['attachments'] = $header->getElementsByTagName('i')->item(0) ? true : false;
	$messages[$message_count]['id'] = substr($header->getElementsByTagName('a')->item(0)->getAttribute('href'), 15, -28);
	$messages[$message_count]['sender'] = trim(substr($row->getElementsByTagName('td')->item(1)->textContent, 0, -112));
	$date = trim($row->getElementsByTagName('td')->item(0)->textContent);
	$messages[$message_count]['date'] = substr($date, 0, 2) . ' ' . substr($date, 3, 3) . ' 20' . substr($date, 7, 2);
	$messages[$message_count]['unread'] = false;
	$messages[$message_count]['board'] = $_GET['board'];
	$message_count++;
}

usort($messages, function($a, $b) {
	if ($a['date'] === $b['date']) return 0;
	$months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
	if (array_search(substr($a['date'], 3), $months) > array_search(substr($b['date'], 3), $months)) return -1;
	if (array_search(substr($a['date'], 3), $months) < array_search(substr($b['date'], 3), $months)) return 1;
	if (substr($a['date'], 0, 2) > substr($b['date'], 0, 2)) return -1;
	else return 1;

});

echo json_encode([
	'success' => true,
	'content' => $messages
]);