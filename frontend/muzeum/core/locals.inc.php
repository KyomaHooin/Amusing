<?php

function uid() {
    if(!$_SESSION->user) return 0;
    return $_SESSION->user->u_id;
}

function urole() {
    if(!$_SESSION->user) return 'N';
    return $_SESSION->user->u_role;
}

function sqlinsert($tab,$vals,$vals2=false) {
    global $SQL;
    $toi=array();
    foreach($vals as $k=>$v) {
	$toi[]=$k."=\"".$SQL->escape($v)."\"";
    }
    if(is_array($vals2)) {
	foreach($vals2 as $k=>$v) {
	    $toi[]=$k."=".$v;
	}
    }
    $SQL->query("insert into ".$tab." set ".implode(",",$toi));
}

function logsys($t) {
    sqlinsert("log",array("l_text"=>$t),array("l_date"=>"now()","l_uid"=>"0"));
}

function logtext($t) {
    if(!$_SESSION->user) logsys($t);
    else {
	sqlinsert("log",array("l_text"=>$t,"l_uid"=>uid()),array("l_date"=>"now()"));
    }
}

function showmenu() {
    global $_JQUERY;
    global $PAGE;

    if(!$_SESSION->user) return;
    switch($_SESSION->user->u_role) {
    case 'A': // all
	$items=array(
	    "main"=>"přehled",
	    "buildings"=>"budovy",
	    "rooms"=>"místnosti",
	    "materials"=>"materiály",
	    "measpoints"=>"měřící body",
	    "comments"=>"komentáře",
	    "alarms"=>"alarmy",
	    "alarmspreset"=>"al. nastavení",
	    "alarmslog"=>"al. události",
	    "alarmsack"=>"al. potvrzení",
	    "sensors"=>"senzory",
	    "sensormodels"=>"sen. modely",
	    "variables"=>"veličiny",
	    "varcodes"=>"vel. kódy",
	    "users"=>"uživatelé",
	    "log"=>"log",
	    "settings"=>"nastavení");
	break;
    case 'D':
	$items=array(
	    "main"=>"přehled",
	    "buildings"=>"budovy",
	    "rooms"=>"místnosti",
	    "materials"=>"materiály",
	    "measpoints"=>"měřící body",
	    "comments"=>"komentáře",
	    "alarms"=>"alarmy",
	    "alarmspreset"=>"al. nastavení",
	    "alarmslog"=>"al. události",
	    "alarmsack"=>"al. potvrzení",
	    "sensors"=>"senzory",
	    "sensormodels"=>"sen. modely",
	    "variables"=>"veličiny",
	    "users"=>"uživatelé",
	    "log"=>"log",
	    "settings"=>"nastavení");
	break;
    case 'U':
	$items=array(
	    "main"=>"přehled",
	    "measpoints"=>"měřící body");
	break;
    default:
	return;
    }

    echo "<div id=\"divmenu\" style=\"white-space:nowrap; background-color:rgb(240,242,244); height:30px; margin:0;\">";
    foreach($items as $k=>$v) {
	if($PAGE==$k) echo "<a href=\"".root().$k."\"><b>".$v."</b></a>";
	else echo "<a href=\"".root().$k."\">".$v."</a>";
    }
    echo "</div>";
    echo "<script type=\"text/javascript\">
// <![CDATA[
function menugui() {
    $(\"#divmenu\").buttonset();
}
// ]]>
</script>";
    $_JQUERY[]="menugui();";
    
    if($PAGE!="main") echo "<br />";
}

function pageperm() {
    global $PAGE;
    if(!$_SESSION->user) redir(root()."login");
    if(urole()=='A') return;
    if(urole()=='D') {
	switch($PAGE) {
	case "varcodes":
	case "varcodetab":
	    redir(root()."main");
	}
	return;
    }
    switch($PAGE) {
    case "main":
    case "user":
    case "measpoints":
    case "import":
	break;
    default:
	redir(root()."main");
    }
}

function pages($i,$pag,$href,$pid=false,$perpage=false) {
    global $_PERPAGE;
    if($perpage===false) $perpage=$_PERPAGE;
    if($i>$perpage) {
	$j=(int)(($i+$perpage-1)/$perpage);
	if($pid) echo "<p class=\"pagep\" id=\"".$pid."\">Strana: ";
	else echo "<p class=\"pagep\">Strana: ";
	
	$incs=array(1,1,1,1,1,5,10,10,10,50,100,200,350,500,1000000,0);
	$pag=(int)$pag;
	if($pag<0) $pag=0;
	if($pag>=$j) { // special case.... print just one side, not selected anyone
	    $pag=0;
	    $i=1;
	    $resright=array(sprintf($href,0,1));
	} else {
	    $i=$pag+1;
	    $resright=array("<b>".$i."</b>");
	}
	$last=$pag;
	foreach($incs as $val) {
	    if($i>=$j) {
		if($last!=$j-1) $resright[]=sprintf($href,$j-1,$j);
		break;
	    }
	    $resright[]=sprintf($href,$i,$i+1);
	    $last=$i;
	    $i+=$val;
	}
	
	$i=$pag-1;
	$resleft=array();
	$last=$pag;
	foreach($incs as $val) {
	    if($i<=0) {
		if($last) $resleft[]=sprintf($href,0,1);
		break;
	    }
	    $resleft[]=sprintf($href,$i,$i+1);
	    $last=$i;
	    $i-=$val;
	}
	$res=array_merge(array_reverse($resleft),$resright);
//	echo implode(" ",$res);
	echo implode("",$res);
	echo "</p>";
    }
}

function resizeimg(&$img,$imgmaxw=320,$imgmaxh=240) {
    if(imagesx($img)>$imgmaxw) {
	$coef=$imgmaxw/imagesx($img);
	if(imagesy($img)*$coef>$imgmaxh) $coef=$imgmaxh/imagesy($img);
    } else if(imagesy($img)>$imgmaxh) {
	$coef=$imgmaxh/imagesy($img);
	if(imagesx($img)*$coef>$imgmaxw) $coef=$imgmaxw/imagesx($img);
    } else return;
    $neww=(int)(imagesx($img)*$coef);
    $newh=(int)(imagesy($img)*$coef);
    $tmp=imagecreatetruecolor($neww,$newh);
    imagecopyresized($tmp,$img,0,0,0,0,$neww,$newh,imagesx($img),imagesy($img));
    $img=$tmp;
}

function showtime($s) { // convert sql utc timestamp into server local time
    global $_IGNOREDST;
    if(!$_IGNOREDST) return date("Y-m-d H:i:s",$s);
    return gmdate("Y-m-d H:i:s",$s+3600); // NASTY !!!
}

function showtime2($s) { // convert sql utc timestamp into server local time
    global $_IGNOREDST;
    if(!$_IGNOREDST) return date("Y-m-d",$s)."<br />".date("H:i:s",$s);
    return gmdate("Y-m-d",$s+3600)."<br />".gmdate("H:i:s",$s+3600);
}

function showdate($s) {
    global $_IGNOREDST;
    if(!$_IGNOREDST) return date("Y-m-d",$s);
    return gmdate("Y-m-d",$s+3600);
}

function gettime($s) {
    global $_IGNOREDST;
    if(preg_match("/^(\\d+)\\-(\\d+)\\-(\\d+)$/",trim($s),$mch)) {
	if(!checkdate($mch[2],$mch[3],$mch[1])) return false;
	if(!$_IGNOREDST) return mktime(0,0,0,$mch[2],$mch[3],$mch[1]);
	return gmmktime(0,0,0,$mch[2],$mch[3],$mch[1])-3600;
    }
    if(preg_match("/^(\\d+)\\-(\\d+)\\-(\\d+)\\s+(\\d+)\\:(\\d+)$/",trim($s),$mch)) {
	if(!checkdate($mch[2],$mch[3],$mch[1])) return false;
	if($mch[4]>23) return false;
	if($mch[5]>59) return false;
	if(!$_IGNOREDST) return mktime($mch[4],$mch[5],0,$mch[2],$mch[3],$mch[1]);
	return gmmktime($mch[4],$mch[5],0,$mch[2],$mch[3],$mch[1])-3600;
    }
    if(preg_match("/^(\\d+)\\-(\\d+)\\-(\\d+)\\s+(\\d+)\\:(\\d+)\\:(\\d+)$/",trim($s),$mch)) {
	if(!checkdate($mch[2],$mch[3],$mch[1])) return false;
	if($mch[4]>23) return false;
	if($mch[5]>59) return false;
	if($mch[6]>59) return false;
	if(!$_IGNOREDST) return mktime($mch[4],$mch[5],$mch[6],$mch[2],$mch[3],$mch[1]);
	return gmmktime($mch[4],$mch[5],$mch[6],$mch[2],$mch[3],$mch[1])-3600;
    }
    return false;
}

function sortlocalref($as,$st,$sm) {
    global $PAGE;
    echo "<tr>";
    foreach($as as $val) {
	if(!$val['a']) echo "<th>".$val['n']."</th>";
	else {
	    echo "<th><nobr><a href=\"".root().$PAGE."/sort/".$val['a']."\">".$val['n']."</a>";
	    if($st==$val['a']) echo " ".($sm?"&uarr;":"&darr;");
	    echo "</nobr></th>";
	}
    }
    echo "</tr>";
}

// there could be real db lock version using lock tables sql command, but it lost lock in case of lost connection
function dblock() {
    global $_LOCKFILE;
    global $_LOCKCNT;
    global $_LOCKHANDLE;
    if(!$_LOCKCNT) {
	$_LOCKHANDLE=@fopen($_LOCKFILE,"w+");
	if(!$_LOCKHANDLE) throw new Exception("Cant lock");
	if(!@flock($_LOCKHANDLE,LOCK_EX)) {
	    @fclose($_LOCKHANDLE);
	    throw new Exception("Cant lock");
	}
    }
    $_LOCKCNT++;
}

function dbunlock() {
    global $_LOCKCNT;
    global $_LOCKHANDLE;
    if($_LOCKCNT) {
	$_LOCKCNT--;
	if(!$_LOCKCNT) {
	    @fclose($_LOCKHANDLE);
	    $_LOCKHANDLE=false;
	}
    }
}

function abshum($t,$rh) {
    return ((1.0007+0.00000346*$t)*6.1121*exp(17.502*$t/($t+240.9)))*100.0/($t+273.15)*$rh/100.0/8.31415*18.0153;
}

if(!function_exists("hex2bin")) {
    function hex2bin($str) {
	$sbin="";
	$len=strlen($str);
	for($i=0;$i<$len;$i+=2) $sbin.=pack("H*",substr($str,$i,2));
	return $sbin;
    }
}

function getcolumns($t) {
    global $SQL;
    
    $ret=array();
    $qe=$SQL->query("desc ".$t);
    if($SQL->errnum) return false;
    while($fe=$qe->row()) {
	$ret[]=$fe[0];
    }
    return $ret;
}

function is_intnumber($val) {
    return preg_match("/^\\d+$/",$val)!=0;
}

function saveuserpref() {
    global $SQL;
    if(!$_SESSION->user) return;
    $SQL->query("update user set u_pref=\"".$SQL->escape(serialize($_SESSION->userpref))."\" where u_id=\"".$SQL->escape($_SESSION->user->u_id)."\"");
}

function dbfsort($e1,$e2) {
    if($e1[0]<$e2[0]) return -1;
    if($e1[0]>$e2[0]) return 1;
    return 0;
}

function cronunlock() {
    global $locked;
    global $SQL;
    if(!$locked) return;
    $SQL->query("update cronlock set cl_lock='N' where cl_pid=".getmypid());
}

function cronlock() {
    global $locked;
    global $SQL;
    if($locked) return true;
    $SQL->query("lock table cronlock write");
    $qe=$SQL->query("select *,if(adddate(cl_date,interval 60 minute)<now(),1,0) as old from cronlock");
    $fe=$qe->obj();
    if(!$fe) $SQL->query("insert into cronlock set cl_lock='Y',cl_date=now(),cl_pid=".getmypid());
    else {
	if($fe->cl_lock=='Y') {
	    if(!$fe->old) {
		$SQL->unlock();
		return false; // fresh lock
	    }
	    logsys("cron proces patrne neplatně skončil");
	}
	$SQL->query("update cronlock set cl_lock='Y',cl_date=now(),cl_pid=".getmypid());
    }
    
    $SQL->unlock();
    $locked=true;
    register_shutdown_function("cronunlock");
    return true;
}

function freshlock() {
    global $SQL;
    global $locked;
    if(!$locked) return;
    $SQL->query("update cronlock set cl_date=now() where cl_pid=".getmypid());
}
