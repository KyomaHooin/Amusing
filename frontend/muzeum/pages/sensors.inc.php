<?php

pageperm();
showmenu();

showerror();

ajaxsess();

switch($ARGC) {
case 2:
    switch($ARGV[0]) {
    case "sort":
	if($_SESSION->sensor_sort==$ARGV[1]) $_SESSION->sensor_sortmode=!$_SESSION->sensor_sortmode;
	$_SESSION->sensor_sort=$ARGV[1];
	redir();
    case "page":
	$_SESSION->sensor_currpage=(int)$ARGV[1];
	redir();
    }
    break;
}

$ord=array();
switch($_SESSION->sensor_sort) {
case "desc":
    $ord[]="s_desc ".($_SESSION->sensor_sortmode?"desc":"asc");
    break;
case "model":
    $ord[]="sm_name ".($_SESSION->sensor_sortmode?"desc":"asc");
    break;
case "mdesc":
    $ord[]="m_desc ".($_SESSION->sensor_sortmode?"desc":"asc");
    break;
case "type":
    $ord[]="st_desc ".($_SESSION->sensor_sortmode?"desc":"asc");
    break;
case "zone":
    $ord[]="s_timezone ".($_SESSION->sensor_sortmode?"desc":"asc");
    break;
case "build":
    $ord[]="b_name ".($_SESSION->sensor_sortmode?"desc":"asc");
    break;
case "city":
    $ord[]="b_city ".($_SESSION->sensor_sortmode?"desc":"asc");
    break;
case "room":
    $ord[]="r_desc ".($_SESSION->sensor_sortmode?"desc":"asc");
    break;
case "floor":
    $ord[]="r_floor ".($_SESSION->sensor_sortmode?"desc":"asc");
    break;
case "act":
    $ord[]="s_active ".($_SESSION->sensor_sortmode?"desc":"asc");
    break;
default:
    $_SESSION->sensor_sort="serial";
}
$ord[]="s_serial ".($_SESSION->sensor_sortmode?"desc":"asc");

echo "<form id=\"senform\" action=\"".root().$PAGE."\" method=\"post\">";

echo input_button("sen_new","Nový senzor","newbutton");

$whr=array();
if($_SESSION->sensor_filterenable) {
    echo "<fieldset><legend>Filtr</legend>";
    echo "<table class=\"nobr\">";
    
    $opts=array(0=>"Všechny modely");
    $qe=$SQL->query("select * from sensormodel order by sm_name");
    while($fe=$qe->obj()) $opts[$fe->sm_id]=$fe->sm_name." ".$fe->sm_vendor;
    echo "<tr><td>Model:&nbsp;</td><td>".input_select("000_sen_filter_model",$opts,get_ind($_SESSION->sensor_filter,"000_sen_filter_model"))."</td></tr>";
    
    echo "<tr><td>Sériové číslo:&nbsp;</td><td>".input_text("000_sen_filter_serial",get_ind($_SESSION->sensor_filter,"000_sen_filter_serial"),"finput")."</td></tr>";
    echo "<tr><td>Popis:&nbsp;</td><td>".input_text("000_sen_filter_desc",get_ind($_SESSION->sensor_filter,"000_sen_filter_desc"),"finput")."</td></tr>";

    $opts=array(0=>"Všechny typy");
    $qe=$SQL->query("select * from sensortype order by st_desc");
    while($fe=$qe->obj()) {
	$opts[$fe->st_id]=$fe->st_desc;
    }
    echo "<tr><td>Typ:&nbsp;</td><td>".input_select("000_sen_filter_type",$opts,get_ind($_SESSION->sensor_filter,"000_sen_filter_type"))."</td></tr>";

// ---------------
    $opts=array(0=>"Všechna města");
    $qe=$SQL->query("select * from building group by b_city order by b_city");
    while($fe=$qe->obj()) {
	$opts[bin2hex($fe->b_city)]=$fe->b_city;
    }
    $sb=get_ind($_SESSION->sensor_filter,"000_sen_filter_city");
    echo "<tr><td>Město:&nbsp;</td><td>".input_select("000_sen_filter_city",$opts,$sb)."</td></tr>";
    
    $opts=array(0=>"Všechny budovy");
    if($sb) {
	$qe=$SQL->query("select * from building where b_city=\"".$SQL->escape(hex2bin($sb))."\" order by b_name");
	while($fe=$qe->obj()) {
	    $opts[$fe->b_id]=$fe->b_name;
	}
	$sb=get_ind($_SESSION->sensor_filter,"001_ajax_build");
    } else $sb=false;
    echo "<tr><td>Budova:&nbsp;</td><td><span id=\"measbuildc\">".input_select("001_ajax_build",$opts,$sb)."</span></td></tr>";
    $opts=array(0=>"Všechny místnosti");
    if($sb) {
	$qe=$SQL->query("select * from room where r_bid=\"".$SQL->escape($sb)."\" order by r_desc");
	while($fe=$qe->obj()) {
	    $opts[$fe->r_id]=$fe->r_desc;
	}
    }
    echo "<tr><td>Místnost:&nbsp;</td><td><span id=\"measroomc\">".input_select("001_ajax_room",$opts,get_ind($_SESSION->measpoint_filter,"001_ajax_room"))."</span></td></tr>";
    echo "</table>";

    echo input_button("sen_fapply","Použít")." ".input_button("sen_fall","Zobrazit vše");
    echo "</fieldset>";
    
    $ftmp=get_ind($_SESSION->sensor_filter,"000_sen_filter_desc");
    if($ftmp) $whr[]="s_desc like \"%".$SQL->escape($ftmp)."%\"";
    $ftmp=get_ind($_SESSION->sensor_filter,"000_sen_filter_serial");
    if($ftmp) $whr[]="s_serial like \"%".$SQL->escape($ftmp)."%\"";
    $fb=get_ind($_SESSION->sensor_filter,"001_ajax_build");
    if($fb) $whr[]="b_id=\"".$SQL->escape($fb)."\"";
    $fb=get_ind($_SESSION->sensor_filter,"001_ajax_room");
    if($fb) $whr[]="r_id=\"".$SQL->escape($fb)."\"";
    $fb=get_ind($_SESSION->sensor_filter,"000_sen_filter_type");
    if($fb) $whr[]="s_type=\"".$SQL->escape($fb)."\"";
    $fb=get_ind($_SESSION->sensor_filter,"000_sen_filter_model");
    if($fb) $whr[]="s_model=\"".$SQL->escape($fb)."\"";
    $ftmp=get_ind($_SESSION->sensor_filter,"000_sen_filter_city");
    if($ftmp) $whr[]="b_city=\"".$SQL->escape(hex2bin($ftmp))."\"";

    echo "<script type=\"text/javascript\">
// <![CDATA[
function buildchange() {
    $.get(\"".root()."ajax/getroomsel2/\"+$(\"#001_ajax_build\").val(),function(data) {
	$(\"#measroomc\").html(data);
    });
}
function buildsub() {
    $(\"#001_ajax_build\").change(buildchange);
    $(\"#000_sen_filter_city\").change(function() {
	$.get(\"".root()."ajax/getbuildsel2/\"+$(this).val(),function(data) {
	    $(\"#measbuildc\").html(data);
	    $(\"#001_ajax_build\").change(buildchange);
	    buildchange();
	});
    });
}
// ]]>
</script>";
    $_JQUERY[]="buildsub();";
}

ob_start();
$offset=(int)($_SESSION->sensor_currpage*$_PERPAGE);
$limit=(int)$_PERPAGE;
echo "<table>";
sortlocalref(array(
    array('n'=>"#",'a'=>false),
    array('n'=>"Model",'a'=>"model"),
    array('n'=>"Sériové číslo",'a'=>"serial"),
    array('n'=>"Typ",'a'=>"type"),
    array('n'=>"Popis",'a'=>"desc"),
    array('n'=>"Zóna",'a'=>"zone"),
    array('n'=>"Igorovat DST",'a'=>false),
    array('n'=>"Měřící bod",'a'=>"mdesc"),
    array('n'=>"Město",'a'=>"city"),
    array('n'=>"Budova",'a'=>"build"),
    array('n'=>"Místnost",'a'=>"room"),
    array('n'=>"Patro",'a'=>"floor"),
    array('n'=>"akt.",'a'=>"act"),
    array('n'=>input_button("sen_filter","Filtr"),'a'=>false)
),$_SESSION->sensor_sort,$_SESSION->sensor_sortmode);

$qe=$SQL->query("select SQL_CALC_FOUND_ROWS * from sensor left join sensormodel on s_model=sm_id left join sensortype on s_type=st_id left join measuring on s_mid=m_id left join room on m_rid=r_id left join building on r_bid=b_id ".(count($whr)?"where ".implode(" && ",$whr):"")." order by ".implode(",",$ord)." limit ".$offset.",".$limit);
$qer=$SQL->query("select FOUND_ROWS() as rows");
$fe=$qer->obj();
$totalrows=$fe->rows;

while($fe=$qe->obj()) {
    echo "<tr>
	<td>".$fe->s_id."</td>
	<td>".htmlspecialchars($fe->sm_name." ".$fe->sm_vendor)."</td>
	<td>".htmlspecialchars($fe->s_serial)."</td>
	<td>".htmlspecialchars($fe->st_desc)."</td>
	<td>".htmlspecialchars($fe->s_desc)."</td>
	<td>".htmlspecialchars($fe->s_timezone)."</td>
	<td>".input_check("sen_igndst[".$fe->s_id."]",'Y',$fe->s_ignoredst=='Y',false,true)."</td>
	<td>".htmlspecialchars($fe->m_desc)."</td>
	<td>".htmlspecialchars($fe->b_city)."</td>
	<td>".htmlspecialchars($fe->b_name)."</td>
	<td>".htmlspecialchars($fe->r_desc)."</td>
	<td>".htmlspecialchars($fe->r_floor)."</td>
	<td>".input_check("sen_av[".$fe->s_id."]",'Y',$fe->s_active=='Y',false,true)."</td>
	<td>".input_button("sen_edit[".$fe->s_id."]","Editovat");
	
	$stt=false;
	if($fe->st_class) {
	    $stt=new $fe->st_class();
	    $stt->fe=$fe;
	}
	if($fe->s_mid && $stt && $stt->canimport()) echo " ".input_button("sen_import[".$fe->s_id."]","Importovat data");
	if(urole()=='A') echo " ".input_button("sen_del[".$fe->s_id."]","Smazat senzor");
	echo "</td></tr>";
}

echo "</table>";
$tbl=ob_get_clean();
if($totalrows) pages($totalrows,$_SESSION->sensor_currpage,"<a href=\"".root().$PAGE."/page/%d\">%d</a>");
echo $tbl;
if($totalrows) pages($totalrows,$_SESSION->sensor_currpage,"<a href=\"".root().$PAGE."/page/%d\">%d</a>");

echo "<script type=\"text/javascript\">
// <![CDATA[
var todel=false;
function sensorsgui() {
    $(\"button\").button().click(function() {
	var bid=$(this).attr('id');
	if(bid.match(/^sen_del\\[\\d+\\]$/)) todel=true;
    });
    $(\"#senform\").submit(function() {
	if(todel) {
	    todel=false;
	    return confirm('Opravdu nenávratně smazat senzor?');
	}
    });
    $(\".pagep a\").button();
    $(\".pagep b\").button({disabled:true});
}
// ]]>
</script>";
    $_JQUERY[]="sensorsgui();";

echo "</form>";

function sendelete($sid) {
    global $SQL;
    $qe=$SQL->query("show tables");
    while($fe=$qe->row()) { // maybe not needed
	if(preg_match("/^rawvalues_\\d+$/",$fe[0])) {
	    $SQL->query("update ".$fe[0]." set rv_sid=0 where rv_sid=".$sid);
	}
    }
    $SQL->query("delete from sensor where s_id=".$sid);
}

if($_SERVER['REQUEST_METHOD']=="POST") {
    $_SESSION->invalid=false;
    $_SESSION->temp_form=false;

    if(get_ind($_POST,"sen_new")) {
	$_SESSION->temp_form=array("001_sen_zone"=>"Europe/Prague","000_sen_absh"=>"Y");
	redir(root()."sensortab/edit/0");
    }
    if(get_ind($_POST,"sen_edit")) {
	if(is_array($_POST['sen_edit'])) {
	    $sensoredit=(int)key($_POST['sen_edit']);
	    if($sensoredit) {
		$qe=$SQL->query("select * from sensor left join measuring on s_mid=m_id left join room on m_rid=r_id left join building on r_bid=b_id where s_id=".$sensoredit);
		$fe=$qe->obj();
		if(!$fe) {
		    $_SESSION->error_text="Senzor nenalezen";
		    redir();
		} else {
		    $stt=c_stype_base::getsensorbyid($fe->s_type);
		    if(!$stt) {
			$_SESSION->error_text="Invalidní typ senzoru";
			redir();
		    }
		    $_SESSION->temp_form=array(
			"001_sen_serial"=>$fe->s_serial,
			"001_sen_model"=>$fe->s_model,
			"000_sen_desc"=>$fe->s_desc,
			"001_sen_type"=>$fe->s_type,
			"001_sen_city"=>bin2hex($fe->b_city),
			"001_ajax_build"=>$fe->b_id,
			"001_ajax_room"=>$fe->r_id,
			"001_ajax_meas"=>$fe->m_id,
			"000_sen_state"=>$fe->s_active
		    );
		    $stt->addtempform($fe);
		    redir(root()."sensortab/edit/".$sensoredit);
		}
	    }
	}
	redir();
    }
    if(get_ind($_POST,"sen_filter")) {
	$_SESSION->sensor_filterenable=!$_SESSION->sensor_filterenable;
	if($_SESSION->sensor_filterenable) $_SESSION->sensor_currpage=0;
	redir();
    }
    if(get_ind($_POST,"sen_fall")) {
	$_SESSION->sensor_filter=false;
	redir();
    }
    if(get_ind($_POST,"sen_fapply")) {
	postcheck($ITEMS,$_POST);
	$_SESSION->sensor_filter=$_POST;
	$_SESSION->sensor_currpage=0;
	redir();
    }
    if(get_ind($_POST,"sen_del")) {
	if(urole()!='A') {
	    $_SESSION->error_text="přístup odepřen";
	    redir();
	}
	$si=get_ind($_POST,"sen_del");
	if(is_array($si)) {
	    $sid=(int)key($si);
	    // HC
	    sendelete($sid);
	    $_SESSION->error_text="Senzor smazán";
	}
	redir();
    }
    if(get_ind($_POST,"sen_import")) {
	$si=get_ind($_POST,"sen_import");
	if(is_array($si)) {
	    $_SESSION->prevpage=$PAGE;
	    redir(root()."import/".key($si));
	}
	redir();
    }
    redir();
}
