<?php

class c_alarm_newdata extends c_alarm_gen {
    var $f;
    var $w;
    var $a;
    
    var $lastt;
    
    var $age;
    
    var $preset;
    
    var $tosend;
    
    function __construct() {
	$this->f=false;
	$this->w=false;
	$this->a=array();
	$this->preset=0;
	$this->tosend=false;
	$this->lastt=0;
    }

    function showform() {
	echo "<table class=\"nobr\">";
	echo "<tr><td>Indikátor nových dat.</td></tr>";
	echo "</table>";
    }

    function checkform(&$rerr) {
    }

    function checkdef($pid,&$dt) {
	$this->preset=$pid;
	return true;
    }

    function loadtempform(&$tmp,$dt) {
    }

    function saveform($vid,$mid,$desc,$em,$crit) {
	global $SQL;
    
	$qe=$SQL->query("select * from varmeascache where vmc_mid=".$mid." && vmc_varid=".$vid);
	$fe=$qe->obj();
	if($fe) {
	    $tm=$fe->vmc_lastrawtime;
	} else {
	    $tm=0;
	}
	
	$SQL->query("insert into alarm set
	    a_desc=\"".$SQL->escape($desc)."\",
	    a_email=\"".$SQL->escape($em)."\",
	    a_vid=\"".$SQL->escape($vid)."\",
	    a_mid=\"".$SQL->escape($mid)."\",
	    a_uid=".uid().",
	    a_preset=".$this->preset.",
	    a_class=\"c_alarm_newdata\",
	    a_crit=\"".$SQL->escape($crit)."\",
	    a_data=\"".$SQL->escape(serialize(array('set'=>0,'from'=>$tm)))."\""); // from time 0 ?
    }
    
    function savedef($id,$desc,$em) {
	global $SQL;
	
	if(!$id) {
	    $SQL->query("insert into alarm_preset set
		ap_desc=\"".$SQL->escape($desc)."\",
		ap_email=\"".$SQL->escape($em)."\",
		ap_class=\"c_alarm_newdata\",
		ap_data=\"".$SQL->escape(serialize(array()))."\"");
	} else {
	    // update all existing alarms of that template
	    $pdata=array();
	    $SQL->query("update alarm_preset set
		ap_desc=\"".$SQL->escape($desc)."\",
		ap_email=\"".$SQL->escape($em)."\",
		ap_data=\"".$SQL->escape(serialize($pdata))."\"
		where ap_id=\"".$SQL->escape($id)."\" && ap_class=\"c_alarm_newdata\"");
	    $qe=$SQL->query("select * from alarm where a_preset=\"".$SQL->escape($id)."\" && a_class=\"c_alarm_newdata\"");
	    while($fe=$qe->obj()) {
		$adata=unserialize($fe->a_data);
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
	if($val[0]>$this->lastt) $this->lastt=$val[0];
	return 0;
    }
    
    function savedata($send) {
	global $SQL;
    
	if($this->lastt>$this->w['from']) {
	    $this->w['from']=$this->lastt;
	    
	    $this->pushalarm(array($this->lastt,0),'R');
	    $this->pushalarm(array($this->lastt,0),'F');
	    if($this->tosend) $SQL->query("update alarm set a_alarmed='Y',a_mailed=subdate(now(),interval 1 year) where a_alarmed='N' && a_id=".$this->f->a_id);
	    $SQL->query("update alarm set a_data=\"".$SQL->escape(serialize($this->w))."\" where a_id=".$this->f->a_id);
// also flush alarms here
	    if(count($this->a)) {
		foreach(array_chunk($this->a,1024) as $val) {
		    $SQL->query("insert into alarmlog (al_date,al_vid,al_mid,al_uid,al_edge,al_value,al_class,al_data,al_crit) values ".implode(",",$val));
		}
	    }
	    return 2;
	}
	return 0;
    }

    function cron($fe) {
    }

    function desc(&$dt) {
	$data=unserialize($dt);
	return "nová data";
    }
}
