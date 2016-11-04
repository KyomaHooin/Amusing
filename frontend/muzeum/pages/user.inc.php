<?php

pageperm();
showmenu();

showerror();

ajaxsess();

echo "<form action=\"".root().$PAGE."\" method=\"post\">";

echo "<fieldset><legend>Nastavení uživatele</legend>";

if(!is_array($_SESSION->temp_form)) $_SESSION->temp_form=array();
if(!get_temp("001_user_fullname")) $_SESSION->temp_form["001_user_fullname"]=$_SESSION->user->u_fullname;
if(!get_temp("004_user_email")) $_SESSION->temp_form["004_user_email"]=$_SESSION->user->u_email;

echo "<table class=\"nobr\">";
echo "<tr><td>Jméno:&nbsp;</td><td>".input_text_temp_err("001_user_fullname","finput")."</td></tr>";
echo "<tr><td>Email:&nbsp;</td><td>".input_text_temp_err("004_user_email","finput")." ".input_button("set_fname","Nastavit")."</td></tr>";
echo "<tr><td colspan=\"2\">&nbsp;</td></tr>";
echo "<tr><td>Původní heslo:&nbsp;</td><td>".input_passwd("000_passo","finput")."</td></tr>";
echo "<tr><td>Heslo:&nbsp;</td><td>".input_passwd("000_pass1","finput")."</td></tr>";
echo "<tr><td>Kontrola hesla:&nbsp;</td><td>".input_passwd("000_pass2","finput")." ".input_button("set_pass","Nastavit heslo")."</td></tr>";
echo "</table>";

echo input_button("set_cancel","Storno");
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
    if(get_ind($_POST,"set_fname")) {
	$rerr=postcheck($ITEMS,$_POST);
	if(count($rerr)) {
	    $_SESSION->error_text=reset($rerr);
	    $_SESSION->invalid=$rerr;
	    $_SESSION->temp_form=$_POST;
	    redir();
	}
	$SQL->query("update user set u_fullname=\"".$SQL->escape(get_ind($_POST,"001_user_fullname"))."\",u_email=\"".$SQL->escape(get_ind($_POST,"004_user_email"))."\" where u_id=".uid());
	$_SESSION->user->u_fullname=get_ind($_POST,"001_user_fullname");
	$_SESSION->user->u_email=get_ind($_POST,"004_user_email");
	$_SESSION->error_text="nastaveno";
	redir();
    }
    if(get_ind($_POST,"set_pass")) {
	$passo=get_ind($_POST,"000_passo");
	if(strcmp($_SESSION->user->u_pass,sha1($passo))) {
	    $_SESSION->error_text="nesprávné původní heslo";
	    redir();
	}
	$pass1=get_ind($_POST,"000_pass1");
	$pass2=get_ind($_POST,"000_pass2");
	if(strcmp($pass1,$pass2)) {
	    $_SESSION->error_text="hesla nejsou stejná";
	    redir();
	}
	if(strlen($pass1)<6) {
	    $_SESSION->error_text="nové heslo musí mít alespoň 6 znaků";
	    redir();
	}
	$SQL->query("update user set u_pass=sha1(\"".$SQL->escape($pass1)."\") where u_id=".uid());
	$_SESSION->user->u_pass=sha1($pass1);
	$_SESSION->error_text="nastaveno";
	redir();
    }
    redir();
}
