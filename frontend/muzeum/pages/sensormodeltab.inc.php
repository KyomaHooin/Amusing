<?php

pageperm();
showmenu();

showerror();

ajaxsess();

if($ARGC!=2) redir(root()."sensormodels");
if($ARGV[0]!="edit" || !is_numeric($ARGV[1])) redir(root()."sensormodels");
$smedit=(int)$ARGV[1];
if($smedit) {
    $qe=$SQL->query("select * from sensormodel where sm_id=".$smedit);
    $fe=$qe->obj();
    if(!$fe) redir(root()."sensormodels");
}

echo "<form id=\"formsmtab\" action=\"".root().$PAGE."/edit/".$smedit."\" method=\"post\">";

	echo "<fieldset><legend>".($smedit?"Editace modelu":"Nový model")."</legend>";
	echo "<table class=\"nobr\">";

	echo "<tr><td>Název:&nbsp;</td><td>".input_text_temp_err("001_sm_name","finput")."</td></tr>";
	echo "<tr><td>Výrobce:&nbsp;</td><td>".input_text_temp_err("001_sm_vendor","finput")."</td></tr>";
	echo "<tr><td>Poznámka:&nbsp;</td><td>".input_text_temp_err("000_sm_note","finput")."</td></tr>";
	
	echo "</table>";
	
	echo input_button("sm_save","Uložit")." ".input_button("sm_cancel","Storno");
	echo "</fieldset>";
	
    echo "<script type=\"text/javascript\">
// <![CDATA[
function smgui() {
    $(\"button\").button();
}
// ]]>
</script>";
    $_JQUERY[]="smgui();";

echo "</form>";

function sredir() {
    global $PAGE;
    global $smedit;
    redir(root().$PAGE."/edit/".$smedit);
}

if($_SERVER['REQUEST_METHOD']=="POST") {
    $_SESSION->invalid=false;
    $_SESSION->temp_form=false;

    if(get_ind($_POST,"sm_cancel")) {
	redir(root()."sensormodels");
    }
    if(get_ind($_POST,"sm_save")) {
	$rerr=postcheck($ITEMS,$_POST);
	
	if(count($rerr)) {
	    $_SESSION->error_text=reset($rerr);
	    $_SESSION->invalid=$rerr;
	    $_SESSION->temp_form=$_POST;
	    sredir();
	}
	
	if(!$smedit) {
	    $SQL->query("insert into sensormodel set
		sm_name=\"".$SQL->escape(get_ind($_POST,"001_sm_name"))."\",
		sm_vendor=\"".$SQL->escape(get_ind($_POST,"001_sm_vendor"))."\",
		sm_note=\"".$SQL->escape(get_ind($_POST,"000_sm_note"))."\"");
	    $_SESSION->error_text="Model vytvořen";
	} else {
	    $SQL->query("update sensormodel set
		sm_name=\"".$SQL->escape(get_ind($_POST,"001_sm_name"))."\",
		sm_vendor=\"".$SQL->escape(get_ind($_POST,"001_sm_vendor"))."\",
		sm_note=\"".$SQL->escape(get_ind($_POST,"000_sm_note"))."\"
		where sm_id=".$smedit);
	    $_SESSION->error_text="Model upraven";
	}
	redir(root()."sensormodels");
    }
    sredir();
}
