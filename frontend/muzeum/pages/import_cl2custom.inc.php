<?php

$stid=$_sensor_data->s_id;

echo "<form action=\"".root().$PAGE."/".$_sensor_data->s_id."\" method=\"post\" enctype=\"multipart/form-data\">";
	echo "<fieldset><legend>Import dat (CL2 soubor)</legend>";

	$sdata=unserialize($_sensor_data->s_data);
	if(!is_array($sdata)) $sdata=array('set'=>false);
	if(!get_temp("sensor".$stid."_import")) $_SESSION->temp_form=get_ind($sdata,'set');
	
	$vopts=array(0=>"Zvolte veličinu");
	$qe=$SQL->query("select * from variable order by var_unit");
	while($fe=$qe->obj()) {
	    $vopts[$fe->var_id]=$fe->var_unit." ".$fe->var_desc;
	}

	echo "<table class=\"nobr\">";
	echo "<tr><td colspan=\"2\">Mapování sloupců:</td></tr>";
	echo "<tr><td>Veličina 1:&nbsp;</td><td>".input_select_temp_err("001_imp".$stid."_var1",$vopts)." ".input_select_temp_err("001_imp".$stid."_var1c",array(-1=>"Zvolte sloupec",1=>"1","2","3","4","5","6","7","8","9"))."</td></tr>";
	echo "<tr><td>Veličina 2:&nbsp;</td><td>".input_select_temp_err("001_imp".$stid."_var2",$vopts)." ".input_select_temp_err("001_imp".$stid."_var2c",array(-1=>"Zvolte sloupec",1=>"1","2","3","4","5","6","7","8","9"))."</td></tr>";

	$mydata=unserialize($_sensor_data->s_data);
	echo "<tr><td>Dopočítat abs. vlhkost:&nbsp;</td><td>".input_check("000_imp".$stid."_absh",'Y',get_ind($mydata,"absh"))."</td></tr>";

	echo "<tr><td>Data:&nbsp;</td><td>".input_file("sensor".$stid."_data")."</td></tr>";
	echo "</table>";
	
	echo input_button("sensor".$stid."_import","Uložit")." ".input_button("sensor".$stid."_cancel","Zpět");
	echo "</fieldset>";

echo "</form>";

function deferr($err="Chyba při čtení souboru") {
    $_SESSION->error_text=$err;
    sredir();
}

function parsedatetime($dt) {
    global $_sensor_data;
    global $_sensor_dstoff;

    if(!preg_match("/^(\\d{4})(\\d{2})(\\d{2})T(\\d{2})(\\d{2})(\\d{2})$/",$dt,$mch)) deferr("Neplatný formát datumu a času: ".$dt);
    $y=$mch[1];
    $m=$mch[2];
    $d=$mch[3];
    if(!checkdate($m,$d,$y)) deferr("Neplatný formát datumu: ".$dt);
    $ret=mktime($mch[4],$mch[5],$mch[6],$m,$d,$y);
    if($ret===false || $ret<0) deferr("Neplatný formát času: ".$dt." ".$t);
    return $ret+$_sensor_dstoff;
}

if($_SERVER['REQUEST_METHOD']=="POST") {
    $_SESSION->invalid=false;
    $_SESSION->temp_form=false;

    if(get_ind($_POST,"sensor".$stid."_cancel")) {
	backredir();
    }
    if(get_ind($_POST,"sensor".$stid."_import")) {
// check all that columns and so on...
	$rerr=postcheck($ITEMS,$_POST);
	
	$c_val1=(int)get_ind($_POST,"001_imp".$stid."_var1c");
	$c_val2=(int)get_ind($_POST,"001_imp".$stid."_var2c");
	if($c_val1<1 && $c_val2<1) $rerr["001_imp".$stid."_var1c"]=$rerr["001_imp".$stid."_var2c"]="Aspoň jeden sloupec veličiny musí být zvolen";
	else if($c_val1==$c_val2) $rerr["001_imp".$stid."_var2c"]="Zvolen stejny sloupec";
	
	$v_val1=(int)get_ind($_POST,"001_imp".$stid."_var1");
	$v_val2=(int)get_ind($_POST,"001_imp".$stid."_var2");

	if(!$c_val1 && $v_val1) $rerr["001_imp".$stid."_var1c"]="Neplatný sloupec";
	if(!$c_val2 && $v_val2) $rerr["001_imp".$stid."_var2c"]="Neplatný sloupec";
	
	if(!$v_val1 && !$v_val2) $rerr["001_imp".$stid."_var1"]=$rerr["001_imp".$stid."_var2"]="Musí být zvolena alespoň jedna veličina";
	else if($v_val1==$v_val2) $rerr["001_imp".$stid."_var2"]="Zvolena stejná veličina";
	
	$tc=false;
	$hr=false;
	$absh=(get_ind($_POST,"000_imp".$stid."_absh")=='Y');
	
	if($v_val1) {
	    $qe=$SQL->query("select * from variable where var_id=\"".$SQL->escape($v_val1)."\"");
	    $fe=$qe->obj();
	    if(!$fe) $rerr["001_imp".$stid."_var1"]="Neexistující veličina";
	    else {
		switch($fe->var_code) {
		case "temperature":
		    $tc=$c_val1;
		    break;
		case "humidity":
		    $hr=$c_val1;
		    break;
		}
	    }
	}
	if($v_val2) {
	    $qe=$SQL->query("select * from variable where var_id=\"".$SQL->escape($v_val2)."\"");
	    $fe=$qe->obj();
	    if(!$fe) $rerr["001_imp".$stid."_var2"]="Neexistující veličina";
	    else {
		switch($fe->var_code) {
		case "temperature":
		    $tc=$c_val2;
		    break;
		case "humidity":
		    $hr=$c_val2;
		    break;
		}
	    }
	}
	
	if(count($rerr)) {
	    $_SESSION->error_text=reset($rerr);
	    $_SESSION->invalid=$rerr;
	    $_SESSION->temp_form=$_POST;
	    sredir();
	}
	
	$vfrom=$_sensor_data->m_validfrom;
	$vto=$_sensor_data->m_validto;
	
	// perform import
	$data=get_ind($_FILES,"sensor".$stid."_data");
	if(!$data || !is_array($data)) deferr("Nebyl zadán soubor");
	$uperr=get_ind($data,"error");
	if($uperr) deferr("Chyba ".$uperr." při nahrávání souboru");

	// save setting for that sensortype
	$sdata['set']=$_POST;
	$SQL->query("update sensor set s_data=\"".$SQL->escape(serialize($sdata))."\" where s_id=".$stid);
	
	$raw=fopen(get_ind($data,"tmp_name"),"rb");
//	$raw=fopen("pages/_DATA-ALL.cl2","rb");
	if(!$raw) deferr();
	// file parsing

	$vals1=array();
	$vals2=array();
	$valsah=array();

	$prevz=date_default_timezone_get();
	if($_sensor_data->s_ignoredst=='Y') date_default_timezone_set("UTC");
	else if(!date_default_timezone_set($_sensor_data->s_timezone)) deferr("Neplatná zóna: ".$_sensor_data->s_timezone);
	while(!feof($raw)) {
	    $ln=trim(fgets($raw));
	    if(!strlen($ln)) continue;
	    $cls=preg_split("/\\s+/",$ln);
	    if(!count($cls)) continue;
	    if($c_val1>=count($cls) || $c_val2>=count($cls)) {
		deferr("Neplatný sloupec");
	    }
	    $ts=parsedatetime($cls[0]);
	    if($ts<$vfrom || $ts>$vto) continue;
	    
	    if($c_val1>0) {
		$v=$cls[$c_val1];
		if(!is_numeric($v)) deferr("Neplatné číslo: ".$v);
		$vals1[]=array($ts,$v);
	    }
	    if($c_val2>0) {
		$v=$cls[$c_val2];
		if(!is_numeric($v)) deferr("Neplatné číslo: ".$v);
		$vals2[]=array($ts,$v);
	    }
	    if($absh && $tc!==false && $hr!==false) {
		$valsah[]=array($ts,abshum($cls[$tc],$cls[$hr]));
	    }
	}
	date_default_timezone_set($prevz);
	if(!(count($vals1)+count($vals2))) deferr("Data jsou patrně mimo časovou platnost senzoru");
	
	try {

	$d=0;
	$c=0;
	$ta=0;
	if(count($vals1)) {
	    usort($vals1,"dbfsort");
	    $r=val_saverawvalues($vals1,$v_val1,$_sensor_data->m_id,$_sensor_data->s_id);
	    if(is_array($r)) {
		$d+=$r['d'];
		$c+=$r['r'];
		$ta+=$r['a'];
	    }
	}
	if(count($vals2)) {
	    usort($vals2,"dbfsort");
	    $r=val_saverawvalues($vals2,$v_val2,$_sensor_data->m_id,$_sensor_data->s_id);
	    if(is_array($r)) {
		$d+=$r['d'];
		$c+=$r['r'];
		$ta+=$r['a'];
	    }
	}
	if(count($valsah)) {
	    $qe=$SQL->query("select * from variable where var_code=\"abshumidity\"");
	    $fe=$qe->obj();
	    if($fe) {
		usort($valsah,"dbfsort");
		$r=val_saverawvalues($valsah,$fe->var_id,$_sensor_data->m_id,$_sensor_data->s_id);
		if(is_array($r)) {
//		    $d+=$r['d'];
//		    $c+=$r['r'];
//		    $ta+=$r['a'];
		}
	    }
	}

	$_SESSION->error_text="Data naimportována, záznamů: ".$c.", duplicitních záznamů: ".$d.", alarmů: ".$ta;
	sredir();
	
	} catch(Exception $e) {
	    $_SESSION->error_text="Chyba při zpracování: ".$e->getMessage();
	    sredir();
	}
    }
    sredir();
}
