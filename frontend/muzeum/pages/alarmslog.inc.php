<?php

pageperm();
showmenu();

showerror();

ajaxsess();

$makecsv=false;
switch($ARGC) {
case 2:
    switch($ARGV[0]) {
    case "sort":
	if($_SESSION->alarmslog_sort==$ARGV[1]) $_SESSION->alarmslog_sortmode=!$_SESSION->alarmslog_sortmode;
	$_SESSION->alarmslog_sort=$ARGV[1];
	redir();
	break;
    case "page":
	$_SESSION->alarmslog_currpage=(int)$ARGV[1];
	break;
    }
case 1:
    switch($ARGV[0]) {
    case "csv":
	$makecsv=true;
	break;
    }
}

$ord=array();
switch($_SESSION->alarmslog_sort) {
case "build":
    $ord[]="b_name ".($_SESSION->alarmslog_sortmode?"desc":"asc");
    break;
case "city":
    $ord[]="b_city ".($_SESSION->alarmslog_sortmode?"desc":"asc");
    break;
case "room":
    $ord[]="r_desc ".($_SESSION->alarmslog_sortmode?"desc":"asc");
    break;
case "floor":
    $ord[]="r_floor ".($_SESSION->alarmslog_sortmode?"desc":"asc");
    break;
case "meas":
    $ord[]="m_desc ".($_SESSION->alarmslog_sortmode?"desc":"asc");
    break;
case "user":
    $ord[]="u_fullname ".($_SESSION->alarmslog_sortmode?"desc":"asc");
    break;
case "class":
    $ord[]="al_class ".($_SESSION->alarmslog_sortmode?"desc":"asc");
    break;
case "edge":
    $ord[]="al_edge ".($_SESSION->alarmslog_sortmode?"desc":"asc");
    break;
case "text":
    $ord[]="al_text ".($_SESSION->alarmslog_sortmode?"desc":"asc");
    break;
case "var":
    $ord[]="var_desc ".($_SESSION->alarmslog_sortmode?"desc":"asc");
    break;
default:
    $_SESSION->alarmslog_sort="date";
}
$ord[]="al_date ".($_SESSION->alarmslog_sortmode?"asc":"desc");

echo "<form action=\"".root().$PAGE."\" method=\"post\">";

$whr=array();
switch(urole()) {
case 'A':
case 'D':
    break;
default:
    $whr[]="u_id=".uid();
}

if($_SESSION->alarmslog_filterenable) { // using same filter variables
    echo "<fieldset><legend>Filtr</legend>";
    echo "<table class=\"nobr\">";

    $opts=array(0=>"Všechna města");
    $qe=$SQL->query("select * from building group by b_city order by b_city");
    while($fe=$qe->obj()) {
	$opts[bin2hex($fe->b_city)]=$fe->b_city;
    }
    $sb=get_ind($_SESSION->alarmslog_filter,"000_alog_filter_city");
    echo "<tr><td>Město:&nbsp;</td><td>".input_select("000_alog_filter_city",$opts,$sb)."</td></tr>";

    $opts=array(0=>"Všechny budovy");
    if($sb) {
	$qe=$SQL->query("select * from building where b_city=\"".$SQL->escape(my_hex2bin($sb))."\" order by b_name");
	while($fe=$qe->obj()) {
	    $opts[$fe->b_id]=$fe->b_name;
	}
	$sb=get_ind($_SESSION->alarmslog_filter,"001_ajax_build");
    } else $sb=false;
    echo "<tr><td>Budova:&nbsp;</td><td><span id=\"alogbuildc\">".input_select("001_ajax_build",$opts,$sb)."</span></td></tr>";
    
    $opts=array(0=>"Všechny místnosti");
    if($sb) {
	$qe=$SQL->query("select * from room where r_bid=\"".$SQL->escape($sb)."\" order by r_desc");
	while($fe=$qe->obj()) {
	    $opts[$fe->r_id]=$fe->r_desc;
	}
	$sb=get_ind($_SESSION->alarmslog_filter,"001_ajax_room");
    } else $sb=false;
    echo "<tr><td>Místnost:&nbsp;</td><td><span id=\"alogroomc\">".input_select("001_ajax_room",$opts,$sb)."</span></td></tr>";
    
    $opts=array(0=>"Všechny měřící body");
    if($sb) {
	$qe=$SQL->query("select * from measuring where m_rid=\"".$SQL->escape($sb)."\" order by m_desc");
	while($fe=$qe->obj()) {
	    $opts[$fe->m_id]=$fe->m_desc;
	}
    }
    echo "<tr><td>Měřící bod:&nbsp;</td><td><span id=\"alogmeasc\">".input_select("001_ajax_meas",$opts,get_ind($_SESSION->alarmslog_filter,"001_ajax_meas"))."</span></td></tr>";
    
    $opts=array(0=>"Všechny velličiny");
    $qe=$SQL->query("select * from variable order by var_desc");
    while($fe=$qe->obj()) $opts[$fe->var_id]=$fe->var_desc." ".$fe->var_unit;
    echo "<tr><td>Veličina:&nbsp;</td><td>".input_select("001_alog_var",$opts,get_ind($_SESSION->alarmslog_filter,"001_alog_var"))."</td></tr>";
    
    $opts=array(0=>"Všichni uživatelé");
    $qe=$SQL->query("select * from user order by u_fullname");
    while($fe=$qe->obj()) $opts[$fe->u_id]=$fe->u_fullname;
    echo "<tr><td>Uřivatel:&nbsp;</td><td>".input_select("001_alog_user",$opts,get_ind($_SESSION->alarmslog_filter,"001_alog_user"))."</td></tr>";
    
    $opts=array(0=>"Všechny typy");
    foreach(c_alarm_gen::getalltypes() as $key=>$val) $opts[$key]=$val;
    echo "<tr><td>Typ alarmu:&nbsp;</td><td>".input_select("001_alog_type",$opts,get_ind($_SESSION->alarmslog_filter,"001_alog_type"))."</td></tr>";
    $opts=array(0=>"Všechny hrany","R"=>"Vzestupná","F"=>"Sestupná");
    echo "<tr><td>Hrana:&nbsp;</td><td>".input_select("001_alog_edge",$opts,get_ind($_SESSION->alarmslog_filter,"001_alog_edge"))."</td></tr>";
    echo "</table>";

    echo input_button("alog_fapply","Použít")." ".input_button("alog_fall","Zobrazit vše");
    echo "</fieldset>";
    
    $fb=get_ind($_SESSION->alarmslog_filter,"001_ajax_build");
    if($fb) $whr[]="b_id=\"".$SQL->escape($fb)."\"";
    $fb=get_ind($_SESSION->alarmslog_filter,"001_ajax_room");
    if($fb) $whr[]="r_id=\"".$SQL->escape($fb)."\"";
    $fb=get_ind($_SESSION->alarmslog_filter,"001_ajax_meas");
    if($fb) $whr[]="m_id=\"".$SQL->escape($fb)."\"";
    $fb=get_ind($_SESSION->alarmslog_filter,"001_alog_user");
    if($fb) $whr[]="u_id=\"".$SQL->escape($fb)."\"";
    $fb=get_ind($_SESSION->alarmslog_filter,"001_alog_type");
    if($fb) $whr[]="al_class=\"".$SQL->escape($fb)."\"";
    $fb=get_ind($_SESSION->alarmslog_filter,"001_alog_edge");
    if($fb) $whr[]="al_edge=\"".$SQL->escape($fb)."\"";
    $fb=get_ind($_SESSION->alarmslog_filter,"001_alog_var");
    if($fb) $whr[]="var_id=\"".$SQL->escape($fb)."\"";
    $ftmp=get_ind($_SESSION->alarmslog_filter,"000_alog_filter_city");
    if($ftmp) $whr[]="b_city=\"".$SQL->escape(my_hex2bin($ftmp))."\"";

    echo "<script type=\"text/javascript\">
// <![CDATA[
function roomchange() {
    $.get(\"".root()."ajax/getmeassel2/\"+$(\"#001_ajax_room\").val(),function(data) {
	$(\"#alogmeasc\").html(data);
    });
}
function buildchange() {
    $.get(\"".root()."ajax/getroomsel2/\"+$(\"#001_ajax_build\").val(),function(data) {
	$(\"#alogroomc\").html(data);
	$(\"#001_ajax_room\").change(roomchange);
	roomchange();
    });
}
function buildsub() {
    $(\"#001_ajax_build\").change(buildchange);
    $(\"#001_ajax_room\").change(roomchange);
    $(\"#000_alog_filter_city\").change(function() {
	$.get(\"".root()."ajax/getbuildsel2/\"+$(this).val(),function(data) {
	    $(\"#alogbuildc\").html(data);
	    $(\"#001_ajax_build\").change(buildchange);
	    buildchange();
	});
    });
}
// ]]>
</script>";
    $_JQUERY[]="buildsub();";
}

$acl=array();
if($makecsv) {
    ob_clean();
    $_NOHEAD=true;
//    header("Content-type: text/plain");
    header("Content-type: text/x-csv");
    header("Content-Disposition: attachment; filename=".$PAGE.".csv");
    
    ob_start();
    echo csvline(array("Datum","Město","Budova","Místnost","Patro","Měřící bod","Veličina","Uživatel","Typ","Charakter","Hrana","Text"));
    $qe=$SQL->query("select * from alarmlog left join variable on al_vid=var_id left join measuring on al_mid=m_id left join room on m_rid=r_id left join building on r_bid=b_id left join user on al_uid=u_id ".(count($whr)?"where ".implode(" && ",$whr):"")." order by ".implode(",",$ord));
    while($fe=$qe->obj()) {
	if(!get_ind($acl,$fe->al_class)) $acl[$fe->al_class]=c_alarm_gen::getalarmbyname($fe->al_class);
	$ca=$acl[$fe->al_class];
	echo csvline(array(showtime($fe->al_date),$fe->b_city,$fe->b_name,$fe->r_desc,$fe->r_floor,$fe->m_desc,$fe->var_desc." ".$fe->var_unit,$fe->u_fullname,
	    c_alarm_gen::getdescbyname($fe->al_class),$fe->al_crit=='Y'?"Kritický":"Varování",$fe->al_edge=='R'?"vzestupná":"sestupná",$ca?$ca->desc($fe->al_data):"NaN"));
    }
    $csv=ob_get_contents();
    ob_end_clean();
    echo csvoutput($csv);
    
    exit();
}

$offset=(int)($_SESSION->alarmslog_currpage*$_PERPAGE);
$limit=(int)$_PERPAGE;
$qe=$SQL->query("select SQL_CALC_FOUND_ROWS * from alarmlog left join variable on al_vid=var_id left join measuring on al_mid=m_id left join room on m_rid=r_id left join building on r_bid=b_id left join user on al_uid=u_id ".(count($whr)?"where ".implode(" && ",$whr):"")." order by ".implode(",",$ord)." limit ".$offset.",".$limit);

ob_start();
echo "<table>";
sortlocalref(array(
    array('n'=>"Datum",'a'=>"date"),
    array('n'=>"Město",'a'=>"city"),
    array('n'=>"Budova",'a'=>"build"),
    array('n'=>"Místnost",'a'=>"room"),
    array('n'=>"Patro",'a'=>"floor"),
    array('n'=>"Měřící bod",'a'=>"meas"),
    array('n'=>"Veličina",'a'=>"var"),
    array('n'=>"Uživatel",'a'=>"user"),
    array('n'=>"Typ",'a'=>"class"),
    array('n'=>"Charakter",'a'=>false),
    array('n'=>"Hrana",'a'=>"edge"),
    array('n'=>"Text",'a'=>"text"),
    array('n'=>input_button("alog_filter","Filtr"),'a'=>false)
),$_SESSION->alarmslog_sort,$_SESSION->alarmslog_sortmode);

while($fe=$qe->obj()) {
    if(!get_ind($acl,$fe->al_class)) $acl[$fe->al_class]=c_alarm_gen::getalarmbyname($fe->al_class);
    $ca=$acl[$fe->al_class];
    echo "<tr><td>".showtime($fe->al_date)."</td>
	<td>".htmlspecialchars($fe->b_city)."</td>
	<td>".htmlspecialchars($fe->b_name)."</td>
	<td>".htmlspecialchars($fe->r_desc)."</td>
	<td>".htmlspecialchars($fe->r_floor)."</td>
	<td>".htmlspecialchars($fe->m_desc)."</td>
	<td>".htmlspecialchars($fe->var_desc." ".$fe->var_unit)."</td>
	<td>".htmlspecialchars($fe->u_fullname)."</td>
	<td>".htmlspecialchars(c_alarm_gen::getdescbyname($fe->al_class))."</td>
	<td>".($fe->al_crit=='Y'?"Kritický":"Varování")."</td>
	<td>".($fe->al_edge=='R'?"vzestupná":"sestupná")."</td>
	<td colspan=\"2\">".htmlspecialchars($ca?$ca->desc($fe->al_data):"NaN")."</td></tr>";
}

echo "</table>";
$tbl=ob_get_clean();

$qe=$SQL->query("select FOUND_ROWS() as rows");
$fe=$qe->obj();
$totalrows=$fe->rows;
if($totalrows) pages($totalrows,$_SESSION->alarmslog_currpage,"<a href=\"".root().$PAGE."/page/%d\">%d</a>");
echo $tbl;
if($totalrows) pages($totalrows,$_SESSION->alarmslog_currpage,"<a href=\"".root().$PAGE."/page/%d\">%d</a>");

echo "<br /><a href=\"".root().$PAGE."/csv\">Uložit jako csv</a>";

echo "<script type=\"text/javascript\">
// <![CDATA[
function alarmsgui() {
    $(\"button\").button();
    $(\".pagep a\").button();
    $(\".pagep b\").button({disabled:true});
}
// ]]>
</script>";
    $_JQUERY[]="alarmsgui();";

echo "</form>";

if($_SERVER['REQUEST_METHOD']=="POST") {
    $_SESSION->invalid=false;
    $_SESSION->temp_form=false;

    if(get_ind($_POST,"alog_filter")) {
	$_SESSION->alarmslog_filterenable=!$_SESSION->alarmslog_filterenable;
	if($_SESSION->alarmslog_filterenable) $_SESSION->alarmslog_currpage=0;
	redir();
    }
    if(get_ind($_POST,"alog_fall")) {
	$_SESSION->alarmslog_filter=false;
	redir();
    }
    if(get_ind($_POST,"alog_fapply")) {
	postcheck($ITEMS,$_POST);
	$_SESSION->alarmslog_filter=$_POST;
	$_SESSION->alarmslog_currpage=0;
	redir();
    }
    redir();
}
