<?php

class c_stype_ringo1 extends c_stype_base {
    function showform($nw) {
	echo "<table class=\"nobr\">";
	
	echo "<tr><td>Uri:&nbsp;</td><td>".input_text_temp_err("001_sen_ringo1_uri","finput")." <span id=\"sen_rin1_test\">Test</span></td></tr>";
	echo "<tr><td id=\"sen_rin1_res\" colspan=\"2\"></td></tr>";
	$zopts=array();
	foreach(timezone_identifiers_list() as $val) {
	    $zopts[$val]=$val;
	}
	echo "<tr><td>Zóna:&nbsp;</td><td>".input_select_temp_err("001_sen_zone",$zopts)."</td></tr>";
	
	echo "<tr><td>Dopočítat abs. vlhkost:&nbsp;</td><td>".input_check_temp("000_sen_absh")."</td></tr>";

	echo "</table>";

	echo "<script type=\"text/javascript\">
// <![CDATA[
$(\"#sen_rin1_test\").button().click(function() {
    $.post(\"".root()."ajaxstype/rin1test\",$(\"#formsentab\").serializeArray(),function(data) {
	$(\"#sen_rin1_res\").html(data);
    }).fail(function() {
	alert(\"Nelze otestovat\");
    });
});
// ]]>
</script>";
    }
    
    function checkform(&$rerr) {
	if(!in_array(get_ind($_POST,"001_sen_zone"),timezone_identifiers_list())) $rerr['001_sen_zone']="Neplatná časová zóna";
    }

    function newsensor($meas,$mod) {
	$absh=(get_ind($_POST,"000_sen_absh")=='Y');
	
	$data=array('url'=>get_ind($_POST,"001_sen_ringo1_uri"),'absh'=>$absh);
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
	$data['url']=get_ind($_POST,"001_sen_ringo1_uri");
	$data['absh']=(get_ind($_POST,"000_sen_absh")=='Y');
	
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
	$_SESSION->temp_form["001_sen_ringo1_uri"]=get_ind($data,"url");
	$_SESSION->temp_form["000_sen_absh"]=(get_ind($data,"absh")?'Y':'N');
    }
    
    function canimport() {
	return false;
    }
    
    function imprequire() {
	return false;
    }
    
    function cron($sfe) {
	global $SQL;
	try {
	
	echo "process: ".$sfe->s_desc."\n";
	$sdata=unserialize($sfe->s_data);
	$vars=rin1getvars(get_ind($sdata,"url"),$sfe->s_serial);
	if($vars===false || !is_array($vars) || !count($vars)) return;
	
	$mints=time();
	$myvars=array();
	$myids=array();
	foreach($vars as $v) {
	    $qe=$SQL->query("select * from variable left join varcodes on vc_text=var_code where var_code=\"".$SQL->escape($v)."\"");
	    $fe=$qe->obj();
	    if(!$fe) continue;
	    $myvars[]=$v;
	    $myids[]=$fe;
	    $lt=get_ind($sdata,"lt_".$v);
	    if($lt===false) $lt=0;
	    if($lt<$sfe->m_validfrom) $lt=$sfe->m_validfrom;
	    if($lt<$mints) $mints=$lt;
	}
	if(!count($myvars)) return;
	$data=rin1getdata(get_ind($sdata,"url"),$sfe->s_serial,$myvars,$mints,time());
	if(!is_array($data) || !count($data)) return; // some error
	usort($data,"dbfsort");
// compute abs
	if(get_ind($sdata,"absh")) {
	    $qe=$SQL->query("select * from variable left join varcodes on vc_text=var_code where var_code=\"abshumidity\"");
	    $fe=$qe->obj();
	    if($fe) {
		$tc=array_search("temperature",$myvars);
		$hr=array_search("humidity",$myvars);
		if($tc!==false && $hr!==false) {
		    $tc++;
		    $hr++;
		    $myvars[]="abshumidity";
		    $myids[]=$fe;
		    foreach($data as &$val) {
			$val[]=abshum($val[$tc],$val[$hr]);
		    }
		    unset($val);
		}
	    }
	}

	for($i=0;$i<count($myvars);$i++) {
	    echo " ".$myvars[$i]."\n";

	    $tostore=array();
	    if($myids[$i]->vc_bin=='Y') {
		$valc=false;
		foreach($myvars as $key=>$val) {
		    if($val=="phototrapvalue") {
			$valc=$key;
			break;
		    }
		}
		if($valc!==false) {
		    $valc++;
		    foreach($data as $d) { 
			if($d[0]>$sfe->m_validto) break; 
			$tostore[]=array($d[0],serialize(array("type"=>"jpeg","value"=>$d[$valc],"data"=>base64_decode($d[$i+1]))));
		    }
		} else {
		    foreach($data as $d) { 
			if($d[0]>$sfe->m_validto) break; 
			$tostore[]=array($d[0],serialize(array("type"=>"jpeg","data"=>base64_decode($d[$i+1]))));
		    }
		}
	    } else {
		foreach($data as $d) { if($d[0]>$sfe->m_validto) break; $tostore[]=array($d[0],$d[$i+1]); }
	    }
	    if(!count($tostore)) continue;
	    val_saverawvalues($tostore,$myids[$i]->var_id,$sfe->m_id,$sfe->s_id,true);
	    $sdata["lt_".$myvars[$i]]=$tostore[count($tostore)-1][0];
	}
	print_r($sdata);
	$SQL->query("update sensor set s_data=\"".$SQL->escape(serialize($sdata))."\" where s_id=".$sfe->s_id);

	} catch(Exception $e) { // leave some chance for next cron objects
	}
    }

    function attach($sfe) {
	global $SQL;
	$data=unserialize($sfe->s_data);
	$SQL->query("update sensor set s_data=\"".$SQL->escape(serialize(array('url'=>get_ind($data,"url"))))."\" where s_id=".$sfe->s_id);
    }
}
