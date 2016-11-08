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
	if($_SESSION->alarmsack_sort==$ARGV[1]) $_SESSION->alarmsack_sortmode=!$_SESSION->alarmsack_sortmode;
	$_SESSION->alarmsack_sort=$ARGV[1];
	redir();
	break;
    case "page":
	$_SESSION->alarmsack_currpage=(int)$ARGV[1];
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
switch($_SESSION->alarmsack_sort) {
case "build":
    $ord[]="b_name ".($_SESSION->alarmsack_sortmode?"desc":"asc");
    break;
case "city":
    $ord[]="b_city ".($_SESSION->alarmsack_sortmode?"desc":"asc");
    break;
case "room":
    $ord[]="r_desc ".($_SESSION->alarmsack_sortmode?"desc":"asc");
    break;
case "floor":
    $ord[]="r_floor ".($_SESSION->alarmsack_sortmode?"desc":"asc");
    break;
case "meas":
    $ord[]="m_desc ".($_SESSION->alarmsack_sortmode?"desc":"asc");
    break;
case "user":
    $ord[]="u_fullname ".($_SESSION->alarmsack_sortmode?"desc":"asc");
    break;
case "alarm":
    $ord[]="ac_atext ".($_SESSION->alarmsack_sortmode?"desc":"asc");
    break;
case "text":
    $ord[]="ac_text ".($_SESSION->alarmsack_sortmode?"desc":"asc");
    break;
case "var":
    $ord[]="var_desc ".($_SESSION->alarmsack_sortmode?"desc":"asc");
    break;
case "dateack":
    $ord[]="ac_dateack ".($_SESSION->alarmsack_sortmode?"desc":"asc");
    break;
default:
    $_SESSION->alarmsack_sort="date";
}
$ord[]="ac_dategen ".($_SESSION->alarmsack_sortmode?"asc":"desc");

echo "<form id=\"aackform\" action=\"".root().$PAGE."\" method=\"post\">";
$whr=array();
switch(urole()) {
case 'A':
case 'D':
    $limitacc=false;
    break;
default:
    $whr[]="u_id=".uid();
    $limitacc=false;
}

if($_SESSION->alarmsack_filterenable) { // using same filter variables
    echo "<fieldset><legend>Filtr</legend>";
    echo "<table class=\"nobr\">";

    $opts=array(0=>"Všechna města");
    $qe=$SQL->query("select * from building group by b_city order by b_city");
    while($fe=$qe->obj()) {
	$opts[bin2hex($fe->b_city)]=$fe->b_city;
    }
    $sb=get_ind($_SESSION->alarmsack_filter,"000_aack_filter_city");
    echo "<tr><td>Město:&nbsp;</td><td>".input_select("000_aack_filter_city",$opts,$sb)."</td></tr>";

    $opts=array(0=>"Všechny budovy");
    if($sb) {
	$qe=$SQL->query("select * from building where b_city=\"".$SQL->escape(my_hex2bin($sb))."\" order by b_name");
	while($fe=$qe->obj()) {
	    $opts[$fe->b_id]=$fe->b_name;
	}
	$sb=get_ind($_SESSION->alarmsack_filter,"001_ajax_build");
    } else $sb=false;
    echo "<tr><td>Budova:&nbsp;</td><td><span id=\"aackbuildc\">".input_select("001_ajax_build",$opts,$sb)."</span></td></tr>";
    
    $opts=array(0=>"Všechny místnosti");
    if($sb) {
	$qe=$SQL->query("select * from room where r_bid=\"".$SQL->escape($sb)."\" order by r_desc");
	while($fe=$qe->obj()) {
	    $opts[$fe->r_id]=$fe->r_desc;
	}
	$sb=get_ind($_SESSION->alarmsack_filter,"001_ajax_room");
    } else $sb=false;
    echo "<tr><td>Místnost:&nbsp;</td><td><span id=\"aackroomc\">".input_select("001_ajax_room",$opts,$sb)."</span></td></tr>";
    
    $opts=array(0=>"Všechny měřící body");
    if($sb) {
	$qe=$SQL->query("select * from measuring where m_rid=\"".$SQL->escape($sb)."\" order by m_desc");
	while($fe=$qe->obj()) {
	    $opts[$fe->m_id]=$fe->m_desc;
	}
    }
    echo "<tr><td>Měřící bod:&nbsp;</td><td><span id=\"aackmeasc\">".input_select("001_ajax_meas",$opts,get_ind($_SESSION->alarmsack_filter,"001_ajax_meas"))."</span></td></tr>";
    
    $opts=array(0=>"Všechny velličiny");
    $qe=$SQL->query("select * from variable order by var_desc");
    while($fe=$qe->obj()) $opts[$fe->var_id]=$fe->var_desc." ".$fe->var_unit;
    echo "<tr><td>Veličina:&nbsp;</td><td>".input_select("001_aack_var",$opts,get_ind($_SESSION->alarmsack_filter,"001_aack_var"))."</td></tr>";
    
    $opts=array(0=>"Všichni uživatelé");
    $qe=$SQL->query("select * from user order by u_fullname");
    while($fe=$qe->obj()) $opts[$fe->u_id]=$fe->u_fullname;
    echo "<tr><td>Uřivatel:&nbsp;</td><td>".input_select("001_aack_user",$opts,get_ind($_SESSION->alarmsack_filter,"001_aack_user"))."</td></tr>";
    
    echo "</table>";

    echo input_button("aack_fapply","Použít")." ".input_button("aack_fall","Zobrazit vše");
    echo "</fieldset>";
    
    $fb=get_ind($_SESSION->alarmsack_filter,"001_ajax_build");
    if($fb) $whr[]="b_id=\"".$SQL->escape($fb)."\"";
    $fb=get_ind($_SESSION->alarmsack_filter,"001_ajax_room");
    if($fb) $whr[]="r_id=\"".$SQL->escape($fb)."\"";
    $fb=get_ind($_SESSION->alarmsack_filter,"001_ajax_meas");
    if($fb) $whr[]="m_id=\"".$SQL->escape($fb)."\"";
    $fb=get_ind($_SESSION->alarmsack_filter,"001_aack_user");
    if($fb) $whr[]="u_id=\"".$SQL->escape($fb)."\"";
    $fb=get_ind($_SESSION->alarmsack_filter,"001_aack_var");
    if($fb) $whr[]="var_id=\"".$SQL->escape($fb)."\"";
    $ftmp=get_ind($_SESSION->alarmsack_filter,"000_aack_filter_city");
    if($ftmp) $whr[]="b_city=\"".$SQL->escape(my_hex2bin($ftmp))."\"";

    echo "<script type=\"text/javascript\">
// <![CDATA[
function roomchange() {
    $.get(\"".root()."ajax/getmeassel2/\"+$(\"#001_ajax_room\").val(),function(data) {
	$(\"#aackmeasc\").html(data);
    });
}
function buildchange() {
    $.get(\"".root()."ajax/getroomsel2/\"+$(\"#001_ajax_build\").val(),function(data) {
	$(\"#aackroomc\").html(data);
	$(\"#001_ajax_room\").change(roomchange);
	roomchange();
    });
}
function buildsub() {
    $(\"#001_ajax_build\").change(buildchange);
    $(\"#001_ajax_room\").change(roomchange);
    $(\"#000_aack_filter_city\").change(function() {
	$.get(\"".root()."ajax/getbuildsel2/\"+$(this).val(),function(data) {
	    $(\"#aackbuildc\").html(data);
	    $(\"#001_ajax_build\").change(buildchange);
	    buildchange();
	});
    });
}
// ]]>
</script>";
    $_JQUERY[]="buildsub();";
}

if($makecsv) {
    ob_clean();
    $_NOHEAD=true;
//    header("Content-type: text/plain");
    header("Content-type: text/x-csv");
    header("Content-Disposition: attachment; filename=".$PAGE.".csv");
    
    ob_start();
    echo csvline(array("Datum vzniku","Město","Budova","Místnost","Patro","Měřící bod","Veličina","Uživatel","Alarm","Text","Potvrzeno","Datum potvrzeni"));
    $qe=$SQL->query("select * from alarmack left join variable on ac_vid=var_id left join measuring on ac_mid=m_id left join room on m_rid=r_id left join building on r_bid=b_id left join user on ac_uid=u_id ".(count($whr)?"where ".implode(" && ",$whr):"")." order by ".implode(",",$ord));
    while($fe=$qe->obj()) {
	$acked=($fe->ac_state=='Y');
	echo csvline(array($fe->ac_dategen,$fe->b_city,$fe->b_name,$fe->r_desc,$fe->r_floor,$fe->m_desc,$fe->var_desc." ".$fe->var_unit,$fe->u_fullname,$fe->ac_atext,$fe->ac_text,
	    $acked?"Ano":"Ne",$acked?$fe->ac_dateack:"-"));
    }
    $csv=ob_get_contents();
    ob_end_clean();
    echo csvoutput($csv);
    
    exit();
}

$offset=(int)($_SESSION->alarmsack_currpage*$_PERPAGE);
$limit=(int)$_PERPAGE;
$qe=$SQL->query("select SQL_CALC_FOUND_ROWS * from alarmack left join variable on ac_vid=var_id left join measuring on ac_mid=m_id left join room on m_rid=r_id left join building on r_bid=b_id left join user on ac_uid=u_id ".(count($whr)?"where ".implode(" && ",$whr):"")." order by ".implode(",",$ord)." limit ".$offset.",".$limit);

ob_start();
echo "<table>";
sortlocalref(array(
    array('n'=>"Datum vzniku",'a'=>"date"),
    array('n'=>"Město",'a'=>"city"),
    array('n'=>"Budova",'a'=>"build"),
    array('n'=>"Místnost",'a'=>"room"),
    array('n'=>"Patro",'a'=>"floor"),
    array('n'=>"Měřící bod",'a'=>"meas"),
    array('n'=>"Veličina",'a'=>"var"),
    array('n'=>"Uživatel",'a'=>"user"),
    array('n'=>"Alarm",'a'=>"alarm"),
    array('n'=>"Text",'a'=>"text"),
    array('n'=>"Potvrzeno",'a'=>false),
    array('n'=>"Datum potvrzeni",'a'=>"dateack"),
    array('n'=>input_button("aack_filter","Filtr"),'a'=>false)
),$_SESSION->alarmsack_sort,$_SESSION->alarmsack_sortmode);

$nack=0;
$nackid=array();
while($fe=$qe->obj()) {
    $acked=($fe->ac_state=='Y');
    echo "<tr><td>".htmlspecialchars($fe->ac_dategen)."</td>
	<td>".htmlspecialchars($fe->b_city)."</td>
	<td>".htmlspecialchars($fe->b_name)."</td>
	<td>".htmlspecialchars($fe->r_desc)."</td>
	<td>".htmlspecialchars($fe->r_floor)."</td>
	<td>".htmlspecialchars($fe->m_desc)."</td>
	<td>".htmlspecialchars($fe->var_desc." ".$fe->var_unit)."</td>
	<td>".htmlspecialchars($fe->u_fullname)."</td>
	<td>".htmlspecialchars($fe->ac_atext)."</td>
	<td>".htmlspecialchars($fe->ac_text)."</td>
	<td>".($acked?"Ano":"Ne")."</td>
	<td>".($acked?htmlspecialchars($fe->ac_dateack):"-")."</td>";
	if($acked) echo "<td>&nbsp;</td>";
	else {
	    $nackid[]=$fe->ac_id;
	    echo "<td>".input_button("aack_ack[".$fe->ac_id."]","Potvrdit")."</td>";
	    $nack++;
	}
	echo "</tr>";
}

if($nack) echo "<tr><td colspan=\"12\">&nbsp;</td><td>".input_button("aack_hackall","Potvrdit vše")."</td></tr>";
echo "</table>";
$tbl=ob_get_clean();

$qe=$SQL->query("select FOUND_ROWS() as rows");
$fe=$qe->obj();
$totalrows=$fe->rows;
if($totalrows) pages($totalrows,$_SESSION->alarmsack_currpage,"<a href=\"".root().$PAGE."/page/%d\">%d</a>");
echo $tbl;
if($totalrows) pages($totalrows,$_SESSION->alarmsack_currpage,"<a href=\"".root().$PAGE."/page/%d\">%d</a>");

echo "<br /><a href=\"".root().$PAGE."/csv\">Uložit jako csv</a>";

echo "</form>";

echo "<script type=\"text/javascript\">
// <![CDATA[
var surp=false;
function alarmsgui() {
    $(\"button\").button();
    $(\".pagep a\").button();
    $(\".pagep b\").button({disabled:true});";

if($nack) {
    echo "
    $(\"#aackform\").submit(function() {
	if(surp) {
	    surp=false;
	    return false;
	}
    });
    $(\"#aack_hackall\").click(function() {
	surp=true;
	$(\"#aack_hackt\").dialog(\"open\");
    });
    $(\"#aack_hackt\").dialog({
	resizable: false,
	height: \"auto\",
	width: \"auto\",
	modal: true,
	autoOpen: false,
	buttons: {
	    \"Potvrdit\":function() {
		$(\"#aackdlgfrm\").submit();
	    },
	    \"Zpět\":function() {
		$(this).dialog(\"close\");
	    }
	}
    });
    ";
}
echo "
}
// ]]>
</script>";
    $_JQUERY[]="alarmsgui();";

if($nack) {
    echo "<div id=\"aack_hackt\" title=\"Potvrdit vše\">
<form id=\"aackdlgfrm\" action=\"".root().$PAGE."\" method=\"post\">
<table><tr><td>Text potvrzení:&nbsp;</td><td>".input_area("000_aack_hacktext",false,"farea")."</td></tr></table>";
    echo input_hidden("aack_hackallh","1");
    foreach($nackid as $val) echo input_hidden("aack_hack[]",$val);
    echo "</form>
</div>";
}

if($_SERVER['REQUEST_METHOD']=="POST") {
    $_SESSION->invalid=false;
    $_SESSION->temp_form=false;
    
    if(get_ind($_POST,"aack_filter")) {
	$_SESSION->alarmsack_filterenable=!$_SESSION->alarmsack_filterenable;
	if($_SESSION->alarmsack_filterenable) $_SESSION->alarmsack_currpage=0;
	redir();
    }
    if(get_ind($_POST,"aack_fall")) {
	$_SESSION->alarmsack_filter=false;
	redir();
    }
    if(get_ind($_POST,"aack_fapply")) {
	postcheck($ITEMS,$_POST);
	$_SESSION->alarmsack_filter=$_POST;
	$_SESSION->alarmsack_currpage=0;
	redir();
    }
    if(get_ind($_POST,"aack_ack")) {
	$ks=get_ind($_POST,"aack_ack");
	if(is_array($ks)) {
	    $ks=key($ks);
	    redir(root()."alarmacktab/m/".$ks);
	}
	redir();
    }
    if(get_ind($_POST,"aack_hackallh")) {
	$vl=get_ind($_POST,"aack_hack");
	if(is_array($vl)) {
	    $qry=array();
	    
	    foreach($vl as $val) $qry[]="\"".$SQL->escape($val)."\"";
	    if(count($qry)) {
		$txt=$SQL->escape(trim(get_ind($_POST,"000_aack_hacktext")));
		if($limitacc) $qe=$SQL->query("select * from alarmack left join variable on ac_vid=var_id left join alarm on ac_id=a_ackid left join measuring on ac_mid=m_id left join room on m_rid=r_id left join building on r_bid=b_id left join user on ac_uid=u_id where ac_id in (".implode(",",$qry).") && u_id=".uid());
		else $qe=$SQL->query("select * from alarmack left join variable on ac_vid=var_id left join alarm on ac_id=a_ackid left join measuring on ac_mid=m_id left join room on m_rid=r_id left join building on r_bid=b_id left join user on ac_uid=u_id where ac_id in (".implode(",",$qry).")");
		while($ack=$qe->obj()) {
		    $SQL->query("update alarmack set ac_state='Y',ac_text=\"".$txt."\",ac_dateack=now() where ac_id=\"".$SQL->escape($ack->ac_id)."\" && ac_state!='Y'");
		    $SQL->query("update alarm set a_ackid=\"\",a_alarmed='N' where a_id=\"".$SQL->escape($ack->ac_aid)."\"");
		}
	    }
	}
	redir();
    }
    redir();
}
