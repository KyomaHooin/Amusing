<?php

pageperm();
showmenu();

showerror();

ajaxsess();

if($ARGC!=2) redir(root()."alarmspreset");
if($ARGV[0]!="edit" || !is_numeric($ARGV[1])) redir(root()."alarmspreset");
$apedit=(int)$ARGV[1];

echo "<form action=\"".root().$PAGE."/edit/".$apedit."\" method=\"post\" enctype=\"multipart/form-data\">";

	echo "<fieldset><legend>".($apedit?"Editace definice alarmu":"Nová definice alarm")."</legend>";

	echo "<table class=\"nobr\">";
	echo "<tr><td>Popis:&nbsp;</td><td>".input_text_temp_err("001_ap_desc","finput")."</td></tr>";
	
	echo "<tr><td>Email:&nbsp;</td><td>".input_area_temp_err("001_ap_email","farea")."</td></tr>";

	$aopts=array(0=>"Zvolte typ");
	foreach(c_alarm_gen::getalltypes() as $key=>$val) $aopts[$key]=$val;
	echo "<tr><td>Typ alarmu:&nbsp;</td><td>".input_select_temp_err("001_ap_type",$aopts)."</td></tr>";
	echo "</table><hr />";

	echo "<span id=\"apform\"></span>";
	
	echo input_button("ap_save","Uložit")." ".input_button("ap_cancel","Zpět");
	echo "</fieldset>";

    echo "<script type=\"text/javascript\">
// <![CDATA[
function getatype(at) {
    $.get(\"".root()."ajaxalarm/form/\"+at,function(data) {
	$(\"#apform\").html(data);
    });
}
function aptype() {
    getatype($(\"#001_ap_type\").val());
    $(\"#001_ap_type\").change(function() {
	getatype($(this).val());
    });

    $(\"button\").button();
}
// ]]>
</script>";
    $_JQUERY[]="aptype();";

echo "</form>";

function sredir() {
    global $PAGE;
    global $apedit;
    redir(root().$PAGE."/edit/".$apedit);
}

if($_SERVER['REQUEST_METHOD']=="POST") {
    $_SESSION->invalid=false;
    $_SESSION->temp_form=false;
    
    if(get_ind($_POST,"ap_cancel")) {
	redir(root()."alarmspreset");
    }
    if(get_ind($_POST,"ap_save")) {
	$rerr=postcheck($ITEMS,$_POST);
	$at=get_ind($_POST,"001_ap_type");
	if($at=="0") $rerr['001_ap_type']="Nezvolen typ alarmu";
	
	$ems=get_ind($_POST,"001_ap_email");
	if(strlen($ems)) {
	    foreach(explode("\n",$ems) as $val) {
		foreach(explode(",",$val) as $e) {
		    $t=trim($e);
		    if(strlen($t) && !preg_match("/[a-zA-Z0-9\.\-_]+@[a-zA-Z0-9\.\-_]+\.[a-zA-Z]+/",$t)) {
			$rerr['001_ap_email']="<b>Nesprávně vyplněné položky.</b>";
			break;
		    }
		}
	    }
	}
	
	$alrm=c_alarm_gen::getalarmbyname($at);
	if(!$alrm) $rerr['001_ap_type']="Neexistující typ alarmu";
	else $alrm->checkform($rerr);
	if(count($rerr)) {
	    $_SESSION->error_text=reset($rerr);
	    $_SESSION->invalid=$rerr;
	    $_SESSION->temp_form=$_POST;
	    sredir();
	}
	$alrm->savedef($apedit,get_ind($_POST,"001_ap_desc"),get_ind($_POST,"001_ap_email"));
	$_SESSION->error_text="Definice uložena";
	redir(root()."alarmspreset");
    }
    sredir();
}
