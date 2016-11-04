<?php

$_NOHEAD=true;

function pagebegin() {
    echo "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">
<html xmlns=\"http://www.w3.org/1999/xhtml\">
<head>
<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />
<link rel=\"stylesheet\" type=\"text/css\" href=\"".root()."css/main.css\" />
<link rel=\"stylesheet\" type=\"text/css\" href=\"".root()."css/jquery-ui.min.css\" />
<title>Automatic Museum Monitoring</title>
</head>
<body>";

echo "<script src=\"".root()."js/jquery-1.11.2.min.js\"></script>";
echo "<script src=\"".root()."js/jquery-ui.min.js\"></script>";
}

function pageend() {
echo "<script type=\"text/javascript\">
// <![CDATA[
$(function() {
    $(\".pagep a\").button();
    $(\".pagep b\").button({disabled:true});
});
// ]]>
</script>";
    echo "</body></html>";
}

if(!$_SESSION->user) {
    header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
    exit();
}

switch($ARGC) {
case 3:
    if($ARGV[1]!="page" || !preg_match("/^\\d+$/",$ARGV[2])) {
	header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
	exit();
    }
    $sl=get_ind($_SESSION->imgsliders,$ARGV[0]);
    if($sl===false || !is_array($sl)) {
	header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
	exit();
    }
    $sl['page']=$ARGV[2];
    $_SESSION->imgsliders[$ARGV[0]]=$sl;
case 1:
    $sl=get_ind($_SESSION->imgsliders,$ARGV[0]);
    if($sl===false || !is_array($sl)) {
	header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
	exit();
    }
    $mid=get_ind($sl,"mid");
    $vid=get_ind($sl,"vid");
    $from=get_ind($sl,"from");
    $to=get_ind($sl,"to");
    $cpage=get_ind($sl,"page");
    if($cpage===false) $cpage=0;
    
    if(!is_numeric($from) || !is_numeric($to)) {
	header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
	exit();
    }
    $items=array();
    
	$tabs=array();
	dblock();
	$qe=$SQL->query("show tables like \"valuesblob\\_%\"");
	while($fe=$qe->row()) {
	    if(preg_match("/^valuesblob_(\\d+)$/",$fe[0],$mch)) $tabs[]=$mch[1];
	}
	dbunlock();
	sort($tabs);
	$fromy=gmdate("Y",$from);
	$toy=gmdate("Y",$to);
    
    $_IGNOREDST=true;
	foreach($tabs as $yval) {
	    if($yval>=$fromy && $yval<=$toy) { // try select here
		$qe=$SQL->query("select count(*) as cnt from valuesblob_".$yval." where vb_mid=".$mid." && vb_varid=".$vid." && vb_date>=".$from." && vb_date<=".$to);
		$fe=$qe->obj();
		$totcnt=0;
		if($fe) $totcnt=$fe->cnt;
	    
		$chunk=20;
		for($i=0;$i<$totcnt;$i+=$chunk) {
		    $qe=$SQL->query("select * from valuesblob_".$yval." where vb_mid=".$mid." && vb_varid=".$vid." && vb_date>=".$from." && vb_date<=".$to." order by vb_date limit ".$i.",".$chunk);
		    if($SQL->errnum) {
			sherr("Chyba databáze: ".$SQL->errnum);
			return;
		    }
		    while($fe=$qe->obj()) {
			$data=unserialize($fe->vb_value);
			$items[]=array($fe->vb_date,get_ind($data,"value"));
		    }
		}
	    }
	}
    
    pagebegin();
    if(!count($items)) echo "no data";
    else {
	pages(count($items),$cpage,"<a href=\"".root().$PAGE."/".$ARGV[0]."/page/%d\">%d</a>",false,$_BINSPERPAGE);
	$br=0;
	$i=$cpage*$_BINSPERPAGE;
	echo "<table border=\"1\">";
	for(;$i<count($items);$i++) {
	    $val=$items[$i];
	    echo "<tr><td>".showtime($val[0])."<br />".$val[1]."</td><td><img src=\"".root()."getplotbin/".$ARGV[0]."/".$val[0]."\" /></td></tr>";
	    $br++;
	    if($br==$_BINSPERPAGE) break;
	}
	echo "</table>";
	pages(count($items),$cpage,"<a href=\"".root().$PAGE."/".$ARGV[0]."/page/%d\">%d</a>",false,$_BINSPERPAGE);
    }
    pageend();
    $_IGNOREDST=false;
    break;
case 2:
    $sl=get_ind($_SESSION->imgsliders,$ARGV[0]);
    if($sl===false) {
	header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
	exit();
    }
    $mid=get_ind($sl,"mid");
    $vid=get_ind($sl,"vid");
    $item=$ARGV[1];
    $ity=date("Y",$item);

    if(!is_numeric($item) || !is_numeric($ity)) {
	header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
	exit();
    }

    $qe=$SQL->query("select * from valuesblob_".$ity." where
	vb_mid=\"".$SQL->escape($mid)."\" &&
	vb_varid=\"".$SQL->escape($vid)."\" &&
	vb_date=\"".$SQL->escape($item)."\"");
    if($SQL->errnum) {
	header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
	exit();
    }
    $fe=$qe->obj();
    if(!$fe) {
	header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
	exit();
    }

    $data=unserialize($fe->vb_value);
    $type=get_ind($data,"type");
    switch($type) {
    case "jpeg":
	header("Content-type: image/jpeg");
	$jpg=get_ind($data,"data");
	header("Content-length: ".strlen($jpg));
	echo $jpg;
	break;
    default:
	header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
    }
    break;
default:
    header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
    exit();
}
