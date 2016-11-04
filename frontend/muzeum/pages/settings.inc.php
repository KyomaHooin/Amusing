<?php

pageperm();
showmenu();

showerror();

ajaxsess();

echo "<form action=\"".root().$PAGE."\" method=\"post\">";

echo "<fieldset><legend>Nastavení</legend>";

if(!is_array($_SESSION->temp_form)) $_SESSION->temp_form=array();

echo "<table class=\"nobr\">";

$cfgitems=array(
    array("_PLOTW","Šířka obrázku grafu:","002__PLOTW"),
    array("_PLOTH","Výška obrázku grafu:","002__PLOTH"),
    array("_ALARMMAIL","Odchozí adresa alarmů:","004__ALARMMAIL"),
    array("_MAXDATAAGE","Indikace stáří dat měřícího bodu (minuty):","002__MAXDATAAGE"),
    array("_BINSPERPAGE","Obrázků na stránku u zobrazení všech:","002__BINSPERPAGE"),
    array("_PERPAGE","Položek na stránku:","002__PERPAGE")
);

foreach($cfgitems as $val) {
    if(get_ind($_SESSION->temp_form,$val[2])===false) $_SESSION->temp_form[$val[2]]=$$val[0];
    echo "<tr><td>".$val[1]."&nbsp;</td><td>".input_text_temp_err($val[2],"finput")."</td></tr>";
}

$_SESSION->invalid=false;
$_SESSION->temp_form=false;

echo "</table>";

echo input_button("set_save","Nastavit")." ".input_button("set_cancel","Storno");
echo "</fieldset>";

echo "<script type=\"text/javascript\">
// <![CDATA[
function setsgui() {
    $(\"button\").button();
}
// ]]>
</script>";
    $_JQUERY[]="setsgui();";

echo "</form>";

if($_SERVER['REQUEST_METHOD']=="POST") {
    $_SESSION->invalid=false;
    $_SESSION->temp_form=false;

    if(get_ind($_POST,"set_cancel")) {
	redir();
    }
    if(get_ind($_POST,"set_save")) {
	$rerr=postcheck($ITEMS,$_POST);
	if(count($rerr)) {
	    $_SESSION->error_text=reset($rerr);
	    $_SESSION->invalid=$rerr;
	    $_SESSION->temp_form=$_POST;
	    redir();
	}
	foreach($cfgitems as $val) {
	    $SQL->query("insert into setup set
		set_variable=\"".$SQL->escape($val[0])."\",
		set_value=\"".$SQL->escape(get_ind($_POST,$val[2]))."\"
		on duplicate key update
		set_value=\"".$SQL->escape(get_ind($_POST,$val[2]))."\"");
	}
	$_SESSION->error_text="Nastaveno";
	redir();
    }
    redir();
}
