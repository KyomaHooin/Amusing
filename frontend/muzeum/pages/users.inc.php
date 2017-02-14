<?php

pageperm();
showmenu();

showerror();

ajaxsess();

$makecsv=false;
switch($ARGC) {
case 2:
    switch($ARGV[0]) {
    case "sort":
	if($_SESSION->user_sort==$ARGV[1]) $_SESSION->user_sortmode=!$_SESSION->user_sortmode;
	$_SESSION->user_sort=$ARGV[1];
	redir();
    }
    break;
case 1:
    switch($ARGV[0]) {
    case "csv":
	$makecsv=true;
	break;
    }
}

echo "<form action=\"".root().$PAGE."\" method=\"post\">";

echo input_button("user_new","Nový uživatel","newbutton");

$whr=array();
if($_SESSION->user_filterenable) {
    echo "<fieldset><legend>Filtr</legend>";
    echo "<table class=\"nobr\">";
    echo "<tr><td>Jméno:&nbsp;</td><td>".input_text("000_user_filter_name",get_ind($_SESSION->user_filter,"000_user_filter_name"),"finput")."</td></tr>";
    echo "<tr><td>Role:&nbsp;</td><td>admin ".input_check("000_user_filter_role[0]",'A',get_ind(get_ind($_SESSION->user_filter,"000_user_filter_role"),0)=='A');
    echo ", poweruser ".input_check("000_user_filter_role[1]",'D',get_ind(get_ind($_SESSION->user_filter,"000_user_filter_role"),1)=='D');
    echo ", user ".input_check("000_user_filter_role[2]",'U',get_ind(get_ind($_SESSION->user_filter,"000_user_filter_role"),2)=='U')."</td></tr>";
    echo "</table>";
    echo input_button("user_fapply","Použít")." ".input_button("user_fall","Zobrazit vše");
    echo "</fieldset>";
    
    $fname=get_ind($_SESSION->user_filter,"000_user_filter_name");
    if($fname) $whr[]="u_fullname like \"%".$SQL->escape($fname)."%\"";
    $frole=get_ind($_SESSION->user_filter,"000_user_filter_role");
    if(is_array($frole) && count($frole)) {
	$rs=array();
	foreach($frole as $val) $rs[]="\"".$SQL->escape($val)."\"";
	$whr[]="u_role in (".implode(",",$rs).")";
    }
}

$ord=array();
switch($_SESSION->user_sort) {
case "uname":
    $ord[]="u_uname ".($_SESSION->user_sortmode?"desc":"asc");
    break;
case "email":
    $ord[]="u_email ".($_SESSION->user_sortmode?"desc":"asc");
    break;
case "role":
    $ord[]="u_role ".($_SESSION->user_sortmode?"desc":"asc");
    break;
case "lock":
    $ord[]="u_state ".($_SESSION->user_sortmode?"desc":"asc");
    break;
default:
    $_SESSION->user_sort="sname";
}
$ord[]="u_fullname ".($_SESSION->user_sortmode?"desc":"asc");

function role2string($r) {
    switch($r) {
    case 'A':
	return "admin";
    case 'D':
	return "poweruser";
    case 'U':
	return "user";
    }
    return "error";
}

if($makecsv) {
    ob_clean();
    $_NOHEAD=true;
//    header("Content-type: text/plain");
    header("Content-type: text/x-csv");
    header("Content-Disposition: attachment; filename=".$PAGE.".csv");
    
    ob_start();
    echo csvline(array("#","Uživatelské jméno","Jméno","Email","Role","Povolen"));
    $qe=$SQL->query("select * from user ".(count($whr)?"where ".implode(" && ",$whr):"")." order by ".implode(",",$ord));
    while($fe=$qe->obj()) {
	echo csvline(array($fe->u_id,$fe->u_uname,$fe->u_fullname,$fe->u_email,role2string($fe->u_role),$fe->u_state=='Y'?"ano":"ne"));
    }
    $csv=ob_get_contents();
    ob_end_clean();
    echo csvoutput($csv);
    
    exit();
}

echo "<table>";
sortlocalref(array(
    array('n'=>"#",'a'=>false),
    array('n'=>"Uživatelské jméno",'a'=>"uname"),
    array('n'=>"Jméno",'a'=>"sname"),
    array('n'=>"Email",'a'=>"email"),
    array('n'=>"Role",'a'=>"role"),
    array('n'=>"Povolen",'a'=>"lock"),
    array('n'=>input_button("user_filter","Filtr"),'a'=>false)
),$_SESSION->user_sort,$_SESSION->user_sortmode);

$qe=$SQL->query("select * from user ".(count($whr)?"where ".implode(" && ",$whr):"")." order by ".implode(",",$ord));
while($fe=$qe->obj()) {
// mark deactivated with style
    echo "<tr><td>".$fe->u_id."</td><td>".htmlspecialchars($fe->u_uname)."</td>
	<td>".htmlspecialchars($fe->u_fullname)."</td>
	<td>".htmlspecialchars($fe->u_email)."</td>
	<td>";
    echo role2string($fe->u_role);
    echo "</td><td>".input_check("user_av[".$fe->u_id."]",'Y',$fe->u_state=='Y',false,true)."</td><td>".input_button("user_edit[".$fe->u_id."]","Editovat")."</td></tr>";
}

echo "</table>";

echo "<br /><a href=\"".root().$PAGE."/csv\">Uložit jako csv</a>";

echo "<script type=\"text/javascript\">
// <![CDATA[
function usersgui() {
    $(\"button\").button();
}
// ]]>
</script>";
    $_JQUERY[]="usersgui();";

echo "</form>";

if($_SERVER['REQUEST_METHOD']=="POST") {
    $_SESSION->invalid=false;
    $_SESSION->temp_form=false;
    
    if(get_ind($_POST,"user_edit")) {
	if(is_array($_POST['user_edit'])) {
	    $uedit=(int)key($_POST['user_edit']);
	    if($uedit) {
		$qe=$SQL->query("select * from user where u_id=".$uedit);
		$fe=$qe->obj();
		if(!$fe) {
		    $_SESSION->error_text="Uživatel nenalezen";
		    redir();
		} else {
		    $_SESSION->temp_form=array(
			"001_user_fname"=>$fe->u_fullname,
			"004_user_email"=>$fe->u_email,
			"001_user_role"=>$fe->u_role,
			"000_user_state"=>$fe->u_state
		    );
		    redir(root()."usertab/edit/".$uedit);
		}
	    }
	}
	redir();
    }
    if(get_ind($_POST,"user_new")) {
	redir(root()."usertab/edit/0");
    }
    if(get_ind($_POST,"user_filter")) {
	$_SESSION->user_filterenable=!$_SESSION->user_filterenable;
	redir();
    }
    if(get_ind($_POST,"user_fall")) {
	$_SESSION->user_filter=false;
	redir();
    }
    if(get_ind($_POST,"user_fapply")) {
	postcheck($ITEMS,$_POST);
	$_SESSION->user_filter=$_POST;
	redir();
    }
    redir();
}
