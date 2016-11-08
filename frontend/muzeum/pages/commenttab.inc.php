<?php

pageperm();
showmenu();

showerror();

ajaxsess();

function backredir($e=false) {
    if($e) $_SESSION->error_text=$e;
    if(strlen($_SESSION->prevpage)) redir(root().$_SESSION->prevpage);
    redir(root()."comments");
}

if($ARGC!=2) backredir();
if($ARGV[0]!="edit" || !is_numeric($ARGV[1])) backredir();
$commedit=(int)$ARGV[1];
if($commedit) {
    switch(urole()) {
    case 'A':
    case 'D':
	$qe=$SQL->query("select * from comment where cm_id=".$commedit);
	break;
    default:
	$qe=$SQL->query("select * from comment where cm_id=".$commedit." && cm_uid=".uid());
    }
    $fe=$qe->obj();
    if(!$fe) backredir();
}

echo "<form action=\"".root().$PAGE."/edit/".$commedit."\" method=\"post\" enctype=\"multipart/form-data\">";

echo "<fieldset><legend>".($commedit?"Editace komentáře":"Nový komentář")."</legend>";
echo "<table class=\"nobr\">";

	$opts=array(0=>"Zvolte město");
	$qe=$SQL->query("select * from building group by b_city order by b_city");
	while($fe=$qe->obj()) {
	    $opts[bin2hex($fe->b_city)]=$fe->b_city;
	}
	echo "<tr><td>Město:&nbsp;</td><td>".input_select_temp_err("001_comm_city",$opts)."</td></tr>";
	
	$opts=array(0=>"Zvolte budovu");
	$sb=get_temp("001_comm_city");
	if($sb) {
	    $qe=$SQL->query("select * from building where b_city=\"".$SQL->escape(my_hex2bin($sb))."\" order by b_name");
	    while($fe=$qe->obj()) {
		$opts[$fe->b_id]=$fe->b_name;
	    }
	}
	echo "<tr><td>Budova:&nbsp;</td><td><span id=\"measbuildc\">".input_select_temp_err("001_ajax_build",$opts)."</span></td></tr>";
	$opts=array(0=>"Zvolte místnost");
	$sb=get_temp("001_ajax_build");
	if($sb) { // printout rooms
	    $qe=$SQL->query("select * from room where r_bid=\"".$SQL->escape($sb)."\" order by r_desc");
	    while($fe=$qe->obj()) {
		$opts[$fe->r_id]=$fe->r_desc;
	    }
	}
	echo "<tr><td>Místnost:&nbsp;</td><td><span id=\"measroomc\">".input_select_temp_err("001_ajax_room",$opts)."</span></td></tr>";
	
	$opts=array(0=>"Zvolte měřící bod");
	$sb=get_temp("001_ajax_room");
	if($sb) {
	    $qe=$SQL->query("select * from measuring where m_rid=\"".$SQL->escape($sb)."\" order by m_desc");
	    while($fe=$qe->obj()) {
		$opts[$fe->m_id]=$fe->m_desc;
	    }
	}
	echo "<tr><td>Měřící bod:&nbsp;</td><td><span id=\"senmeasc\">".input_select_temp_err("001_ajax_meas",$opts)."</span></td></tr>";

    $hours=array();
    for($i=0;$i<24;$i++) $hours[$i]=sprintf("%02d",$i);
    $mins=array();
    for($i=0;$i<60;$i+=5) $mins[$i]=sprintf("%02d",$i);

	echo "<tr><td>Datum a čas:&nbsp;</td><td>".input_text_temp_err("001_comm_date")."&nbsp;".input_select_temp_err("001_comm_date_h",$hours).":".input_select_temp_err("001_comm_date_m",$mins)."</td></tr>";
	echo "<tr><td>Text:&nbsp;</td><td>".input_area_temp_err("001_comm_text","farea")."</td></tr>";

echo "</table>";

echo input_button("comm_save","Uložit")." ".input_button("comm_cancel","Storno");
echo "</fieldset>";

echo "<script type=\"text/javascript\">
// <![CDATA[
function roomchange() {
    $.get(\"".root()."ajax/getmeassel/\"+$(\"#001_ajax_room\").val(),function(data) {
	$(\"#senmeasc\").html(data);
    });
}
function buildchange() {
    $.get(\"".root()."ajax/getroomsel/\"+$(\"#001_ajax_build\").val(),function(data) {
	$(\"#measroomc\").html(data);
	$(\"#001_ajax_room\").change(roomchange);
	roomchange();
    });
}
function commgui() {
    $(\"button\").button();
    $(\"#001_comm_date\").datepicker({dateFormat: \"yy-mm-dd\", changeMonth: true, changeYear: true, yearRange: \"2000:2050\"});

    $(\"#001_comm_city\").change(function() {
	$.get(\"".root()."ajax/getbuildsel/\"+$(this).val(),function(data) {
	    $(\"#measbuildc\").html(data);
	    $(\"#001_ajax_build\").change(buildchange);
	    buildchange();
	});
    });
    $(\"#001_ajax_build\").change(buildchange);
    $(\"#001_ajax_room\").change(roomchange);
";
if($commedit) echo "$(\"#000_var_code\").prop(\"disabled\",true)";
echo "
}
// ]]>
</script>";
    $_JQUERY[]="commgui();";

echo "</form>";

function sredir() {
    global $PAGE;
    global $commedit;
    redir(root().$PAGE."/edit/".$commedit);
}

if($_SERVER['REQUEST_METHOD']=="POST") {
    $_SESSION->invalid=false;
    $_SESSION->temp_form=false;

    if(get_ind($_POST,"comm_cancel")) {
	backredir();
    }
    if(get_ind($_POST,"comm_save")) {
	$rerr=postcheck($ITEMS,$_POST);
	
	$mid=(int)get_ind($_POST,"001_ajax_meas");
	$qe=$SQL->query("select * from measuring where m_id=".$mid);
	if(!$qe->rowcount() && !get_ind($rerr,"001_ajax_meas")) $rerr['001_ajax_meas']="Neexistující měřící bod";
	
	$cdate=gettime(get_ind($_POST,"001_comm_date")." ".get_ind($_POST,"001_comm_date_h").":".get_ind($_POST,"001_comm_date_m"));
	if($cdate===false) {
	    $rerr['001_comm_date']="Nastavte validní datum a čas";
	    $rerr['001_comm_date_m']="Nastavte validní datum a čas";
	    $rerr['001_comm_date_h']="Nastavte validní datum a čas";
	}
	
	if(count($rerr)) {
	    $_SESSION->error_text=reset($rerr);
	    $_SESSION->invalid=$rerr;
	    $_SESSION->temp_form=$_POST;
	    sredir();
	}
	if(!$commedit) {
	    $SQL->query("insert into comment set
		cm_mid=".$mid.",
		cm_date=".$cdate.",
		cm_uid=".uid().",
		cm_text=\"".$SQL->escape(get_ind($_POST,"001_comm_text"))."\"");
	    switch($SQL->errnum) {
	    case 0:
		break;
	    default:
		backredir("Chyba databáze");
	    }
	} else {
	    $SQL->query("update comment set
		cm_mid=".$mid.",
		cm_date=".$cdate.",
		cm_text=\"".$SQL->escape(get_ind($_POST,"001_comm_text"))."\"
		where cm_id=".$commedit); // dont change user
	    switch($SQL->errnum) {
	    case 0:
		break;
	    default:
		backredir("Chyba databáze");
	    }
	}
	backredir("Komentář uložen");
    }
    sredir();
}
