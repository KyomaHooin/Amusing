<?php

pageperm();
showmenu();

showerror();

ajaxsess();

$minpass=5;

if($ARGC!=2) redir(root()."users");
if($ARGV[0]!="edit" || !is_numeric($ARGV[1])) redir(root()."users");
$useredit=(int)$ARGV[1];
if($useredit) {
    $qe=$SQL->query("select * from user where u_id=".$useredit);
    $userfe=$qe->obj();
    if(!$userfe) redir(root()."users");
    if($userfe->u_role=='A' && urole()!='A') {
	$_SESSION->error_text="Nemáte oprávnění nastavit admin účet";
	redir(root()."users");
    }
}

echo "<form action=\"".root().$PAGE."/edit/".$useredit."\" method=\"post\">";

echo "<fieldset><legend>Editace: ".htmlspecialchars($useredit?$userfe->u_uname:"Nový uživatel")."</legend>";
echo "<table class=\"nobr\">";
if(!$useredit) echo "<tr><td>Uživ. jméno:&nbsp;</td><td>".input_text_temp_err("001_user_uname","finput")."</td></tr>";
echo "<tr><td>Jméno:&nbsp;</td><td>".input_text_temp_err("001_user_fname","finput")."</td></tr>";
echo "<tr><td>Email:&nbsp;</td><td>".input_text_temp_err("004_user_email","finput")."</td></tr>";
echo "<tr><td>Heslo:&nbsp;</td><td>".input_passwd_err("000_user_pass1","finput")."</td></tr>";
echo "<tr><td>Kontrola hesla:&nbsp;</td><td>".input_passwd_err("000_user_pass2","finput")."</td></tr>";
echo "<tr><td>Role:&nbsp;</td><td>".input_select_temp_err("001_user_role",
    array('A'=>"Administrátor",
	'D'=>"Poweruser",
	'U'=>"User"))."</td></tr>";
echo "<tr><td>Povoleno:&nbsp;</td><td>".input_check_temp("000_user_state",'Y')."</td></tr>";
echo "</table>";
echo input_button("user_save","Uložit")." ".input_button("user_cancel","Zpět");
echo "</fieldset>";

echo "<script type=\"text/javascript\">
// <![CDATA[
function usersgui() {
    $(\"button\").button();
}
// ]]>
</script>";
    $_JQUERY[]="usersgui();";

echo "</form>";

function sredir() {
    global $PAGE;
    global $useredit;
    redir(root().$PAGE."/edit/".$useredit);
}

if($_SERVER['REQUEST_METHOD']=="POST") {
    $_SESSION->invalid=false;
    $_SESSION->temp_form=false;
    
    if(get_ind($_POST,"user_cancel")) {
	redir(root()."users");
    }
    if(get_ind($_POST,"user_save")) {
	$rerr=postcheck($ITEMS,$_POST);
	$pass1=get_ind($_POST,"000_user_pass1");
	$pass2=get_ind($_POST,"000_user_pass2");
	if(!$useredit) {
	    if(strlen($pass1)<$minpass) $rerr['000_user_pass1']="Heslo musí mít alespoň ".$minpass." znaků";
	    if(strcmp($pass1,$pass2)) $rerr['000_user_pass2']="Neshoda hesel";
	} else {
	    if(strlen($pass1)) {
		if(strlen($pass1)<$minpass) $rerr['000_user_pass1']="Heslo musí mít alespoň ".$minpass." znaků";
		if(strcmp($pass1,$pass2)) $rerr['000_user_pass2']="Neshoda hesel";
	    }
	}
	switch(get_ind($_POST,"001_user_role")) {
	case 'A':
	    if(urole()!='A') $rerr['001_user_role']="Nemáte oprávnění nastavit admin práva";
	    break;
	case 'D':
	case 'U':
	    break;
	default:
	    $rerr['001_user_role']="Neplatná role";
	}
	
	if(count($rerr)) {
	    $_SESSION->error_text=reset($rerr);
	    $_SESSION->invalid=$rerr;
	    $_SESSION->temp_form=$_POST;
	    sredir();
	}
	if(!$useredit) {
	    $SQL->query("insert into user set
		    u_uname=\"".$SQL->escape(get_ind($_POST,"001_user_uname"))."\",
		    u_pass=sha1(\"".$SQL->escape($pass1)."\"),
		    u_fullname=\"".$SQL->escape(get_ind($_POST,"001_user_fname"))."\",
		    u_email=\"".$SQL->escape(get_ind($_POST,"004_user_email"))."\",
		    u_role=\"".$SQL->escape(get_ind($_POST,"001_user_role"))."\",
		    u_state=\"".$SQL->escape(get_ind($_POST,"000_user_state")=='Y'?'Y':'N')."\"");
	    switch($SQL->errnum) {
	    case 0:
		$_SESSION->error_text="Uživatel vytvořen";
		break;
	    case 1062:
		$_SESSION->error_text="Shoda uživatelských jmen";
		$_SESSION->invalid=array("001_user_uname"=>"Shoda uživatelských jmen");
		$_SESSION->temp_form=$_POST;
		sredir();
	    default:
		$_SESSION->error_text="Chyba databáze";
	    }
	} else {
	    if(strlen($pass1)) {
		$SQL->query("update user set
		    u_pass=sha1(\"".$SQL->escape($pass1)."\"),
		    u_fullname=\"".$SQL->escape(get_ind($_POST,"001_user_fname"))."\",
		    u_email=\"".$SQL->escape(get_ind($_POST,"004_user_email"))."\",
		    u_role=\"".$SQL->escape(get_ind($_POST,"001_user_role"))."\",
		    u_state=\"".$SQL->escape(get_ind($_POST,"000_user_state")=='Y'?'Y':'N')."\"
		    where u_id=".$useredit);
	    } else {
		$SQL->query("update user set
		    u_fullname=\"".$SQL->escape(get_ind($_POST,"001_user_fname"))."\",
		    u_email=\"".$SQL->escape(get_ind($_POST,"004_user_email"))."\",
		    u_role=\"".$SQL->escape(get_ind($_POST,"001_user_role"))."\",
		    u_state=\"".$SQL->escape(get_ind($_POST,"000_user_state")=='Y'?'Y':'N')."\"
		    where u_id=".$useredit);
	    }
	    if($SQL->errnum) $_SESSION->error_text="Chyba databáze";
	    else $_SESSION->error_text="Uživatel uložen";
	}
	redir(root()."users");
    }
    sredir();
}
