<?php

class c_stype_files extends c_stype_base {
    function showform($nw) {
	echo "<table class=\"nobr\">";
	
	echo "<tr><td>Dopočítat abs. vlhkost:&nbsp;</td><td>".input_check_temp("000_sen_absh")."</td></tr>";

	echo "</table>";
    }
    
    function checkform(&$rerr) {
    }

    function newsensor($meas,$mod) {
	$absh=(get_ind($_POST,"000_sen_absh")=='Y');
	
	$data=array('absh'=>$absh);
	global $SQL;
	    if($meas) $SQL->query("update sensor set s_mid=0 where s_mid=\"".$SQL->escape($meas)."\"");
	    $SQL->query("insert into sensor set
		s_mid=\"".$SQL->escape($meas)."\",
		s_desc=\"".$SQL->escape(get_ind($_POST,"000_sen_desc"))."\",
		s_serial=\"".$SQL->escape(get_ind($_POST,"001_sen_serial"))."\",
		s_model=".$mod.",
		s_timezone=\"Europe/Prague\",
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
	global $SQL;
	$qe=$SQL->query("select * from sensor where s_id=".$id);
	$fe=$qe->obj();
	if(!$fe) {
	    $_SESSION->error_text="Neplatný senzor";
	    return true;
	}
	$cid=$this->myid();
	if($fe->s_type!=$cid) $data=array();
	else {
	    $data=unserialize($fe->s_data);
	    if($meas!=$fe->s_mid) { // different measuring point, clear variable timestamps
		$data=array();
	    } else if(!is_array($data)) $data=array();
	}
	$data['absh']=(get_ind($_POST,"000_sen_absh")=='Y');
	
	    if($meas) $SQL->query("update sensor set s_mid=0 where s_mid=\"".$SQL->escape($meas)."\" && s_id!=".$id);
	    $SQL->query("update sensor set
		s_mid=\"".$SQL->escape($meas)."\",
		s_desc=\"".$SQL->escape(get_ind($_POST,"000_sen_desc"))."\",
		s_serial=\"".$SQL->escape(get_ind($_POST,"001_sen_serial"))."\",
		s_model=".$mod.",
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
	$_SESSION->temp_form["000_sen_absh"]=(get_ind($data,"absh")?'Y':'N');
    }
    
    function canimport() {
	return false;
    }
    
    function imprequire() {
	return false;
    }
    
    function cron($sfe) {
    }

    function attach($sfe) {
    }
}
