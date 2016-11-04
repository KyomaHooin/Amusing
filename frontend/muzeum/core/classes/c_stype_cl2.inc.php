<?php

class c_stype_cl2 extends c_stype_base {
    function showform($nw) {
	echo "<table class=\"nobr\">";

	$zopts=array();
	foreach(timezone_identifiers_list() as $val) {
	    $zopts[$val]=$val;
	}
	echo "<tr><td>Zóna:&nbsp;</td><td>".input_select_temp_err("001_sen_zone",$zopts)."</td></tr>";
	echo "<tr><td>Dopočítat abs. vlhkost:&nbsp;</td><td>".input_check_temp("000_sen_absh")."</td></tr>";
	echo "</table>";
    }
    
    function checkform(&$rerr) {
	if(!in_array(get_ind($_POST,"001_sen_zone"),timezone_identifiers_list())) $rerr['001_sen_zone']="Neplatná časová zóna";
    }

    function newsensor($meas,$mod) {
	$data=array('absh'=>(get_ind($_POST,"000_sen_absh")=='Y'));
	global $SQL;
	    if($meas) $SQL->query("update sensor set s_mid=0 where s_mid=\"".$SQL->escape($meas)."\"");
	    $SQL->query("insert into sensor set
		s_mid=\"".$SQL->escape($meas)."\",
		s_desc=\"".$SQL->escape(get_ind($_POST,"000_sen_desc"))."\",
		s_serial=\"".$SQL->escape(get_ind($_POST,"001_sen_serial"))."\",
		s_model=".$mod.",
		s_timezone=\"".$SQL->escape(get_ind($_POST,"001_sen_zone"))."\",
		s_ignoredst=\"Y\",
		s_type=".$this->myid().",
		s_data=\"".$SQL->escape(serialize($data))."\",
		s_active=\"".(get_ind($_POST,"000_sen_state")=='Y'?'Y':'N')."\"");
	    switch($SQL->errnum) {
	    case 0:
		$_SESSION->error_text="Senzor uložen";
		break;
	    case 1062:
		$_SESSION->error_text="Shoda sériových čísel a modelu senzoru";
		$_SESSION->invalid=array(
		    "001_sen_serial"=>"Shoda sériových čísel a modelu senzoru",
		    "001_sen_model"=>"Shoda sériových čísel a modelu senzoru"
		);
		$_SESSION->temp_form=$_POST;
		return false;
	    default:
		$_SESSION->error_text="Chyba databáze";
	    }
	return true;
    }
    
    function editsensor($id,$meas,$mod) {
	$data=array('absh'=>(get_ind($_POST,"000_sen_absh")=='Y'));
	global $SQL;
	$qe=$SQL->query("select * from sensor where s_id=".$id);
	$fe=$qe->obj();
	if(!$fe) {
	    $_SESSION->error_text="Neplatný senzor";
	    return true;
	}
	$cid=$this->myid();

	    if($meas) $SQL->query("update sensor set s_mid=0 where s_mid=\"".$SQL->escape($meas)."\" && s_id!=".$id);
	    $SQL->query("update sensor set
		s_mid=\"".$SQL->escape($meas)."\",
		s_desc=\"".$SQL->escape(get_ind($_POST,"000_sen_desc"))."\",
		s_serial=\"".$SQL->escape(get_ind($_POST,"001_sen_serial"))."\",
		s_model=".$mod.",
		s_timezone=\"".$SQL->escape(get_ind($_POST,"001_sen_zone"))."\",
		s_ignoredst=\"Y\",
		s_type=".$cid.",
		s_data=\"".$SQL->escape(serialize($data))."\",
		s_active=\"".(get_ind($_POST,"000_sen_state")=='Y'?'Y':'N')."\"
		where s_id=".$id);
	    switch($SQL->errnum) {
	    case 0:
		$_SESSION->error_text="Senzor uložen";
		break;
	    case 1062:
		$_SESSION->error_text="Shoda sériových čísel a modelu senzoru";
		$_SESSION->invalid=array(
		    "001_sen_serial"=>"Shoda sériových čísel a modelu senzoru",
		    "001_sen_model"=>"Shoda sériových čísel a modelu senzoru"
		);
		$_SESSION->temp_form=$_POST;
		return false;
	    default:
		$_SESSION->error_text="Chyba databáze";
	    }
	return true;
    }

    function addtempform($sfe) {
	$data=unserialize($sfe->s_data);
	$_SESSION->temp_form["001_sen_zone"]=$sfe->s_timezone;
	$_SESSION->temp_form["000_sen_absh"]=(get_ind($data,"absh")?'Y':'N');
    }
    
    function canimport() {
	return true;
    }
    
    function imprequire() {
	return "import_cl2.inc.php";
    }
    
    function cron($sfe) {
    }

    function attach($sfe) {}
}
