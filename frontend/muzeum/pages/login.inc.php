<?php

if($_SESSION->user) redir(root()."main");
showerror();

echo "<form action=\"".root().$PAGE."\" method=\"post\">";

echo "<table class=\"tlogin\" align=\"center\"><tr><td>jméno:&nbsp;</td><td>".input_text("000_user")."</td></tr>";
echo "<tr><td>heslo:&nbsp;</td><td>".input_passwd("000_pass")."</td></tr>";
echo "<tr><td style=\"height:2px\"></td></tr>";
echo "<tr><td colspan=\"2\" style=\"text-align:center\">".input_button("log_ok","Přihlásit")." ".input_button("log_cancel","Zpět")."</td></tr></table>";

    echo "<script type=\"text/javascript\">
// <![CDATA[
function logingui() {
    $(\"button\").button();
}
// ]]>
</script>";
    $_JQUERY[]="logingui();";

echo "</form>";

function setuserdefaults() {
    global $SQL;
    if(!is_array($_SESSION->mainform)) $_SESSION->mainform=array();
    $qe=$SQL->query("select * from variable where var_default='Y'");
    while($fe=$qe->obj()) {
	$_SESSION->mainform["000_main_vargr_".$fe->var_id]='Y';
    }
    $_SESSION->mainform["main_groupvars"]='Y';
    $_SESSION->mainform["main_groupmeas"]='Y';
    $_SESSION->mainform["main_scales"]='Y';
    $_SESSION->mainform["main_extremealarms"]='Y';
    $_SESSION->mainform["main_showalarms"]='Y';
    $_SESSION->mainform["main_setcolors"]='Y';
}

if($_SERVER['REQUEST_METHOD']=="POST") {
    if(get_ind($_POST,"log_ok")) {
	$user=trim(get_ind($_POST,"000_user"));
	if(!strlen($user)) {
	    $_SESSION->error_text="Nezadán uživatel";
	    redir();
	}

	$qe=$SQL->query("select * from user where u_uname=\"".$SQL->escape($user)."\" && u_state='Y'");
	$fe=$qe->obj();
	
	if(!$fe) {
	    $_SESSION->error_text="Neplatný uživatel";
	    redir();
	}

	$ldaprdn  = '[removed]' . "\\" . $user;
	$ldapconn = ldap_connect("ldap://[removed]");
	$ldapconn2 = ldap_connect("ldap://[removed]");
	$ldapbind = @ldap_bind($ldapconn, $ldaprdn,get_ind($_POST,"000_pass"));

	if (!$ldapbind) { $ldapbind = @ldap_bind($ldapconn2, $ldaprdn, get_ind($_POST,"000_pass")); }// fallback

	if(!$ldapbind) {
	    $qe=$SQL->query("select * from user where 
	        u_uname=\"".$SQL->escape($user)."\" &&
	        u_pass=sha1(\"".$SQL->escape(get_ind($_POST,"000_pass"))."\") && u_state='Y'");
	    $fe=$qe->obj();

	    if(!$fe) {
	        $_SESSION->error_text="Neplatné přihlášení";
	        logsys("neplatné přihlášení: ".$user);
	        redir();
	    }
	}
	$_SESSION->user=$fe;
	$_SESSION->userpref=unserialize($fe->u_pref);
	$_SESSION->user->u_pref=""; // memory
//	logtext("přihlášen");
	setuserdefaults();
	if(strlen($_SESSION->alarmack_origin)) redir($_SESSION->alarmack_origin);
	redir(root()."main");
    }
    redir();
}

