<?php
if (empty($_GET['message']) || empty($_COOKIE['sessid'])) {
	header('HTTP/2.0 400 Bad Request');
	exit(json_encode([
		'success' => false,
		'error' => 'missing'
	]));
}

$sessid = empty($_GET['sessid']) ? $_COOKIE['sessid'] : $_GET['sessid'];

require 'curl.php';
$dom = new DOMDocument();


$result = empty($_GET['board']) ? 
	curl('https://iemb.hci.edu.sg/Board/content/' . $_GET['message'] . '?board=1048&isArchived=False', 'GET', true, $sessid) :
	curl('https://iemb.hci.edu.sg/Board/content/' . $_GET['message'] . '?board=' . $_GET['board'] . '&isArchived=False', 'GET', true, $sessid);

if (substr($result, 9, 9) === '302 Found') 
	exit(json_encode([
		'success' => false,
		'error' => 'expired'
	]));

libxml_use_internal_errors(true);
$dom->loadHTML('<!DOCTYPE html>' . substr($result, 340));
libxml_clear_errors();

$metadata = [];
$metadata_element = $dom->getElementById('masterContent')->getElementsByTagName('div')->item(3)->getElementsByTagName('div');
$metadata['title'] = substr(trim($metadata_element->item(0)->textContent), 8);
$metadata['sender'] = substr(trim($metadata_element->item(1)->textContent), 7);
$metadata['datetime'] = trim($metadata_element->item(3)->textContent);


$message = $dom->getElementById('hyplink-css-style');

if ($imgs = $message->getElementsByTagName('img')) {
	foreach ($imgs as $img) {
		if (substr($img->getAttribute('src'), 0, 11) != 'data:image/') {
			$file = curl(substr($img->getAttribute('src'), 0, 4) !== 'http' ? 'https://iemb.hci.edu.sg/Board/content/' . $img->getAttribute('src') : $img->getAttribute('src'), 'GET', false, $sessid);
			$f = finfo_open();
			$imgtype = finfo_buffer($f, $file, FILEINFO_MIME_TYPE);
			$base = base64_encode($file);
			$img->setAttribute('src', 'data:' . $imgtype . ';base64,' . $base);
		}
	}
}
if ($frames = $message->getElementsByTagName('iframe')) {
	while ($frames->length) {
		$frame = $frames->item(0);
		$url = $frame->getAttribute('src');
		
		$link_div = $dom->createElement('div');
		$link = $dom->createElement('a');
		$link->setAttribute('href', $url);
		$link->setAttribute('target', '_blank');
		$link->setAttribute('rel', 'noopener noreferrer');
		$pretext = substr($url, 0, 29) === 'https://docs.google.com/forms' ? 'Google Form: ' : 'Link: ';
		$text = new DOMText($pretext . $url);
		$link->appendChild($text);

		$link_div->appendChild($link);
		$frame->parentNode->replaceChild($link_div, $frame);
	}
}
$xpath = new DOMXPath($dom);
$nodes = $xpath->query('//*[@style]');
foreach ($nodes as $node) $node->removeAttribute('style');
$message = trim(preg_replace('/\s+/', ' ', html_entity_decode($dom->saveHTML($message))));
$form = [
	'present' => false
];
if ($form_element = $dom->getElementById('replyForm')) {
	$selected = 'NONE';
	$text = '';
	foreach (range('A', 'E') as $key => $letter) {
		if ($form_element->getElementsByTagName('input')->item($key + 3)->hasAttribute('checked')) {
			$selected = $letter;
			break;
		}
	}
	$comment = '';
	if ($form_element->getElementsByTagName('textarea')->item(0)->textContent != '') 
		$comment = $form_element->getElementsByTagName('textarea')->item(0)->textContent;
	$form['present'] = true;
	$form['selected'] = $selected;
	$form['comment'] = $comment;
	$form['replyto'] = $dom->getElementById('replyto')->getAttribute('value');
}
$attachments = [];
if ($dom->getElementById('attaches')) {
        preg_match_all("/addConfirmedChild\('attaches','(.+?)'/", $result, $attachments);
        $attachments = $attachments[1];
}

echo json_encode([
	'success' => true,
	'content' => [
		'metadata' => $metadata,
		'message' => $message,
		'form' => $form,
		'attachments' => $attachments
	]
]);
