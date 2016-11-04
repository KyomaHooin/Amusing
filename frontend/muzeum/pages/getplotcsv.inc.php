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
header("Content-length: ".filesize($_CSVDIR.$bn[0]));

readfile($_CSVDIR.$bn[0]);
