<?php

pageperm();
showmenu();

showerror();

ajaxsess();

if($ARGC!=2) redir(root()."materials");
if($ARGV[0]!="edit" || !is_numeric($ARGV[1])) redir(root()."materials");
$materialedit=(int)$ARGV[1];
if($materialedit) {
    $qe=$SQL->query("select * from material where ma_id=".$materialedit);
    $fe=$qe->obj();
    if(!$fe) redir(root()."materials");
}

echo "<form action=\"".root().$PAGE."/edit/".$materialedit."\" method=\"post\" enctype=\"multipart/form-data\">";

echo "<fieldset><legend>".($materialedit?"Editace materiálu":"Nový materiál")."</legend>";
echo "<table class=\"nobr\">";
echo "<tr><td>Popis:&nbsp;</td><td>".input_text_temp_err("001_mat_desc","finput")."</td></tr>";
echo "</table>";

echo input_button("mat_save","Uložit")." ".input_button("mat_cancel","Storno");
echo "</fieldset>";

echo "<script type=\"text/javascript\">
// <![CDATA[
function materialsgui() {
    $(\"button\").button();
}
// ]]>
</script>";
    $_JQUERY[]="materialsgui();";

echo "</form>";

function sredir() {
    global $PAGE;
    global $materialedit;
    redir(root().$PAGE."/edit/".$materialedit);
}

if($_SERVER['REQUEST_METHOD']=="POST") {
    $_SESSION->invalid=false;
    $_SESSION->temp_form=false;

    if(get_ind($_POST,"mat_cancel")) {
	redir(root()."materials");
    }
    if(get_ind($_POST,"mat_save")) {
	$rerr=postcheck($ITEMS,$_POST);
	if(count($rerr)) {
	    $_SESSION->error_text=reset($rerr);
	    $_SESSION->invalid=$rerr;
	    $_SESSION->temp_form=$_POST;
	    sredir();
	}
	if(!$materialedit) {
	    $SQL->query("insert into material set
		ma_desc=\"".$SQL->escape(get_ind($_POST,"001_mat_desc"))."\"");
	    if($SQL->errnum) {
		$_SESSION->error_text="Chyba databáze";
	    } else $_SESSION->error_text="Materiál uložen";
	} else {
	    $SQL->query("update material set
		ma_desc=\"".$SQL->escape(get_ind($_POST,"001_mat_desc"))."\"
		where ma_id=".$materialedit);
	    if($SQL->errnum) {
		$_SESSION->error_text="Chyba databáze";
	    } else {
		$_SESSION->error_text="Materiál uložen";
	    }
	}
	redir(root()."materials");
    }
    sredir();
}
