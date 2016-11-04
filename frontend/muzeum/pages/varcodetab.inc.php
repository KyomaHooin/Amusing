<?php

pageperm();
showmenu();

showerror();

ajaxsess();

if($ARGC==1) {
    if($ARGV[0]!="edit") redir(root()."varcodes");
    $varcodeedit="";
} else if($ARGC==2) {
    if($ARGV[0]!="edit") redir(root()."varcodes");
    $varcodeedit=$ARGV[1];
} else redir(root()."varcodes");
if(strlen($varcodeedit)) {
    $qe=$SQL->query("select * from varcodes where vc_text=\"".$SQL->escape($varcodeedit)."\"");
    $fe=$qe->obj();
    if(!$fe) redir(root()."varcodes");
}

echo "<form action=\"".root().$PAGE."/edit/".$varcodeedit."\" method=\"post\">";

echo "<fieldset><legend>".(strlen($varcodeedit)?"Editace kódu veličiny":"Nový kód veličiny")."</legend>";
echo "<table class=\"nobr\">";
echo "<tr><td>Kód:&nbsp;</td><td>".input_text_temp_err("000_vc_text")."</td></tr>";
echo "<tr><td>Perioda:&nbsp;</td><td>".input_text_temp_err("002_vc_period")."</td></tr>";
echo "<tr><td>Binární:&nbsp;</td><td>".input_check_temp("000_vc_bin")."</td></tr>";
echo "</table>";

echo input_button("vc_save","Uložit")." ".input_button("vc_cancel","Storno");
echo "</fieldset>";

echo "<script type=\"text/javascript\">
// <![CDATA[
function varsgui() {
    $(\"button\").button();
";
if(strlen($varcodeedit)) echo "$(\"#000_vc_text\").prop(\"disabled\",true)";
echo "
}
// ]]>
</script>";
    $_JQUERY[]="varsgui();";

echo "</form>";

function sredir() {
    global $PAGE;
    global $varcodeedit;
    redir(root().$PAGE."/edit/".$varcodeedit);
}

if($_SERVER['REQUEST_METHOD']=="POST") {
    $_SESSION->invalid=false;
    $_SESSION->temp_form=false;
    
    if(get_ind($_POST,"vc_cancel")) {
	redir(root()."varcodes");
    }
    if(get_ind($_POST,"vc_save")) {
	$rerr=postcheck($ITEMS,$_POST);
	if(!is_intnumber(get_ind($_POST,"002_vc_period"))) $rerr['002_vc_period']="Perioda není celé číslo";
	$vc=get_ind($_POST,"000_vc_text");
	if(!strlen($varcodeedit)) {
	    if(!strlen($vc)) $rerr['000_vc_text']="Nezadán kód veličiny";
	    if(!preg_match('/^[a-z0-9]+$/',$vc)) $rerr['000_vc_text']="Nepovolené znaky, je povoleno pouze a až z a 0 až 9 (malá písmena a číslice)";
	}
	$bin=(get_ind($_POST,"000_vc_bin")=='Y'?'Y':'N');
	
	if(count($rerr)) {
	    $_SESSION->error_text=reset($rerr);
	    $_SESSION->invalid=$rerr;
	    $_SESSION->temp_form=$_POST;
	    sredir();
	}

	if(!strlen($varcodeedit)) {
	    $SQL->query("insert into varcodes set
		vc_text=\"".$SQL->escape(get_ind($_POST,"000_vc_text"))."\",
		vc_expperiod=\"".$SQL->escape(get_ind($_POST,"002_vc_period"))."\",
		vc_bin=\"".$bin."\"");
	    switch($SQL->errnum) {
	    case 0:
		break;
	    case 1062:
		$rerr['000_vc_text']="Duplikátní kód veličiny";
		$_SESSION->error_text=reset($rerr);
		$_SESSION->invalid=$rerr;
		$_SESSION->temp_form=$_POST;
		sredir();
	    default:
		$_SESSION->error_text="Chyba databáze";
		redir(root()."varcodes");
	    }
	} else {
	    $SQL->query("update varcodes set
		vc_expperiod=\"".$SQL->escape(get_ind($_POST,"002_vc_period"))."\",
		vc_bin=\"".$bin."\" where vc_text=\"".$SQL->escape($varcodeedit)."\"");
	    switch($SQL->errnum) {
	    case 0:
		break;
	    default:
		$_SESSION->error_text="Chyba databáze";
		redir(root()."varcodes");
	    }
	}
	$_SESSION->error_text="Kód veličiny uložen";
	redir(root()."varcodes");
    }
    sredir();
}
