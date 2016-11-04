<?php

$_NOHEAD=true;

if(!$ARGC || !$_SESSION->user) {
    header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
    exit();
}

$qe=$SQL->query("select * from image where img_id=\"".$SQL->escape($ARGV[0])."\"");
$fe=$qe->obj();

if(!$fe) {
    header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
    exit();
}

if($ARGC>1) {
    switch(get_ind($ARGV,1)) {
    case "max":
	$maxw=get_ind($ARGV,2);
	$maxh=get_ind($ARGV,3);
	if($ARGC!=4 || !is_numeric($maxh) || !is_numeric($maxw)) {
	    header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
	    exit();
	}
	$img=imagecreatefromstring($fe->img_data);
	if(!$img) {
	    header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
	    exit();
	}
	resizeimg($img,$maxw,$maxh);
	ob_start();
	imagejpeg($img);
	$output=ob_get_clean();
	break;
    default:
	header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
	exit();
    }
} else {
    $output=$fe->img_data;
}

header("Content-type: image/jpeg");
header("Content-length: ".strlen($output));

echo $output;
