<?php

$_NOHEAD=true;

function over() {
    echo "";
    exit();
//    redir(root().$_DEFAULTPAGE);
}


if(!$_SESSION->user) over();

switch($ARGC) {
case 2:
    switch($ARGV[0]) {
    case "form":
	$at=$ARGV[1];
	if($at=="0") {
	    echo "";
	    break;
	}
	$alrm=c_alarm_gen::getalarmbyname($at);
	if(!$alrm) {
	    echo "Neznámý typ alarmu<br />";
	    break;
	}
	$alrm->showform();
	break;
    default:
	over();
    }
    break;
default:
    over();
}

