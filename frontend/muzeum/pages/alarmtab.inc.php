<?php

pageperm();
showmenu();

showerror();

ajaxsess();

$ktodel=false;
function delmidskey() {
    global $ktodel;
    if(get_ind($_SESSION->alarms_midsdata,$ktodel)) unset($_SESSION->alarms_midsdata[$ktodel]);
}

switch($ARGC) {
    case 3:
	$amids=array($ARGV[1]);
	break;
    case 4:
	$amids=get_ind($_SESSION->alarms_midsdata,$ARGV[2]);
	if(!is_array($amids) || $ARGV[1]!="sess") redir(root()."alarms");
	$ktodel=$ARGV[2];
	break;
    default:
	redir(root()."alarms");
}
if($ARGV[0]!="edit") redir(root()."alarms");

$measpoints=array();
$_alarmpoints=array();
$warn="";

foreach($amids as $val) {
    if(!is_numeric($val)) {
	delmidskey();
	redir(root()."alarms");
    }
    $mt=(int)$val;
    if(in_array($mt,$measpoints)) continue; // already there, not error ?
    $qe=$SQL->query("select * from measuring left join room on m_rid=r_id left join building on r_bid=b_id where m_id=".$mt);
    $fe=$qe->obj();
    if(!$fe) {
	$_SESSION->error_text="Invalidní měřící bod";
	delmidskey();
	redir(root()."alarms");
    }
    if($fe->m_active!='Y') {
	$warn="Některý z měřících bodů není aktivní";
	continue;
    }
    $measpoints[]=$mt;
    $_alarmpoints[]=$fe;
}

if(!count($_alarmpoints)) {
    $_SESSION->error_text="Žádný aktivní měřící bod k dispozici";
    delmidskey();
    redir(root()."alarms");
}
if(strlen($warn)) echo "<b>".$warn."</b><br />";

$alarmedit=(int)$ARGV[count($ARGV)-1];
if($alarmedit) { // cant edit alarms... only delete and new alarm
    $_SESSION->error_text="Alarmy nelze editovat";
    delmidskey();
    redir(root()."alarms");
}

echo "<form action=\"".root().$PAGE."/".implode("/",$ARGV)."\" method=\"post\" enctype=\"multipart/form-data\">";

	echo "<fieldset><legend>".($alarmedit?"Editace alarmu":"Nový alarm")."</legend>";
// builds and rooms

	$having=array();
	echo "<table class=\"nobr\">";
	foreach($_alarmpoints as $val) {
	    echo "<tr><td>Měřící bod:&nbsp;</td><td>".htmlspecialchars($val->m_desc)."</td></tr>";
	    echo "<tr><td>Místnost:&nbsp;</td><td>".htmlspecialchars($val->r_desc." ".$val->r_floor)."</td></tr>";
	    echo "<tr><td>Budova:&nbsp;</td><td>".htmlspecialchars($val->b_name." ".$val->b_desc)."</td></tr>";
	    echo "<tr><td colspan=\"2\">&nbsp;</td></tr>";

	    $qe=$SQL->query("select * from varmeascache where vmc_mid=".$val->m_id);
	    while($fe=$qe->obj()) $having[]=$fe->vmc_varid;
	}
	echo "</table>";
	$having=array_unique($having);

	echo "<table class=\"nobr\">";
	echo "<tr><td>Popis:&nbsp;</td><td>".input_text_temp_err("001_alarm_desc","finput")."</td></tr>";
	echo "<tr><td>Charakter:&nbsp;</td><td>".input_select_temp_err("001_alarm_char",array("N"=>"Varování","Y"=>"Kritický"))."</td></tr>";
	
	echo "<tr><td>Email:&nbsp;</td><td>".input_area_temp_err("000_alarm_email","farea")."</td></tr>";
	
	$qe=$SQL->query("select * from variable order by var_desc");
	if(!$qe->rowcount()) {
	    $_SESSION->error_text="Není definovaná veličina";
	    delmidskey();
	    redir(root()."alarms");
	}
	$vopts=array(0=>"Zvolte veličinu");
	while($fe=$qe->obj()) $vopts[$fe->var_id]=$fe->var_desc." ".$fe->var_unit.(in_array($fe->var_id,$having)?"":" zatím bez dat");
	echo "<tr><td>Veličina:&nbsp;</td><td>".input_select_temp_err("001_alarm_var",$vopts)."</td></tr>";
	
	$aopts=array(0=>"Zvolte typ");
	foreach(c_alarm_gen::getalltypes() as $key=>$val) $aopts[$key]=$val;
	echo "<tr><td colspan=\"2\">Typ alarmu: ".input_select_temp_err("001_alarm_type",$aopts);
	
	$qe=$SQL->query("select * from alarm_preset order by ap_desc");
	if($qe->rowcount()) {
	    $popts=array(0=>"Zvolte definici");
	    while($fe=$qe->obj()) $popts[$fe->ap_id]=$fe->ap_desc;
	    echo " nebo definice: ".input_select_temp_err("001_alarm_def",$popts);
	}
	echo "</td></tr></table>";
	echo "<hr />";
	echo "<span id=\"alarmform\"></span>";
	
	echo input_button("alarm_save","Uložit")." ".input_button("alarm_cancel","Zpět");
	echo "</fieldset>";

    echo "<script type=\"text/javascript\">
// <![CDATA[
function getatype(at) {
    $.get(\"".root()."ajaxalarm/form/\"+at,function(data) {
	$(\"#alarmform\").html(data);
    });
}
function alarmtype() {
    getatype($(\"#001_alarm_type\").val());
    $(\"#001_alarm_type\").change(function() {
	$(\"#001_alarm_def\").val(0);
	getatype($(this).val());
    });
    $(\"#001_alarm_def\").change(function() {
	$(\"#001_alarm_type\").val(0);
	getatype(0);
	$(\"#000_alarm_email\").prop(\"disabled\",$(this).val()!=0);
    });
    $(\"#000_alarm_email\").prop(\"disabled\",$(\"#001_alarm_def\").val()!=0);

    $(\"button\").button();
}
// ]]>
</script>";
    $_JQUERY[]="alarmtype();";

echo "</form>";

function sredir() {
    global $PAGE;
    global $ARGV;
    redir(root().$PAGE."/".implode("/",$ARGV));
}

if($_SERVER['REQUEST_METHOD']=="POST") {
    $_SESSION->invalid=false;
    $_SESSION->temp_form=false;
    
    if(get_ind($_POST,"alarm_cancel")) {
	delmidskey();
	redir(root()."alarms");
    }
    if(get_ind($_POST,"alarm_save")) {
	$rerr=postcheck($ITEMS,$_POST);
	$vid=(int)get_ind($_POST,"001_alarm_var");
	if(!$vid) $rerr['001_alarm_var']="Nezvolena veličina";
	else {
	    $qe=$SQL->query("select * from variable where var_id=".$vid);
	    if(!$qe->rowcount()) $rerr['001_alarm_var']="Neexistující veličina";
	}
	
	switch(get_ind($_POST,"001_alarm_char")) {
	case 'Y':
	case 'N':
	    break;
	default:
	    $rerr['001_alarm_char']="Invalidní charakter";
	}
	
	$at=get_ind($_POST,"001_alarm_type");
	$dt=(int)get_ind($_POST,"001_alarm_def");
	if(!$dt && $at=="0") $rerr['001_alarm_type']="Nezvolen typ alarmu";
	
	if($at) {
// check email
	    $ems=get_ind($_POST,"000_alarm_email");
	    if(strlen($ems)) {
		foreach(explode("\n",$ems) as $val) {
		    foreach(explode(",",$val) as $e) {
			$t=trim($e);
			if(strlen($t) && !preg_match("/[a-zA-Z0-9\.\-_]+@[a-zA-Z0-9\.\-_]+\.[a-zA-Z]+/",$t)) {
			    $rerr['000_alarm_email']="<b>Nesprávně vyplněné položky.</b>";
			    break;
			}
		    }
		}
	    }

	    $alrm=c_alarm_gen::getalarmbyname($at);
	    if(!$alrm) $rerr['001_alarm_type']="Neexistující typ alarmu";
	    else $alrm->checkform($rerr);
	} else if($dt) {
	    $qe=$SQL->query("select * from alarm_preset where ap_id=".$dt);
	    $fe=$qe->obj();
	    if(!$fe) $rerr['001_alarm_def']="Neexistující definice alarmu";
	    else {
		$ems=$fe->ap_email;
		$alrm=c_alarm_gen::getalarmbyname($fe->ap_class);
		if(!$alrm) $rerr['001_alarm_def']="Invalidní typ alarmu v definici";
		else if(!$alrm->checkdef($fe->ap_id,$fe->ap_data)) $rerr['001_alarm_def']="Invalidní definice";
	    }
	}
	if(count($rerr)) {
	    $_SESSION->error_text=reset($rerr);
	    $_SESSION->invalid=$rerr;
	    $_SESSION->temp_form=$_POST;
	    sredir();
	}
	foreach($_alarmpoints as $val) {
	    $alrm->saveform($vid,$val->m_id,get_ind($_POST,"001_alarm_desc"),$ems,get_ind($_POST,"001_alarm_char"));
	}
	if(count($_alarmpoints)>1) $_SESSION->error_text="Alarmy uloženy";
	else $_SESSION->error_text="Alarm uložen";
	delmidskey();
	redir(root()."alarms");
    }
    sredir();
}
