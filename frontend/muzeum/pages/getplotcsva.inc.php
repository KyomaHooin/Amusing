<?php

$_NOHEAD=true;

if(!$_SESSION->user) {
    header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
    exit();
}
if(!$ARGC) {
    header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
    exit();
}

$bn=explode(".",$ARGV[0]);

if(!get_ind($_SESSION->csv_outputs,$bn[0]) || get_ind($bn,1)!="csv" || !is_file($_CSVDIR.$bn[0])) {
    header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
    exit();
}

header("Content-type: text/csv");
//header("Content-length: ".filesize($_CSVDIR.$bn[0]));

$ol=ob_get_level();
for($i=$ol;$i--;) ob_end_flush();

$out=fopen($_CSVDIR.$bn[0],"r");
if($out) {
    while(($ln=fgets($out))) {
	$l=explode(";",$ln);
	if(count($l)>=2) {
	    $l[0]=gmdate("Y-m-d H:i:s",$l[0]+3600);
	    $l[1]=strtr($l[1],".",",");
	    echo implode(";",$l);
	} else echo $ln;
    }
    fclose($out);
}

//readfile($_CSVDIR.$bn[0]);

for($i=$ol;$i--;) ob_start();
