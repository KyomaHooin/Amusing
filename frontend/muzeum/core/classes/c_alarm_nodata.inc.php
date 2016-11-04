<?php

class c_alarm_nodata extends c_alarm_gen {
    var $f;
    var $w;
    var $a;
    
    var $age;
    
    var $preset;
    
    var $tosend;
    
    function __construct() {
	$this->f=false;
	$this->w=false;
	$this->a=array();
	$this->preset=0;
	$this->tosend=false;
    }

    function showform() {
	echo "<table class=\"nobr\">";
	echo "<tr><td>Maximální stáří dat (hodiny):&nbsp;</td><td>".input_text_temp_err("002_alarm_nodata_age")."</td></tr>";
	echo "</table>";
    }

    function checkform(&$rerr) {
	$this->age=trim(get_ind($_POST,"002_alarm_nodata_age"));
	if(!is_numeric($this->age)) {
	    $rerr['002_alarm_nodata_age']="Stáří není číslo";
	    return;
	}
    }

    function checkdef($pid,&$dt) {
	$data=unserialize($dt);
	$this->age=get_ind($data,"age");
	if(!is_numeric($this->age)) return false;
	$this->preset=$pid;
	return true;
    }

    function loadtempform(&$tmp,$dt) {
	$data=unserialize($dt);
	$tmp["002_alarm_nodata_age"]=get_ind($data,"age");
    }

    function saveform($vid,$mid,$desc,$em,$crit) {
	global $SQL;
    
	$qe=$SQL->query("select * from varmeascache where vmc_mid=".$mid." && vmc_varid=".$vid);
	$fe=$qe->obj();
	if($fe) {
	    $tm=$fe->vmc_lastrawtime;
	    $vl=$fe->vmc_lastrawvalue;
	} else {
	    $tm=time();
	    $vl=0;
	}
	
	$SQL->query("insert into alarm set
	    a_desc=\"".$SQL->escape($desc)."\",
	    a_email=\"".$SQL->escape($em)."\",
	    a_vid=\"".$SQL->escape($vid)."\",
	    a_mid=\"".$SQL->escape($mid)."\",
	    a_uid=".uid().",
	    a_preset=".$this->preset.",
	    a_class=\"c_alarm_nodata\",
	    a_crit=\"".$SQL->escape($crit)."\",
	    a_data=\"".$SQL->escape(serialize(array('age'=>$this->age,'set'=>0,'from'=>array($tm,$vl))))."\""); // from time 0 ?
    }
    
    function savedef($id,$desc,$em) {
	global $SQL;
	
	if(!$id) {
	    $SQL->query("insert into alarm_preset set
		ap_desc=\"".$SQL->escape($desc)."\",
		ap_email=\"".$SQL->escape($em)."\",
		ap_class=\"c_alarm_nodata\",
		ap_data=\"".$SQL->escape(serialize(array('age'=>$this->age)))."\"");
	} else {
	    // update all existing alarms of that template
	    $pdata=array('age'=>$this->age);
	    $SQL->query("update alarm_preset set
		ap_desc=\"".$SQL->escape($desc)."\",
		ap_email=\"".$SQL->escape($em)."\",
		ap_data=\"".$SQL->escape(serialize($pdata))."\"
		where ap_id=\"".$SQL->escape($id)."\" && ap_class=\"c_alarm_nodata\"");
	    $qe=$SQL->query("select * from alarm where a_preset=\"".$SQL->escape($id)."\" && a_class=\"c_alarm_nodata\"");
	    while($fe=$qe->obj()) {
		$adata=unserialize($fe->a_data);
		$adata['age']=$pdata['age'];
		$SQL->query("update alarm set a_email=\"".$SQL->escape($em)."\",a_data=\"".$SQL->escape(serialize($adata))."\" where a_id=".$fe->a_id);
	    }
	}
    }

    function setdata($fe) {
	$this->f=$fe;
	$this->w=unserialize($fe->a_data);
	$this->a=array();
    }
    
    private function pushalarm($t,$e) {
	global $SQL;
	if($e=='R') $this->tosend=true;
	$this->a[]="(".$t[0].",".$this->f->a_vid.",".$this->f->a_mid.",".$this->f->a_uid.",'".$e."',".$t[1].",\"".$SQL->escape($this->f->a_class)."\",\"".
	    $SQL->escape(serialize($this->w))."\",\"".$this->f->a_crit."\")";
    }
    
    function workdata(&$val) {
	if($val[0]<=$this->w['from'][0]) return 0;
	$this->w['from']=$val;
	$lowest=time()-$this->w['age']*3600;
	if($this->w['set'] && $val[0]>=$lowest) {
	    $this->w['set']=0;
	    $this->pushalarm($val,'F');
	    return 1;
	}
	return 0;
    }
    
    function savedata($send) { // should ignore supressing sending email ?
	global $SQL;
	
	if($send && $this->tosend) $SQL->query("update alarm set a_alarmed='Y',a_mailed=subdate(now(),interval 1 year) where a_alarmed='N' && a_id=".$this->f->a_id);
	$SQL->query("update alarm set a_data=\"".$SQL->escape(serialize($this->w))."\" where a_id=".$this->f->a_id);
// also flush alarms here
	if(count($this->a)) {
	    foreach(array_chunk($this->a,1024) as $val) {
		$SQL->query("insert into alarmlog (al_date,al_vid,al_mid,al_uid,al_edge,al_value,al_class,al_data,al_crit) values ".implode(",",$val));
	    }
	}
	return 0;
    }

    function cron($fe) {
	global $SQL;
	
	val_lock($fe->a_vid,$fe->a_mid);
	$this->f=$fe;
	$this->w=unserialize($fe->a_data);
	if(!$this->w['set']) {
	    $lowest=time()-$this->w['age']*3600;
	    if($this->w['from'][0]<$lowest) {
		$this->w['set']=1;
		$this->pushalarm($this->w['from'],'R');
		$this->savedata(true);
	    }
	}
	val_unlock();
    }

    function desc(&$dt) {
	$data=unserialize($dt);
	return "stáří dat: ".get_ind($data,"age")." hodin";
    }
}
