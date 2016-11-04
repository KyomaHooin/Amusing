<?php

class c_alarm_extreme extends c_alarm_gen {
    var $f;
    var $w;
    var $a;
    
    var $ext;
    var $hyst;
    var $rel;
    
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
	echo "<tr><td>Extrémní hodnota:&nbsp;</td><td>".input_text_temp_err("002_alarm_ext_ext")."</td></tr>";
	echo "<tr><td>Trvání (hystereze):&nbsp;</td><td>".input_text_temp_err("002_alarm_ext_hyst")." minut</td></tr>";
	echo "<tr><td>Relace:&nbsp;</td><td>".input_select_temp_err("001_alarm_ext_rel",array(0=>"Větší než",1=>"Menší než"))."</td></tr>";
	echo "</table>";
    }

    function checkform(&$rerr) {
	$this->ext=trim(get_ind($_POST,"002_alarm_ext_ext"));
	if(!is_numeric($this->ext)) {
	    $rerr['002_alarm_ext_ext']="Extrém není číslo";
	    return;
	}
	$this->hyst=trim(get_ind($_POST,"002_alarm_ext_hyst"));
	if(!is_numeric($this->hyst)) {
	    $rerr['002_alarm_ext_hyst']="Trvání není číslo";
	    return;
	}
	if($this->hyst<0) {
	    $rerr['002_alarm_ext_hyst']="Nelze mít záporné trvání";
	    return;
	}
	$this->rel=(int)get_ind($_POST,"001_alarm_ext_rel");
	switch($this->rel) {
	case 0:
	case 1:
	    break;
	default:
	    $rerr['001_alarm_ext_rel']="Neplatná relace";
	    return;
	}
    }

    function checkdef($pid,&$dt) {
	$data=unserialize($dt);
	$this->ext=get_ind($data,"ext");
	$this->hyst=get_ind($data,"hyst")/60;
	$this->rel=get_ind($data,"rel");
	if(!is_numeric($this->ext)) return false;
	if(!is_numeric($this->hyst)) return false;
	if($this->hyst<0) return false;
	switch($this->rel) {
	case 0:
	case 1:
	    break;
	default:
	    return false;
	}
	$this->preset=$pid;
	return true;
    }

    function loadtempform(&$tmp,$dt) {
	$data=unserialize($dt);
	$tmp["002_alarm_ext_ext"]=get_ind($data,"ext");
	$tmp["002_alarm_ext_hyst"]=get_ind($data,"hyst")/60;
	$tmp["001_alarm_ext_rel"]=get_ind($data,"rel");
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
	    a_class=\"c_alarm_extreme\",
	    a_crit=\"".$SQL->escape($crit)."\",
	    a_data=\"".$SQL->escape(serialize(array('ext'=>$this->ext,'hyst'=>$this->hyst*60,'rel'=>$this->rel,'from'=>0)))."\""); // from time 0 ?
    }
    
    function savedef($id,$desc,$em) {
	global $SQL;
	
	if(!$id) {
	    $SQL->query("insert into alarm_preset set
		ap_desc=\"".$SQL->escape($desc)."\",
		ap_email=\"".$SQL->escape($em)."\",
		ap_class=\"c_alarm_extreme\",
		ap_data=\"".$SQL->escape(serialize(array('ext'=>$this->ext,'hyst'=>$this->hyst*60,'rel'=>$this->rel)))."\"");
	} else {
	    // update all existing alarms of that template
	    $pdata=array('ext'=>$this->ext,'hyst'=>$this->hyst*60,'rel'=>$this->rel);
	    $SQL->query("update alarm_preset set
		ap_desc=\"".$SQL->escape($desc)."\",
		ap_email=\"".$SQL->escape($em)."\",
		ap_data=\"".$SQL->escape(serialize($pdata))."\"
		where ap_id=\"".$SQL->escape($id)."\" && ap_class=\"c_alarm_extreme\"");
	    $qe=$SQL->query("select * from alarm where a_preset=\"".$SQL->escape($id)."\" && a_class=\"c_alarm_extreme\"");
	    while($fe=$qe->obj()) {
		$adata=unserialize($fe->a_data);
		$adata['ext']=$pdata['ext'];
		$adata['hyst']=$pdata['hyst'];
		$adata['rel']=$pdata['rel'];
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
	
	    $thr=$this->w['ext'];
	    if($this->w['rel']) { // less than
		if($this->w['lastval']>$thr) {
		    if($val[1]>$thr) {
			$this->w['from']=$val[0];
			$this->w['dur']=0;
			$this->w['set']=0;
			$this->w['lastval']=$val[1];
			return 0; // both values are bigger, so ok
		    }
		    $dx=$val[0]-$this->w['from'];
		    $dy=$this->w['lastval']-$val[1];
		    $dy2=$this->w['lastval']-$thr;
		    $dx2=$dx-$dx*$dy2/$dy;
		    $this->w['dur']=$dx2;
		    $this->w['from']=$val[0];
		    $this->w['lastval']=$val[1];
		    if($this->w['dur']>$this->w['hyst']) {
			$this->w['set']=1;
			$this->pushalarm($val,'R');
			return 1;
		    }
		    return 0;
		}
		if($val[1]>$thr) { // ending duration
		    $dx=$val[0]-$this->w['from'];
		    $dy=$val[1]-$this->w['lastval'];
		    $dy2=$thr-$this->w['lastval'];
		    $dx2=$dx*$dy2/$dy;
		    $this->w['from']=$val[0];
		    $this->w['lastval']=$val[1];
		    if($this->w['set']) {
			$this->pushalarm($val,'F');
		    } else {
			if($this->w['dur']+$dx2>$this->w['hyst']) {
			    $this->pushalarm($val,'R');
			    $this->pushalarm($val,'F');
			    $this->w['dur']=0; // end of alarm
			    return 1;
			}
		    }
		    $this->w['dur']=0;
		    $this->w['set']=0;
		    return 0;
		}
		$dx=$val[0]-$this->w['from'];
		$this->w['dur']+=$dx;
		$this->w['from']=$val[0];
		$this->w['lastval']=$val[1];
		if($this->w['dur']>$this->w['hyst'] && !$this->w['set']) {
		    $this->pushalarm($val,'R');
		    $this->w['set']=1;
		    return 1;
		}
		return 0;
	    }
	// bigger than

		if($this->w['lastval']<$thr) {
		    if($val[1]<$thr) {
			$this->w['from']=$val[0];
			$this->w['dur']=0;
			$this->w['set']=0;
			$this->w['lastval']=$val[1];
			return 0; // both values are less than, so ok
		    }
		    $dx=$val[0]-$this->w['from'];
		    $dy=$val[1]-$this->w['lastval'];
		    $dy2=$thr-$this->w['lastval'];
		    $dx2=$dx-$dx*$dy2/$dy;
		    $this->w['dur']=$dx2;
		    $this->w['from']=$val[0];
		    $this->w['lastval']=$val[1];
		    if($this->w['dur']>$this->w['hyst']) {
			$this->w['set']=1;
			$this->pushalarm($val,'R');
			return 1;
		    }
		    return 0;
		}
		if($val[1]<$thr) { // ending duration
		    $dx=$val[0]-$this->w['from'];
		    $dy=$this->w['lastval']-$val[1];
		    $dy2=$this->w['lastval']-$thr;
		    $dx2=$dx*$dy2/$dy;
		    $this->w['from']=$val[0];
		    $this->w['lastval']=$val[1];
		    if($this->w['set']) {
			$this->pushalarm($val,'F');
		    } else {
			if($this->w['dur']+$dx2>$this->w['hyst'] && !$this->w['set']) {
			    $this->pushalarm($val,'R');
			    $this->pushalarm($val,'F');
			    $this->w['dur']=0; // end of alarm
			    return 1;
			}
		    }
		    $this->w['dur']=0;
		    $this->w['set']=0;
		    return 0;
		}
		$dx=$val[0]-$this->w['from'];
		$this->w['dur']+=$dx;
		$this->w['from']=$val[0];
		$this->w['lastval']=$val[1];
		if($this->w['dur']>$this->w['hyst'] && !$this->w['set']) {
		    $this->pushalarm($val,'R');
		    $this->w['set']=1;
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
	return "extrém: ".get_ind($data,"ext").", trvání: ".(get_ind($data,"hyst")/60).", relace: ".(get_ind($data,"rel")?"Menší než":"Větší než");
    }
}
