<?php

$_NOHEAD=true;

function over() {
    echo "";
    exit();
//    redir(root().$_DEFAULTPAGE);
}

if(!$_SESSION->user) over();

function rin1test() {
    $url=get_ind($_POST,"001_sen_ringo1_uri");
    if(!preg_match("/^https?\\:\\/\\/.*/",$url)) {
	echo "Invalidní adresa";
	return;
    }
    $slist=file_get_contents($url);
    if($slist===false) {
	echo "Nelze navázat spojení";
	return;
    }
    $ls=explode("\n",$slist);
    if(trim($ls[0])!="ok") {
	echo "Chyba na straně Ringo1";
	return;
    }
    array_shift($ls);
    if(!count($ls)) {
	echo "Žádný senzor k dispozici";
	return;
    }
    echo "Dostupné senzory a veličiny:";
    echo "<ul>";
    foreach($ls as $sens) {
	$s=trim($sens);
	if(!strlen($s)) continue;
	echo "<li>".htmlspecialchars($s);
	$vars=rin1getvars($url,$s);
	if($vars===false || !is_array($vars)) echo "<br />Nelze získat veličiny";
	else {
	    if(!count($vars)) echo "<br />Žádná veličina k dispozici";
	    else {
		echo "<ul>";
		foreach($vars as $v) echo "<li>".htmlspecialchars($v)."</li>";
		echo "</ul>";
	    }
	}
	echo "</li>";
    }
    echo "</ul>";
}

if($_SERVER['REQUEST_METHOD']=="POST") {
    switch($ARGC) {
    case 1:
	switch($ARGV[0]) {
	case "rin1test":
	    rin1test();
	    break;
	}
	break;
    }
} else {

switch($ARGC) {
case 2:
    switch($ARGV[0]) {
    case "form":
	$at=$ARGV[1];
	if($at=="0") {
	    echo "";
	    break;
	}
	$st=c_stype_base::getsensorbyid($at);
	if(!$st) {
	    echo "typ nenalezen";
	    break;
	}
	$st->showform(false);
	break;
    case "newform":
	$at=$ARGV[1];
	if($at=="0") {
	    echo "";
	    break;
	}
	$st=c_stype_base::getsensorbyid($at);
	if(!$st) {
	    echo "typ nenalezen";
	    break;
	}
	$st->showform(true);
	break;
    default:
	over();
    }
    break;
default:
    over();
}

}
