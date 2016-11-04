<?php
//
// Simple concurrent PHP POST data uploader with gzip decoding and custom HTTP header.
//
// X-Location: <location>-<ISO datetime>
//

if(!$_SERVER['HTTP_X_LOCATION']) {
	header('HTTP/1.0 400 Bad Request', true, 400);
	exit();
}

$datapath = '/var/www/sensors/data/';
$location = $_SERVER['HTTP_X_LOCATION'];

$postdata = file_get_contents("php://input");

file_put_contents($datapath . $location . '.csv.tmp', gzdecode($postdata));

rename($datapath . $location . '.csv.tmp', $datapath . $location . '.csv');

?>
