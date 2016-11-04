<?php

pageperm();
showmenu();

showerror();

ajaxsess();

if($ARGC!=2) redir(root()."buildings");
if($ARGV[0]!="edit" || !is_numeric($ARGV[1])) redir(root()."buildings");
$buildedit=(int)$ARGV[1];
if($buildedit) {
    $qe=$SQL->query("select * from building where b_id=".$buildedit);
    $buildfe=$qe->obj();
    if(!$buildfe) redir(root()."buildings");
} else redir(root()."buildings");

echo "<form action=\"".root().$PAGE."/edit/".$buildedit."\" method=\"post\">";

echo "<fieldset><legend>Editace oprávnění</legend>";

echo "<table class=\"nobr\">";
echo "<tr><td>Město:&nbsp;</td><td>".htmlspecialchars($buildfe->b_city)."</td></tr>
<td>Budova:&nbsp;</td><td>".htmlspecialchars($buildfe->b_name)."</td></tr>";

$qe=$SQL->query("select * from measuring left join room on m_rid=r_id where r_bid=".$buildedit);
if(!$qe->rowcount()) {
    $_SESSION->error_text="Žádný měřící bod v budově";
    redir(root()."buildings");
}
$mids=array();
$f=true;
while($fe=$qe->obj()) {
    if($f) {
	echo "<tr><td>Měřící bod(y):&nbsp;</td><td>";
	$f=false;
    } else echo "<tr><td>&nbsp;</td><td>";
    echo htmlspecialchars($fe->m_desc." (".$fe->r_desc.")")."</td></tr>";
    $mids[]=$fe->m_id;
}

echo "</table>";

echo "<hr />";

$tot=0;
$perm=array();
$qe=$SQL->query("select * from permission left join user on u_id=pe_uid where pe_mid in (".implode(",",$mids).")");
while($fe=$qe->obj()) {
    if(!get_ind($perm,$fe->pe_uid."_".$fe->pe_type)) $perm[$fe->pe_uid."_".$fe->pe_type]=array(1,$fe);
    else $perm[$fe->pe_uid."_".$fe->pe_type][0]++;
    $tot++;
}

if(!$tot) {
    echo "Žádná oprávnění";
} else {
    echo "<table>";
    echo "<tr><th>uživatel</th><th>oprávnění</th></th>&nbsp;<th></tr>";
    foreach($perm as $val) {
	$it=($val[0]==count($mids)?"":" style=\"font-style:italic;\"");
	echo "<tr>";
	echo "<td".$it.">";
	if(!$val[1]->pe_uid) echo "Všichni</td>";
	else echo htmlspecialchars($val[1]->u_fullname)." (".htmlspecialchars($val[1]->u_uname).")</td>";
	echo "<td".$it.">";
	switch($val[1]->pe_type) {
	case 'V':
	    echo "prohlížení</td>";
	    break;
	case 'I':
	    echo "import dat</td>";
	    break;
	default:
	    echo "invalidní</td>";
	}
	$acckey=bin2hex($val[1]->pe_uid."_".$val[1]->pe_type);
	echo "<td>".input_button("bacc_rem[".$acckey."]","Odebrat")."</td>";
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
echo " ".input_select("001_acc_type",$opts)." ".input_button("bacc_add","Přidat");

echo "<hr />";
	echo input_button("bacc_cancel","Zpět");
	echo "</fieldset>";
	
    echo "<script type=\"text/javascript\">
// <![CDATA[
function baccgui() {
    $(\"button\").button();
}
// ]]>
</script>";
    $_JQUERY[]="baccgui();";

echo "</form>";

function sredir() {
    global $PAGE;
    global $buildedit;
    redir(root().$PAGE."/edit/".$buildedit);
}

if($_SERVER['REQUEST_METHOD']=="POST") {
    $_SESSION->invalid=false;
    $_SESSION->temp_form=false;

    if(get_ind($_POST,"bacc_cancel")) {
	redir(root()."buildings");
    }
    if(get_ind($_POST,"bacc_add")) {
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
	$_SESSION->error_text="Opravnění přidáno";
	foreach($mids as $mid) {
	    $SQL->query("insert into permission set pe_uid=".$uid.",pe_mid=".$mid.",pe_type=\"".$typ."\"");
	    switch($SQL->errnum) {
	    case 0:
	    case 1062:
		break;
	    default:
	        $_SESSION->error_text="Chyba databáze";
	        redir(root()."buildings");
	    }
	}
	sredir();
    }
    if(get_ind($_POST,"bacc_rem")) {
	if(is_array($_POST['bacc_rem'])) {
	    $torem=hex2bin(key($_POST['bacc_rem']));
	    if(preg_match("/^(\\d+)_([IV])$/",$torem,$mch)) {
		$SQL->query("delete from permission where
		    pe_uid=".$mch[1]." && pe_type=\"".$mch[2]."\" && pe_mid in (".implode(",",$mids).")");
		$_SESSION->error_text="Oprávnění odebráno";
	    }
	}
	sredir();
    }
    sredir();
}
