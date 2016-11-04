<?php

pageperm();
showmenu();

showerror();

ajaxsess();

if($ARGC!=2) redir(root()."measpoints");
if($ARGV[0]!="edit" || !is_numeric($ARGV[1])) redir(root()."measpoints");
$measpointedit=(int)$ARGV[1];
if($measpointedit) {
    $qe=$SQL->query("select * from measuring left join room on m_rid=r_id left join building on r_bid=b_id left join sensor on m_id=s_mid where m_id=".$measpointedit);
    $measfe=$qe->obj();
    if(!$measfe) redir(root()."measpoints");
} else redir(root()."measpoints");

echo "<form action=\"".root().$PAGE."/edit/".$measpointedit."\" method=\"post\">";

echo "<fieldset><legend>Editace oprávnění</legend>";

echo "<table class=\"nobr\">";
echo "<tr><td>Město:&nbsp;</td><td>".htmlspecialchars($measfe->b_city)."</td></tr>
<td>Budova:&nbsp;</td><td>".htmlspecialchars($measfe->b_name)."</td></tr>
<tr><td>Místnost:&nbsp;</td><td>".htmlspecialchars($measfe->r_desc)." ".htmlspecialchars($measfe->r_floor)."</td></tr>
<tr><td>Měřící bod:&nbsp;</td><td>".htmlspecialchars($measfe->m_desc)."</td></tr>
</table>";

echo "<hr />";

$qe=$SQL->query("select * from permission left join user on u_id=pe_uid where pe_mid=".$measpointedit." order by u_fullname");
if(!$qe->rowcount()) {
    echo "Žádná oprávnění";
} else {
    echo "<table>";
    echo "<tr><th>uživatel</th><th>oprávnění</th></th>&nbsp;<th></tr>";
    while($fe=$qe->obj()) {
	echo "<tr>";
	if(!$fe->pe_uid) echo "<td>Všichni</td>";
	else echo "<td>".htmlspecialchars($fe->u_fullname)." (".htmlspecialchars($fe->u_uname).")</td>";
	switch($fe->pe_type) {
	case 'V':
	    echo "<td>prohlížení</td>";
	    break;
	case 'I':
	    echo "<td>import dat</td>";
	    break;
	default:
	    echo "<td>invalidní</td>";
	}
	$acckey=bin2hex($fe->pe_uid."_".$fe->pe_mid."_".$fe->pe_type);
	echo "<td>".input_button("macc_rem[".$acckey."]","Odebrat")."</td>";
	echo "</tr>";
    }
    echo "</table>";
}
echo "<hr />";
$opts=array(0=>"Všichni");
$qe=$SQL->query("select * from user where u_role!='A' && u_role!='D' order by u_fullname");
while($fe=$qe->obj()) {
    $opts[$fe->u_id]=$fe->u_fullname." (".$fe->u_uname.")";
}
echo input_select("001_acc_user",$opts);
$opts=array('V'=>"prohlížení",'I'=>"import dat");
echo " ".input_select("001_acc_type",$opts)." ".input_button("macc_add","Přidat");

echo "<hr />";
	echo input_button("macc_cancel","Zpět");
	echo "</fieldset>";
	
    echo "<script type=\"text/javascript\">
// <![CDATA[
function measgui() {
    $(\"button\").button();
}
// ]]>
</script>";
    $_JQUERY[]="measgui();";

echo "</form>";

function sredir() {
    global $PAGE;
    global $measpointedit;
    redir(root().$PAGE."/edit/".$measpointedit);
}

if($_SERVER['REQUEST_METHOD']=="POST") {
    $_SESSION->invalid=false;
    $_SESSION->temp_form=false;

    if(get_ind($_POST,"macc_cancel")) {
	redir(root()."measpoints");
    }
    if(get_ind($_POST,"macc_add")) {
	$uid=get_ind($_POST,"001_acc_user");
	$typ=get_ind($_POST,"001_acc_type");
	switch($typ) {
	case 'I':
	case 'V':
	    break;
	default:
	    $_SESSION->error_text="Neplatné oprávnění";
	    sredir();
	}
	if(!preg_match("/^\\d+$/",$uid)) {
	    $_SESSION->error_text="Neplatný uživatel";
	    sredir();
	}
	$SQL->query("insert into permission set pe_uid=".$uid.",pe_mid=".$measpointedit.",pe_type=\"".$typ."\"");
	    switch($SQL->errnum) {
	    case 0:
		$_SESSION->error_text="Opravnění přidáno";
		break;
	    case 1062:
		$_SESSION->error_text="Oprávnění již existuje";
		sredir();
	    default:
		$_SESSION->error_text="Chyba databáze";
	    }
	sredir();
    }
    if(get_ind($_POST,"macc_rem")) {
	if(is_array($_POST['macc_rem'])) {
	    $torem=hex2bin(key($_POST['macc_rem']));
	    if(preg_match("/^(\\d+)_(\\d+)_([IV])$/",$torem,$mch)) {
		$SQL->query("delete from permission where
		    pe_uid=".$mch[1]." && pe_mid=".$mch[2]." && pe_type=\"".$mch[3]."\"");
		$_SESSION->error_text="Oprávnění odebráno";
	    }
	}
	sredir();
    }
    sredir();
}
