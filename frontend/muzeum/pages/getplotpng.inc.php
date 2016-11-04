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

if(!get_ind($_SESSION->plot_outputs,$bn[0]) || get_ind($bn,1)!="png" || !is_file($_PLOTDIR.$bn[0])) {
    header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
    exit();
}

header("Content-type: image/png");
header("Content-length: ".filesize($_PLOTDIR.$bn[0]));

readfile($_PLOTDIR.$bn[0]);
