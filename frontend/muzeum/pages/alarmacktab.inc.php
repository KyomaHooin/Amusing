<?php

//pageperm();
if($_SESSION->user) showmenu();

showerror();

ajaxsess();

if($ARGC!=2) redir(root()."alarmsack");
if($ARGV[0]!="m") redir(root()."alarmsack");

$acid=get_ind($ARGV,1);

switch(urole()) {
case 'A':
case 'D':
    $qe=$SQL->query("select * from alarmack left join variable on ac_vid=var_id left join alarm on ac_id=a_ackid left join measuring on ac_mid=m_id left join room on m_rid=r_id left join building on r_bid=b_id left join user on ac_uid=u_id where ac_id=\"".$SQL->escape($acid)."\"");
    break;
default:
    $qe=$SQL->query("select * from alarmack left join variable on ac_vid=var_id left join alarm on ac_id=a_ackid left join measuring on ac_mid=m_id left join room on m_rid=r_id left join building on r_bid=b_id left join user on ac_uid=u_id where ac_id=\"".$SQL->escape($acid)."\" && u_id=".uid());
}
$ack=$qe->obj();

do {
    if(!$ack) {
	if($_SESSION->user) {
	    $_SESSION->error_text="Neexistující potvrzení";
	    redir(root()."alarmsack");
	}
	//echo "Neexistující potvrzení";
	echo "Pro potvrzení je vyžadováno přihlásení.";
	break;
    }

echo "<form action=\"".root().$PAGE."/".implode("/",$ARGV)."\" method=\"post\" enctype=\"multipart/form-data\">";

    echo "<fieldset><legend>Potvrzení alarmu</legend>";
// builds and rooms

    echo "<table class=\"nobr\">";
    echo "<tr><td>Datum vzniku:&nbsp;</td><td>".htmlspecialchars($ack->ac_dategen)."</td></tr>
	<tr><td>Budova:&nbsp;</td><td>".htmlspecialchars($ack->b_name)."</td></tr>
	<tr><td>Místnost:&nbsp;</td><td>".htmlspecialchars($ack->r_desc)." ".htmlspecialchars($ack->r_floor)."</td></tr>
	<tr><td>Měřící bod:&nbsp;</td><td>".htmlspecialchars($ack->m_desc)."</td></tr>
	<tr><td>Veličina:&nbsp;</td><td>".htmlspecialchars($ack->var_desc." ".$ack->var_unit)."</td></tr>
	<tr><td>Uživatel:&nbsp;</td><td>".htmlspecialchars($ack->u_fullname)."</td></tr>
	<tr><td>Alarm:&nbsp;</td><td>".htmlspecialchars($ack->ac_atext)."</td></tr>";
	
	if($ack->ac_state=='Y') {
	    echo "<tr><td>Text potvrzení:&nbsp;</td><td>".htmlspecialchars($ack->ac_text)."</td></tr>";
	    echo "<tr><td>Datum potvrzení:&nbsp;</td><td>".htmlspecialchars($ack->ac_dateack)."</td></tr>";
	    echo "</table>";
	    if($_SESSION->user) echo input_button("alarmack_cancel","Zpět");
	} else {
	    echo "<tr><td>Text potvrzení:&nbsp;</td><td>".input_area_temp_err("000_aack_text","farea")."</td></tr>";
	    echo "</table>";
	    echo input_button("alarmack_save","Uložit")." ".input_button("alarmack_cancel","Zpět");
	}

	echo "</fieldset>";

    echo "<script type=\"text/javascript\">
// <![CDATA[
function alarmackgui() {
    $(\"button\").button();
}
// ]]>
</script>";
    $_JQUERY[]="alarmackgui();";

echo "</form>";

} while(false);

function sredir() {
    global $PAGE;
    global $ARGV;
    redir(root().$PAGE."/".implode("/",$ARGV));
}

if($_SERVER['REQUEST_METHOD']=="POST") {
    $_SESSION->invalid=false;
    $_SESSION->temp_form=false;
    
    if(get_ind($_POST,"alarmack_cancel")) {
	if($_SESSION->user) redir(root()."alarmsack");
	else sredir();
    }
    if(get_ind($_POST,"alarmack_save")) {
	$rerr=postcheck($ITEMS,$_POST);
	if(count($rerr)) {
	    $_SESSION->error_text=reset($rerr);
	    $_SESSION->invalid=$rerr;
	    $_SESSION->temp_form=$_POST;
	    sredir();
	}
	$SQL->query("update alarmack set ac_state='Y',ac_text=\"".$SQL->escape(get_ind($_POST,"000_aack_text"))."\",ac_dateack=now() where ac_id=\"".$SQL->escape($ack->ac_id)."\" && ac_state!='Y'");
	$SQL->query("update alarm set a_ackid=\"\",a_alarmed='N' where a_id=\"".$SQL->escape($ack->ac_aid)."\"");
	
	$_SESSION->error_text="Alarm potvrzen";
	if($_SESSION->user) redir(root()."alarmsack");
	sredir();
    }
    sredir();
}
