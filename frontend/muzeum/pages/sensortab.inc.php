<?php

pageperm();
showmenu();

showerror();

ajaxsess();

if($ARGC!=2) redir(root()."sensors");
if($ARGV[0]!="edit" || !is_numeric($ARGV[1])) redir(root()."sensors");
$sensoredit=(int)$ARGV[1];
if($sensoredit) {
    $qe=$SQL->query("select * from sensor where s_id=".$sensoredit);
    $fe=$qe->obj();
    if(!$fe) redir(root()."sensors");
}

echo "<form id=\"formsentab\" action=\"".root().$PAGE."/edit/".$sensoredit."\" method=\"post\" enctype=\"multipart/form-data\">";

	echo "<fieldset><legend>".($sensoredit?"Editace senzoru #".$sensoredit:"Nový senzor")."</legend>";
	echo "<table class=\"nobr\">";

	$opts=array(0=>"Zvolte město");
	$qe=$SQL->query("select * from building group by b_city order by b_city");
	while($fe=$qe->obj()) {
	    $opts[bin2hex($fe->b_city)]=$fe->b_city;
	}
	echo "<tr><td>Město:&nbsp;</td><td>".input_select_temp_err("001_sen_city",$opts)."</td></tr>";
	
	$opts=array(0=>"Zvolte budovu");
	$sb=get_temp("001_sen_city");
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
	
	$opts=array(0=>"Zvolte model");
	$qe=$SQL->query("select * from sensormodel order by sm_name");
	while($fe=$qe->obj()) $opts[$fe->sm_id]=$fe->sm_name." ".$fe->sm_vendor;
	echo "<tr><td>Model:&nbsp;</td><td>".input_select_temp_err("001_sen_model",$opts)."</td></tr>";
	
	echo "<tr><td>Sériové číslo:&nbsp;</td><td>".input_text_temp_err("001_sen_serial","finput")."</td></tr>";
	echo "<tr><td>Popis:&nbsp;</td><td>".input_text_temp_err("000_sen_desc","finput")."</td></tr>";
	
	$opts=array(0=>"Zvolte typ");
	$qe=$SQL->query("select * from sensortype order by st_desc");
	while($fe=$qe->obj()) {
	    $opts[$fe->st_id]=$fe->st_desc;
	}
	echo "<tr><td>Typ:&nbsp;</td><td>".input_select_temp_err("001_sen_type",$opts)."</td></tr>";
	echo "</table>";
	echo "<hr />";
	
	echo "<span id=\"sen_tform\"></span>";

	echo "<table class=\"nobr\"><tr><td>Povoleno:&nbsp;</td><td>".input_check_temp("000_sen_state",'Y')."</td></tr></table>";
	
	echo input_button("sen_save","Uložit")." ".input_button("sen_cancel","Storno");
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
var tocheck=false;
function getsentype(at) {
    $.get(\"".root()."ajaxstype/".($sensoredit?"form":"newform")."/\"+at,function(data) {
	$(\"#sen_tform\").html(data);
    });
}
function sengui() {
    $(\"#001_sen_city\").change(function() {
	$.get(\"".root()."ajax/getbuildsel/\"+$(this).val(),function(data) {
	    $(\"#measbuildc\").html(data);
	    $(\"#001_ajax_build\").change(buildchange);
	    buildchange();
	});
    });
    $(\"#001_ajax_build\").change(buildchange);
    $(\"#001_ajax_room\").change(roomchange);
    
    $(\"#sen_save\").click(function() {
	tocheck=true;
    });
    $(\"#sen_cancel\").click(function() {
	tocheck=false;
    });
    $(\"form\").submit(function() {
	if(tocheck) {
	    $.getJSON(\"".root()."ajax/checksenmeas/".$sensoredit."/\"+$(\"#001_ajax_meas\").val(),function(data) {
		var input=$(\"<input>\").attr(\"type\",\"hidden\").attr(\"name\",\"sen_save\").val(\"sent\");
		if(data.mbusy) {
		    if(confirm('Zvolený měřící bod již senzor má, přepsat ?')) {
			tocheck=false;
			$(\"form\").append($(input));
			$(\"form\").submit();
		    }
		} else {
		    tocheck=false;
		    $(\"form\").append($(input));
		    $(\"form\").submit();
		}
	    });
	    return false;
	}
	return true;
    });
    getsentype($(\"#001_sen_type\").val());
    $(\"#001_sen_type\").change(function() {
	getsentype($(this).val());
    });
    $(\"button\").button();
}
// ]]>
</script>";
    $_JQUERY[]="sengui();";

echo "</form>";

function sredir() {
    global $PAGE;
    global $sensoredit;
    redir(root().$PAGE."/edit/".$sensoredit);
}

if($_SERVER['REQUEST_METHOD']=="POST") {
    $_SESSION->invalid=false;
    $_SESSION->temp_form=false;

    if(get_ind($_POST,"sen_cancel")) {
	redir(root()."sensors");
    }
    if(get_ind($_POST,"sen_save")) {
	$rerr=postcheck($ITEMS,$_POST);
	
	$meas=(int)get_ind($_POST,"001_ajax_meas"); // optional, in case of something, check that, measuring point can have only one sensor
	if($meas) {
	    $qe=$SQL->query("select * from measuring where m_id=\"".$SQL->escape($meas)."\"");
	    if(!$qe->rowcount()) $rerr['001_ajax_meas']="Invalidní měřící bod";
	}
	
	$stt=c_stype_base::getsensorbyid(get_ind($_POST,"001_sen_type"));
	if($stt) $stt->checkform($rerr);
	else $rerr['001_sen_type']="Zvolte typ senzoru";
	
	$smodel=get_ind($_POST,"001_sen_model");
	if(!$smodel) $rerr['001_sen_model']="Nezvolen model senzoru";
	else {
	    $qe=$SQL->query("select * from sensormodel where sm_id=\"".$SQL->escape($smodel)."\"");
	    $fe=$qe->obj();
	    if(!$fe) $rerr['001_sen_model']="Neexistující model senzoru";
	    else $smodel=$fe->sm_id;
	}
	
	if(count($rerr)) {
	    $_SESSION->error_text=reset($rerr);
	    $_SESSION->invalid=$rerr;
	    $_SESSION->temp_form=$_POST;
	    sredir();
	}
	
	if(!$sensoredit) {
	    if(!$stt->newsensor($meas,$smodel)) sredir();
	} else {
	    if(!$stt->editsensor($sensoredit,$meas,$smodel)) sredir();
	}
	redir(root()."sensors");
    }
    sredir();
}
