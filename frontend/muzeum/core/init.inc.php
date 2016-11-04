<?php

function __autoload($cname) {
    require_once "classes/".$cname.".inc.php";
}

if(get_magic_quotes_gpc()) {
//    $process=array(&$_GET,&$_POST,&$_COOKIE,&$_REQUEST);
    $process=array(&$_GET,&$_POST,&$_COOKIE);
    while(list($key,$val) = each($process)) {
	foreach($val as $k=>$v) {
	    unset($process[$key][$k]);
	    if(is_array($v)) {
		$process[$key][stripslashes($k)]=$v;
		$process[]=&$process[$key][stripslashes($k)];
	    } else {
		$process[$key][stripslashes($k)]=stripslashes($v);
            }
	}
    }
    unset($process);
}

require_once "config.inc.php";
require_once "functions.inc.php";

$SQL=new c_sql();

require_once "config_db.inc.php";
require_once "locals.inc.php";
require_once "datafunc.inc.php";
require_once "session.inc.php";

require_once "ringo1.inc.php"; // well.. a bit hc

/* -- post kody
000_ - nepovinny item
001_ - povinny text
002_ - povinny numero
003_ - povinny email
004_ - nepovinny email
005_ - nepovinny numero
*/

$ARGV=explode("?",$_SERVER['REQUEST_URI']);
if(strncmp($ARGV[0],$_ROOTPATH,strlen($_ROOTPATH))) redir(root().$_DEFAULTPAGE);
$ARGV=explode("/",substr($ARGV[0],strlen($_ROOTPATH)));

$pages=array(
    "main"=>true,
    "ajax"=>true,
    "ajaxmain"=>true,
    "login"=>true,
    "logout"=>true,
    "settings"=>true,
    "log"=>true,
    "user"=>true,
    "users"=>true,
    "usertab"=>true,
    "buildings"=>true,
    "buildingtab"=>true,
    "buildingacc"=>true,
    "image"=>true,
    "rooms"=>true,
    "roomtab"=>true,
    "roomacc"=>true,
    "materials"=>true,
    "materialtab"=>true,
    "measpoints"=>true,
    "measpointtab"=>true,
    "measpointacc"=>true,
    "measpointacc2"=>true,
    "sensors"=>true,
    "sensortab"=>true,
    "sensormodels"=>true,
    "sensormodeltab"=>true,
    "ajaxstype"=>true,
    "variables"=>true,
    "variabletab"=>true,
    "import"=>true,
//    "varmeascache"=>true,
    "getplotpng"=>true,
    "getplotsvg"=>true,
    "getplotjs"=>true,
    "getplotcsv"=>true,
    "getplotplot"=>true,
    "getplotbin"=>true,
    "getplotref"=>true,
    "alarms"=>true,
    "alarmspreset"=>true,
    "alarmpresettab"=>true,
    "alarmslog"=>true,
    "alarmtab"=>true,
    "ajaxalarm"=>true,
    "alarmsack"=>true,
    "alarmacktab"=>true,
    "varcodes"=>true,
    "varcodetab"=>true,
    "comments"=>true,
    "commenttab"=>true,
    "sess"=>true,
    "cron"=>true,
    "cronraw"=>true
    );
    
if(count($ARGV)) {
// takze bud obecna rekurze, nebo natvrdo 2 urovne
    $p=array_shift($ARGV);
    if(get_ind($pages,$p)===false) {
	redir(root().$_DEFAULTPAGE);
    } else { // musim checknout jeste druhou uroven
	$PAGE=$p;
    }
} else {
    redir(root().$_DEFAULTPAGE);
}

$ARGC=count($ARGV);
