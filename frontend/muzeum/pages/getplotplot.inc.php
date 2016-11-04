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

if(!get_ind($_SESSION->plot_outputs,$bn[0]) || get_ind($bn,1)!="plot" || !is_file($_PLOTDIR.$bn[0])) {
    header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
    exit();
}

header("Content-type: text/plain");
header("Content-length: ".filesize($_PLOTDIR.$bn[0]));

$ol=ob_get_level();
for($i=$ol;$i--;) ob_end_flush();

readfile($_PLOTDIR.$bn[0]);

for($i=$ol;$i--;) ob_start();
