<?php

pageperm();
showmenu();

showerror();

ajaxsess();

$_SESSION->prevpage=false;

$makecsv=false;
switch($ARGC) {
case 2:
    switch($ARGV[0]) {
    case "sort":
	if($_SESSION->comments_sort==$ARGV[1]) $_SESSION->comments_sortmode=!$_SESSION->comments_sortmode;
	$_SESSION->comments_sort=$ARGV[1];
	redir();
	break;
    case "page":
	$_SESSION->comments_currpage=(int)$ARGV[1];
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
switch($_SESSION->comments_sort) {
case "build":
    $ord[]="b_name ".($_SESSION->comments_sortmode?"desc":"asc");
    break;
case "city":
    $ord[]="b_city ".($_SESSION->comments_sortmode?"desc":"asc");
    break;
case "room":
    $ord[]="r_desc ".($_SESSION->comments_sortmode?"desc":"asc");
    break;
case "floor":
    $ord[]="r_floor ".($_SESSION->comments_sortmode?"desc":"asc");
    break;
case "meas":
    $ord[]="m_desc ".($_SESSION->comments_sortmode?"desc":"asc");
    break;
case "user":
    $ord[]="u_fullname ".($_SESSION->comments_sortmode?"desc":"asc");
    break;
case "text":
    $ord[]="cm_text ".($_SESSION->comments_sortmode?"desc":"asc");
    break;
default:
    $_SESSION->comments_sort="date";
}
$ord[]="cm_date ".($_SESSION->comments_sortmode?"desc":"asc");

echo "<form id=\"commform\" action=\"".root().$PAGE."\" method=\"post\">";

echo input_button("comm_new","Přidat komentář","newbutton");

$whr=array();

//limit comments in case of non admin
switch(urole()) {
case 'A':
case 'D':
    $limitacc=false;
    break;
default:
    $limitacc=true;
    $whr[]="u_id=".uid();
}

if($_SESSION->comments_filterenable) { // using same filter variables
    echo "<fieldset><legend>Filtr</legend>";
    echo "<table class=\"nobr\">";

    $opts=array(0=>"Všechna města");
    $qe=$SQL->query("select * from building group by b_city order by b_city");
    while($fe=$qe->obj()) {
	$opts[bin2hex($fe->b_city)]=$fe->b_city;
    }
    $sb=get_ind($_SESSION->comments_filter,"000_comm_filter_city");
    echo "<tr><td>Město:&nbsp;</td><td>".input_select("000_comm_filter_city",$opts,$sb)."</td></tr>";

    $opts=array(0=>"Všechny budovy");
    if($sb) {
	$qe=$SQL->query("select * from building where b_city=\"".$SQL->escape(my_hex2bin($sb))."\" order by b_name");
	while($fe=$qe->obj()) {
	    $opts[$fe->b_id]=$fe->b_name;
	}
	$sb=get_ind($_SESSION->comments_filter,"001_ajax_build");
    } else $sb=false;
    echo "<tr><td>Budova:&nbsp;</td><td><span id=\"commbuildc\">".input_select("001_ajax_build",$opts,$sb)."</span></td></tr>";
    
    $opts=array(0=>"Všechny místnosti");
    if($sb) {
	$qe=$SQL->query("select * from room where r_bid=\"".$SQL->escape($sb)."\" order by r_desc");
	while($fe=$qe->obj()) {
	    $opts[$fe->r_id]=$fe->r_desc;
	}
	$sb=get_ind($_SESSION->comments_filter,"001_ajax_room");
    } else $sb=false;
    echo "<tr><td>Místnost:&nbsp;</td><td><span id=\"commroomc\">".input_select("001_ajax_room",$opts,$sb)."</span></td></tr>";
    
    $opts=array(0=>"Všechny měřící body");
    if($sb) {
	$qe=$SQL->query("select * from measuring where m_rid=\"".$SQL->escape($sb)."\" order by m_desc");
	while($fe=$qe->obj()) {
	    $opts[$fe->m_id]=$fe->m_desc;
	}
    }
    echo "<tr><td>Měřící bod:&nbsp;</td><td><span id=\"commmeasc\">".input_select("001_ajax_meas",$opts,get_ind($_SESSION->comments_filter,"001_ajax_meas"))."</span></td></tr>";
    
    if(!$limitacc) {
	$opts=array(0=>"Všichni uživatelé");
	$qe=$SQL->query("select * from user order by u_fullname");
	while($fe=$qe->obj()) $opts[$fe->u_id]=$fe->u_fullname;
	echo "<tr><td>Uživatel:&nbsp;</td><td>".input_select("001_comm_user",$opts,get_ind($_SESSION->comments_filter,"001_comm_user"))."</td></tr>";
    }
    
    echo "</table>";

    echo input_button("comm_fapply","Použít")." ".input_button("comm_fall","Zobrazit vše");
    echo "</fieldset>";
    
    $fb=get_ind($_SESSION->comments_filter,"001_ajax_build");
    if($fb) $whr[]="b_id=\"".$SQL->escape($fb)."\"";
    $fb=get_ind($_SESSION->comments_filter,"001_ajax_room");
    if($fb) $whr[]="r_id=\"".$SQL->escape($fb)."\"";
    $fb=get_ind($_SESSION->comments_filter,"001_ajax_meas");
    if($fb) $whr[]="m_id=\"".$SQL->escape($fb)."\"";
    
    if(!$limitacc) {
	$fb=get_ind($_SESSION->comments_filter,"001_comm_user");
	if($fb) $whr[]="u_id=\"".$SQL->escape($fb)."\"";
    }
    
    $ftmp=get_ind($_SESSION->comments_filter,"000_comm_filter_city");
    if($ftmp) $whr[]="b_city=\"".$SQL->escape(my_hex2bin($ftmp))."\"";

    echo "<script type=\"text/javascript\">
// <![CDATA[
function roomchange() {
    $.get(\"".root()."ajax/getmeassel2/\"+$(\"#001_ajax_room\").val(),function(data) {
	$(\"#commmeasc\").html(data);
    });
}
function buildchange() {
    $.get(\"".root()."ajax/getroomsel2/\"+$(\"#001_ajax_build\").val(),function(data) {
	$(\"#commroomc\").html(data);
	$(\"#001_ajax_room\").change(roomchange);
	roomchange();
    });
}
function buildsub() {
    $(\"#001_ajax_build\").change(buildchange);
    $(\"#001_ajax_room\").change(roomchange);
    $(\"#000_comm_filter_city\").change(function() {
	$.get(\"".root()."ajax/getbuildsel2/\"+$(this).val(),function(data) {
	    $(\"#commbuildc\").html(data);
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
    echo csvline(array("#","Datum","Město","Budova","Místnost","Patro","Měřící bod","Uživatel","Text"));
    $qe=$SQL->query("select * from comment left join measuring on cm_mid=m_id left join room on m_rid=r_id left join building on r_bid=b_id left join user on cm_uid=u_id ".(count($whr)?"where ".implode(" && ",$whr):"")." order by ".implode(",",$ord));
    while($fe=$qe->obj()) {
	echo csvline(array($fe->cm_id,showtime($fe->cm_date),$fe->b_city,$fe->b_name,$fe->r_desc,$fe->r_floor,$fe->m_desc,$fe->u_fullname,$fe->cm_text));
    }
    $csv=ob_get_contents();
    ob_end_clean();
    echo csvoutput($csv);
    
    exit();
}

$offset=(int)($_SESSION->comments_currpage*$_PERPAGE);
$limit=(int)$_PERPAGE;
$qe=$SQL->query("select SQL_CALC_FOUND_ROWS * from comment left join measuring on cm_mid=m_id left join room on m_rid=r_id left join building on r_bid=b_id left join user on cm_uid=u_id ".(count($whr)?"where ".implode(" && ",$whr):"")." order by ".implode(",",$ord)." limit ".$offset.",".$limit);

ob_start();
echo "<table>";
sortlocalref(array(
    array('n'=>"#",'a'=>false),
    array('n'=>"&nbsp;",'a'=>false),
    array('n'=>"Datum",'a'=>"date"),
    array('n'=>"Město",'a'=>"city"),
    array('n'=>"Budova",'a'=>"build"),
    array('n'=>"Místnost",'a'=>"room"),
    array('n'=>"Patro",'a'=>"floor"),
    array('n'=>"Měřící bod",'a'=>"meas"),
    array('n'=>"Uživatel",'a'=>"user"),
    array('n'=>"Text",'a'=>"text"),
    array('n'=>input_button("comm_filter","Filtr"),'a'=>false)
),$_SESSION->comments_sort,$_SESSION->comments_sortmode);

function formattext($str) {
    $ret=array();
    foreach(explode("\n",$str) as $val) $ret[]=htmlspecialchars(strtr($val,array("\r"=>"")));
    return implode("<br />",$ret);
}

while($fe=$qe->obj()) {
    echo "<tr><td>".$fe->cm_id."</td><td>".input_check("comm_chk[".$fe->cm_id."]").input_hidden("comm_hid[]",$fe->cm_id)."</td>
	<td>".showtime($fe->cm_date)."</td>
	<td>".htmlspecialchars($fe->b_city)."</td>
	<td>".htmlspecialchars($fe->b_name)."</td>
	<td>".htmlspecialchars($fe->r_desc)."</td>
	<td>".htmlspecialchars($fe->r_floor)."</td>
	<td>".htmlspecialchars($fe->m_desc)."</td>
	<td>".htmlspecialchars($fe->u_fullname)."</td>
	<td>".formattext($fe->cm_text)."</td>
	<td>".input_button("comm_edit[".$fe->cm_id."]","Editovat")." ".input_button("comm_del[".$fe->cm_id."]","Smazat")."</td></tr>";
}

echo "</table>";
$tbl=ob_get_clean();

$qe=$SQL->query("select FOUND_ROWS() as rows");
$fe=$qe->obj();
$totalrows=$fe->rows;
if($totalrows) pages($totalrows,$_SESSION->comments_currpage,"<a href=\"".root().$PAGE."/page/%d\">%d</a>");
echo $tbl;
if($totalrows) {
    pages($totalrows,$_SESSION->comments_currpage,"<a href=\"".root().$PAGE."/page/%d\">%d</a>");
    echo input_button("comm_rem","Smazat vybrané")." ".input_button("comm_remall","Smazat zobrazené");
}

echo "<br /><a href=\"".root().$PAGE."/csv\">Uložit jako csv</a>";

echo "<script type=\"text/javascript\">
// <![CDATA[
var todel=false;
function commsgui() {
    $(\".pagep a\").button();
    $(\".pagep b\").button({disabled:true});
    $(\"button\").button().click(function() {
	var bid=$(this).attr('id');
	if(bid.match(/^comm_del\\[\\d+\\]$/)) todel=true;
    });
    $(\"#commform\").submit(function() {
	if(todel) {
	    todel=false;
	    return confirm('Opravdu nenávratně smazat komentář(e)?');
	}
    });
    $(\"#comm_rem\").click(function() {
	todel=true;
    });
    $(\"#comm_remall\").click(function() {
	todel=true;
    });
}
// ]]>
</script>";
    $_JQUERY[]="commsgui();";

echo "</form>";

if($_SERVER['REQUEST_METHOD']=="POST") {
    $_SESSION->invalid=false;
    $_SESSION->temp_form=false;

    if(get_ind($_POST,"comm_filter")) {
	$_SESSION->comments_filterenable=!$_SESSION->comments_filterenable;
	if($_SESSION->comments_filterenable) $_SESSION->comments_currpage=0;
	redir();
    }
    if(get_ind($_POST,"comm_fall")) {
	$_SESSION->comments_filter=false;
	redir();
    }
    if(get_ind($_POST,"comm_fapply")) {
	postcheck($ITEMS,$_POST);
	$_SESSION->comments_filter=$_POST;
	$_SESSION->comments_currpage=0;
	redir();
    }
    if(get_ind($_POST,"comm_new")) {
    // fill it with filter settings
	$m=date("i");
	$_SESSION->temp_form=array(
	    "001_comm_date"=>showdate(time()),
	    "001_comm_date_h"=>date("H"),
	    "001_comm_date_m"=>sprintf("%02d",$m-($m%5))
	);
	if($_SESSION->comments_filterenable) { // prefill for new comment
	    $_SESSION->temp_form['001_comm_city']=get_ind($_SESSION->comments_filter,"000_comm_filter_city");
	    $_SESSION->temp_form['001_ajax_build']=get_ind($_SESSION->comments_filter,"001_ajax_build");
	    $_SESSION->temp_form['001_ajax_room']=get_ind($_SESSION->comments_filter,"001_ajax_room");
	    $_SESSION->temp_form['001_ajax_meas']=get_ind($_SESSION->comments_filter,"001_ajax_meas");
	}
	redir(root()."commenttab/edit/0");
    }
    if(get_ind($_POST,"comm_edit")) {
	$key=$_POST['comm_edit'];
	if(is_array($key)) {
	    $key=(int)key($key);
	    if($limitacc) $qe=$SQL->query("select * from comment left join measuring on cm_mid=m_id left join room on m_rid=r_id left join building on r_bid=b_id left join user on cm_uid=u_id where cm_id=".$key." && cm_uid=".uid());
	    else $qe=$SQL->query("select * from comment left join measuring on cm_mid=m_id left join room on m_rid=r_id left join building on r_bid=b_id left join user on cm_uid=u_id where cm_id=".$key);
	    $fe=$qe->obj();
	    if($fe) {
		$_SESSION->temp_form=array(
		    "001_comm_city"=>bin2hex($fe->b_city),
		    "001_ajax_build"=>$fe->b_id,
		    "001_ajax_room"=>$fe->r_id,
		    "001_ajax_meas"=>$fe->m_id,
		    "001_comm_date"=>showdate($fe->cm_date),
		    "001_comm_date_h"=>date("H",$fe->cm_date),
		    "001_comm_date_m"=>date("i",$fe->cm_date),
		    "001_comm_text"=>$fe->cm_text
		);
		redir(root()."commenttab/edit/".$key);
	    } else $_SESSION->error_text="Komentář nenalezen";
	}
	redir();
    }
    if(get_ind($_POST,"comm_del")) {
	$key=$_POST['comm_del'];
	if(is_array($key)) {
	    $key=(int)key($key);
	    if($limitacc) $SQL->query("delete from comment where cm_id=".$key." && cm_uid=".uid());
	    else $SQL->query("delete from comment where cm_id=".$key);
	    $_SESSION->error_text="Komentář smazán";
	}
	redir();
    }
    if(get_ind($_POST,"comm_rem")) {
	$ids=get_ind($_POST,"comm_chk");
	if(is_array($ids)) {
	    $cids=array();
	    foreach($ids as $key=>$val) {
		if($val=='Y') $cids[]="\"".$SQL->escape($key)."\"";
	    }
	    if(count($cids)) {
		if($limitacc) $SQL->query("delete from comment where cm_id in (".implode(",",$cids).") && cm_uid=".uid());
		else $SQL->query("delete from comment where cm_id in (".implode(",",$cids).")");
		if(count($cids)>1) $_SESSION->error_text="Komentáře smazány";
		else $_SESSION->error_text="Komentář smazán";
	    }
	}
	redir();
    }
    if(get_ind($_POST,"comm_remall")) {
	$ids=get_ind($_POST,"comm_hid");
	if(is_array($ids)) {
	    $cids=array();
	    foreach($ids as $val) $cids[]="\"".$SQL->escape($val)."\"";
	    if(count($cids)) {
		if($limitacc) $SQL->query("delete from comment where cm_id in (".implode(",",$cids).") && cm_uid=".uid());
		else $SQL->query("delete from comment where cm_id in (".implode(",",$cids).")");
		if(count($cids)>1) $_SESSION->error_text="Komentáře smazány";
		else $_SESSION->error_text="Komentář smazán";
	    }
	}
	redir();
    }
    redir();
}
