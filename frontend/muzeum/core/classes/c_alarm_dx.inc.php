<?php

class c_alarm_dx extends c_alarm_gen {
    var $f;
    var $w;
    var $a;
    
    var $diff;
    var $tim;
    var $hyst;
    
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
	echo "<tr><td>Maximální diference:&nbsp;</td><td>".input_text_temp_err("002_alarm_dx_diff")."</td></tr>";
	echo "<tr><td>Za čas:&nbsp;</td><td>".input_text_temp_err("002_alarm_dx_time")." minut</td></tr>";
	echo "<tr><td>Trvání (hystereze):&nbsp;</td><td>".input_text_temp_err("002_alarm_dx_hyst")." minut</td></tr>";
	echo "</table>";
    }

    function checkform(&$rerr) {
	$this->diff=trim(get_ind($_POST,"002_alarm_dx_diff"));
	if(!is_numeric($this->diff)) {
	    $rerr['002_alarm_dx_diff']="Diference není číslo";
	    return;
	}
	if(!$this->diff) {
	    $rerr['002_alarm_dx_diff']="Diference nemůže být nula";
	    return;
	}
	$this->tim=trim(get_ind($_POST,"002_alarm_dx_time"));
	if(!is_numeric($this->tim)) {
	    $rerr['002_alarm_dx_time']="Čas není číslo";
	    return;
	}
	if($this->tim<=0) {
	    $rerr['002_alarm_dx_time']="Nelze mít nulový nebo záporný čas";
	    return;
	}
	$this->hyst=trim(get_ind($_POST,"002_alarm_dx_hyst"));
	if(!is_numeric($this->hyst)) {
	    $rerr['002_alarm_dx_hyst']="Trvání není číslo";
	    return;
	}
	if($this->hyst<0) {
	    $rerr['002_alarm_dx_hyst']="Nelze mít záporné trvání";
	    return;
	}
    }

    function checkdef($pid,&$dt) {
	$data=unserialize($dt);
	$this->diff=get_ind($data,"dy");
	$this->tim=get_ind($data,"dx")/60;
	$this->hyst=get_ind($data,"hyst")/60;
	if(!is_numeric($this->diff)) return false;
	if(!$this->diff) return false;
	if(!is_numeric($this->tim)) return false;
	if($this->tim<=0) return false;
	if(!is_numeric($this->hyst)) return false;
	if($this->hyst<0) return false;
	$this->preset=$pid;
	return true;
    }
    
    function loadtempform(&$tmp,$dt) {
	$data=unserialize($dt);
	$tmp["002_alarm_dx_diff"]=get_ind($data,"dy");
	$tmp["002_alarm_dx_time"]=get_ind($data,"dx")/60;
	$tmp["002_alarm_dx_hyst"]=get_ind($data,"hyst")/60;
    }

    function saveform($vid,$mid,$desc,$em,$crit) {
	global $SQL;
    
	$SQL->query("insert into alarm set
	    a_desc=\"".$SQL->escape($desc)."\",
	    a_email=\"".$SQL->escape($em)."\",
	    a_vid=\"".$SQL->escape($vid)."\",
	    a_mid=\"".$SQL->escape($mid)."\",
	    a_uid=".uid().",
	    a_preset=".$this->preset.",
	    a_class=\"c_alarm_dx\",
	    a_crit=\"".$SQL->escape($crit)."\",
	    a_data=\"".$SQL->escape(serialize(array('df'=>$this->diff/($this->tim*60),'dy'=>$this->diff,'dx'=>$this->tim*60,'hyst'=>$this->hyst*60,'from'=>0)))."\""); // from time 0 ?
    }
    
    function savedef($id,$desc,$em) {
	global $SQL;
	
	if(!$id) {
	    $SQL->query("insert into alarm_preset set
		ap_desc=\"".$SQL->escape($desc)."\",
		ap_email=\"".$SQL->escape($em)."\",
		ap_class=\"c_alarm_dx\",
		ap_data=\"".$SQL->escape(serialize(array('df'=>$this->diff/($this->tim*60),'dy'=>$this->diff,'dx'=>$this->tim*60,'hyst'=>$this->hyst*60)))."\"");
	} else {
	    // update all existing alarms of that template
	    $pdata=array('df'=>$this->diff/($this->tim*60),'dy'=>$this->diff,'dx'=>$this->tim*60,'hyst'=>$this->hyst*60);
	    $SQL->query("update alarm_preset set
		ap_desc=\"".$SQL->escape($desc)."\",
		ap_email=\"".$SQL->escape($em)."\",
		ap_data=\"".$SQL->escape(serialize($pdata))."\"
		where ap_id=\"".$SQL->escape($id)."\" && ap_class=\"c_alarm_dx\"");
	    $qe=$SQL->query("select * from alarm where a_preset=\"".$SQL->escape($id)."\" && a_class=\"c_alarm_dx\"");
	    while($fe=$qe->obj()) {
		$adata=unserialize($fe->a_data);
		$adata['df']=$pdata['df'];
		$adata['dy']=$pdata['dy'];
		$adata['dx']=$pdata['dx'];
		$adata['hyst']=$pdata['hyst'];
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
	if(!$this->w['from']) { // start
	    $this->w['from']=$val[0];
	    $this->w['lastval']=$val[1];
	    $this->w['dur']=0;
	    $this->w['set']=0;
	} else {
	    if($val[0]<=$this->w['from']) return 0;
	    $df=($val[1]-$this->w['lastval'])/($val[0]-$this->w['from']);
	
	    $thr=$this->w['df'];
	    if($thr<0) { // less than
		if($df>$thr) {
		    $this->w['from']=$val[0];
		    $this->w['dur']=0;
		    $this->w['lastval']=$val[1];
		    if($this->w['set']) {
			$this->w['set']=0;
			$this->pushalarm($val,'F');
			return 1;
		    }
		    return 0;
		}
		$this->w['dur']+=($val[0]-$this->w['from']);
		$this->w['from']=$val[0];
		$this->w['lastval']=$val[1];
		if(!$this->w['set'] && $this->w['dur']>$this->w['hyst']) {
		    $this->w['set']=1;
		    $this->pushalarm($val,'R');
		    return 1;
		}
		return 0;
	    }
	// bigger than
		if($df<$thr) {
		    $this->w['from']=$val[0];
		    $this->w['dur']=0;
		    $this->w['lastval']=$val[1];
		    if($this->w['set']) {
			$this->w['set']=0;
			$this->pushalarm($val,'F');
			return 1;
		    }
		    return 0;
		}
		$this->w['dur']+=($val[0]-$this->w['from']);
		$this->w['from']=$val[0];
		$this->w['lastval']=$val[1];
		if(!$this->w['set'] && $this->w['dur']>$this->w['hyst']) {
		    $this->w['set']=1;
		    $this->pushalarm($val,'R');
		    return 1;
		}
		return 0;
	}
	return 0;
    }
    
    function savedata($send) {
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
    }

    function desc(&$dt) {
	$data=unserialize($dt);
	return "diference: ".get_ind($data,"dy")." za čas: ".(get_ind($data,"dx")/60)." minut, trvání: ".(get_ind($data,"hyst")/60)." minut";
    }
}
