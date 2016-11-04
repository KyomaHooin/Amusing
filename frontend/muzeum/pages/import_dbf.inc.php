<?php

$stid=$_sensor_data->st_id;

echo "<form action=\"".root().$PAGE."/".$_sensor_data->s_id."\" method=\"post\" enctype=\"multipart/form-data\">";
	echo "<fieldset><legend>Import dat (Binární DBF soubor)</legend>";

	$sdata=unserialize($_sensor_data->st_data);
	if(!is_array($sdata)) $sdata=array('set'=>false);
	if(!get_temp("sensor".$stid."_import")) $_SESSION->temp_form=get_ind($sdata,'set');
	
	$vopts=array(0=>"Zvolte veličinu");
	$qe=$SQL->query("select * from variable order by var_unit");
	while($fe=$qe->obj()) {
	    $vopts[$fe->var_id]=$fe->var_unit." ".$fe->var_desc;
	}

	echo "<table class=\"nobr\">";
	echo "<tr><td colspan=\"2\">Mapování sloupců:</td></tr>";
	echo "<tr><td>Datum:&nbsp;</td><td>".input_select_temp_err("001_imp".$stid."_date",array(-1=>"Zvolte sloupec",0=>"0","1","2","3","4","5","6","7","8","9"))."</td></tr>";
	echo "<tr><td>Čas:&nbsp;</td><td>".input_select_temp_err("001_imp".$stid."_time",array(-1=>"Zvolte sloupec",0=>"0","1","2","3","4","5","6","7","8","9"))."</td></tr>";
	echo "<tr><td>Veličina 1:&nbsp;</td><td>".input_select_temp_err("001_imp".$stid."_var1",$vopts)." ".input_select_temp_err("001_imp".$stid."_var1c",array(-1=>"Zvolte sloupec",0=>"0","1","2","3","4","5","6","7","8","9"))."</td></tr>";
	echo "<tr><td>Veličina 2:&nbsp;</td><td>".input_select_temp_err("001_imp".$stid."_var2",$vopts)." ".input_select_temp_err("001_imp".$stid."_var2c",array(-1=>"Zvolte sloupec",0=>"0","1","2","3","4","5","6","7","8","9"))."</td></tr>";

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

function parsedatetime($dt,$t) {
    global $_sensor_data;
    global $_sensor_dstoff;

    if(strlen($dt)!=8 || !is_numeric($dt)) deferr("Neplatný formát datumu: ".$dt);
    if(strlen($t)!=8) deferr("Neplatný formát času: ".$t);
    $y=substr($dt,0,4);
    $m=substr($dt,4,2);
    $d=substr($dt,6,2);
    if(!checkdate($m,$d,$y)) deferr("Neplatný formát datumu: ".$dt);
    
    if(!preg_match("/^(\\d{2})\\:(\\d{2})\\:(\\d{2})$/",$t,$mch)) deferr("Neplatný formát času: ".$t);
    $ret=mktime($mch[1],$mch[2],$mch[3],$m,$d,$y);
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
	$c_date=(int)get_ind($_POST,"001_imp".$stid."_date");
	$c_time=(int)get_ind($_POST,"001_imp".$stid."_time");
	if($c_date<0) $rerr["001_imp".$stid."_date"]="Nebyl zvolen sloupec pro datum";
	if($c_time<0) $rerr["001_imp".$stid."_time"]="Nebyl zvolen sloupec pro čas";
	if($c_date==$c_time) $rerr["001_imp".$stid."_time"]="Shoda sloupců pro čas a datum";
	
	$c_val1=(int)get_ind($_POST,"001_imp".$stid."_var1c");
	$c_val2=(int)get_ind($_POST,"001_imp".$stid."_var2c");
	if($c_val1<0 && $c_val2<0) $rerr["001_imp".$stid."_var1c"]=$rerr["001_imp".$stid."_var2c"]="Aspoň jeden sloupec veličiny musí být zvolen";
	else if($c_val1==$c_val2) $rerr["001_imp".$stid."_var2c"]="Zvolen stejny sloupec";
	
	$v_val1=(int)get_ind($_POST,"001_imp".$stid."_var1");
	$v_val2=(int)get_ind($_POST,"001_imp".$stid."_var2");
	
	if(!$v_val1 && !$v_val2) $rerr["001_imp".$stid."_var1"]=$rerr["001_imp".$stid."_var2"]="Musí být zvolena alespoň jedna veličina";
	else if($v_val1==$v_val2) $rerr["001_imp".$stid."_var2"]="Zvolena stejná veličina";
	
	if($c_val1<0 && $v_val1) $rerr["001_imp".$stid."_var1c"]="Neplatný sloupec";
	if($c_val2<0 && $v_val2) $rerr["001_imp".$stid."_var2c"]="Neplatný sloupec";

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
	
	// compare variable columns with date/time columns
	if($c_val1==$c_date || $c_val1==$c_time) $rerr["001_imp".$stid."_var1c"]="Sloupec se shoduje s časem či datumem";
	if($c_val2==$c_date || $c_val2==$c_time) $rerr["001_imp".$stid."_var2c"]="Sloupec se shoduje s časem či datumem";
	
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
	$SQL->query("update sensortype set st_data=\"".$SQL->escape(serialize($sdata))."\" where st_id=".$stid);
	
	$raw=fopen(get_ind($data,"tmp_name"),"rb");
//	$raw=fopen("pages/test.dbf","rb");
	if(!$raw) deferr();
	// file parsing
	$head=fread($raw,32);
	if(strlen($head)!=32) deferr("Nelze načíst hlavičku");
	if((ord($head)&7)!=3) deferr("Nesprávná verze souboru"); // invalid dbf version
	$header=unpack("Vh/Vr/vhl/vrl",$head);
	
	$flen=0;
	$fields=array();
	for(;;) {
	    $rd=fread($raw,1);
	    if(strlen($rd)!=1) deferr();
	    if(ord($rd)==0x0d) break;
	    $rdr=fread($raw,31);
	    if(strlen($rdr)!=31) deferr();
	    $rd.=$rdr;
	    $ft=array();
	    $ft['n']=trim(substr($rd,0,11));
	    $ft['t']=$rd[11];
	    $ft['s']=ord($rd[16]);
	    $ft['o']=$flen;
	    $flen+=$ft['s'];
	    $fields[]=$ft;
	}
// check timedate columns and if there is enough of column to handle selected ones
//	print_read($header);
	if($c_date>=count($fields)) deferr("V souboru není sloupec pro datum");
	if($c_time>=count($fields)) deferr("V souboru není sloupec pro čas");
	if($fields[$c_date]['t']!='D') deferr("Neplatný typ dat ve sloupci pro datum");
	if($fields[$c_time]['t']!='C') deferr("Neplatný typ dat ve sloupci pro čas");
	
	if($c_val1>=0) {
	    if($c_val1>=count($fields)) deferr("V souboru není sloupec pro veličinu 1");
	    if($fields[$c_val1]['t']!='N') deferr("Neplatný typ dat ve sloupci pro veličinu 1");
	}
	if($c_val2>=0) {
	    if($c_val2>=count($fields)) deferr("V souboru není sloupec pro veličinu 2");
	    if($fields[$c_val2]['t']!='N') deferr("Neplatný typ dat ve sloupci pro veličinu 2");
	}
	
	$vals1=array();
	$vals2=array();
	$valsah=array();
	$lastts=0;
	
	$prevz=date_default_timezone_get();
	if($_sensor_data->s_ignoredst=='Y') date_default_timezone_set("UTC");
	else if(!date_default_timezone_set($_sensor_data->s_timezone)) deferr("Neplatná zóna: ".$_sensor_data->s_timezone);
	while(!feof($raw)) {
	    $lh=fread($raw,1);
	    if(strlen($lh)!=1) deferr();
	    if(ord($lh)==0x1a) break; // ende
	    switch(ord($lh)) {
	    case 0x20: // regular line
		$fl=fread($raw,$flen);
		if(strlen($fl)!=$flen) deferr();
		$ts=parsedatetime(substr($fl,$fields[$c_date]['o'],$fields[$c_date]['s']),substr($fl,$fields[$c_time]['o'],$fields[$c_time]['s']));
		if($lastts==$ts) { // something wrong, check timeshift
//		    break;
		}
		$lastts=$ts;
		if($ts<$vfrom || $ts>$vto) break;
		if($c_val1>=0) {
		    $n=trim(substr($fl,$fields[$c_val1]['o'],$fields[$c_val1]['s']));
		    if(!is_numeric($n)) deferr("Neplatný formát hodnoty");
		    $vals1[]=array($ts,$n);
		}
		if($c_val2>=0) {
		    $n=trim(substr($fl,$fields[$c_val2]['o'],$fields[$c_val2]['s']));
		    if(!is_numeric($n)) deferr("Neplatný formát hodnoty");
		    $vals2[]=array($ts,$n);
		}
		if($absh && $tc!==false && $hr!==false) {
		    $n1=trim(substr($fl,$fields[$tc]['o'],$fields[$tc]['s']));
		    $n2=trim(substr($fl,$fields[$hr]['o'],$fields[$hr]['s']));
		    $valsah[]=array($ts,abshum($n1,$n2));
		}
		break;
	    case 0x2a: // deleted line
		$fl=fread($raw,$flen);
		if(strlen($fl)!=$flen) deferr();
		break;
	    default:
		deferr();
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
