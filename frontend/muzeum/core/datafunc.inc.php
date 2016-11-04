<?php

function val_checkdatatables($f,$t) {
    global $SQL;
    dblock();
    $tabs=array();
    $qe=$SQL->query("show tables like \"%values%\"");
    while($fe=$qe->row()) {
	$tabs[]=$fe[0];
    }
    for(;$f<=$t;$f++) {
	if(!in_array("rawvalues_".$f,$tabs)) {
	    $SQL->query("create table rawvalues_".$f."
		(rv_mid int unsigned,
		rv_varid int unsigned,
		rv_sid int unsigned,
		rv_date bigint,
		rv_value double,
		primary key (rv_mid,rv_varid,rv_date)) engine=myisam");
	    if($SQL->errnum) throw new Exception("Cant create table: ".$SQL->err);
	}
	if(!in_array("values_".$f,$tabs)) {
	    $SQL->query("create table values_".$f."
		(v_mid int unsigned,
		v_varid int unsigned,
		v_date bigint,
		v_value double,
		primary key (v_mid,v_varid,v_date)) engine=myisam");
	    if($SQL->errnum) throw new Exception("Cant create table: ".$SQL->err);
	}
	if(!in_array("valuesblob_".$f,$tabs)) {
	    $SQL->query("create table valuesblob_".$f."
		(vb_mid int unsigned,
		vb_varid int unsigned,
		vb_date bigint,
		vb_value mediumblob,
		primary key (vb_mid,vb_varid,vb_date)) engine=myisam");
	    if($SQL->errnum) throw new Exception("Cant create table: ".$SQL->err);
	}
    }
    dbunlock();
}

$_VAL_LOCKKEY=false;
$_VAL_LOCKHANDLE=false;
$_VAL_LOCKCNT=0;

function val_gunlock() {
    global $_VAL_LOCKCNT;
    while($_VAL_LOCKCNT) val_unlock();
}

function val_lock($vid,$mid) {
    global $SQL;
    global $_VAL_LOCKKEY;
    global $_VAL_LOCKHANDLE;
    global $_VAL_LOCKCNT;
    global $_LOCKDIR;
    if(!$_VAL_LOCKCNT) {
	$_VAL_LOCKKEY=sprintf("%08u_%08u.lock",$vid,$mid);
	$SQL->query("lock tables locker write");
	$SQL->query("insert into locker set lk_id=\"".$_VAL_LOCKKEY."\",lk_cnt=1 on duplicate key update lk_cnt=lk_cnt+1");
	$SQL->unlock();

	$_VAL_LOCKHANDLE=@fopen($_LOCKDIR."/".$_VAL_LOCKKEY,"w+");
	if(!$_VAL_LOCKHANDLE) {
	    $SQL->query("update locker set lk_cnt=lk_cnt-1 where lk_id=\"".$_VAL_LOCKKEY."\"");
	    throw new Exception("Cant lock");
	}
	if(!@flock($_VAL_LOCKHANDLE,LOCK_EX)) {
	    @fclose($_VAL_LOCKHANDLE);
	    $SQL->query("update locker set lk_cnt=lk_cnt-1 where lk_id=\"".$_VAL_LOCKKEY."\"");
	    throw new Exception("Cant lock");
	}

    }
    $_VAL_LOCKCNT++;
    register_shutdown_function("val_gunlock");
}

function val_unlock() {
    global $SQL;
    global $_VAL_LOCKKEY;
    global $_VAL_LOCKHANDLE;
    global $_VAL_LOCKCNT;
    global $_LOCKDIR;

    if($_VAL_LOCKCNT) {
	$_VAL_LOCKCNT--;
	if(!$_VAL_LOCKCNT) {
	    $SQL->query("lock tables locker write");
	    @fclose($_VAL_LOCKHANDLE);
	    $qe=$SQL->query("select * from locker where lk_id=\"".$_VAL_LOCKKEY."\"");
	    $fe=$qe->obj();
	    if($fe->lk_cnt==1) {
		$SQL->query("delete from locker where lk_id=\"".$_VAL_LOCKKEY."\"");
		@unlink($_LOCKDIR."/".$_VAL_LOCKKEY);
	    } else $SQL->query("update locker set lk_cnt=lk_cnt-1 where lk_id=\"".$_VAL_LOCKKEY."\"");
	    $SQL->unlock();
	}
    }
}

function val_flushraws($y,&$vals) {
    global $SQL;
    $SQL->query("insert into rawvalues_".$y." (rv_mid,rv_varid,rv_sid,rv_date,rv_value) values
	".implode(",",$vals)." on duplicate key update rv_value=values(rv_value),rv_sid=values(rv_sid)");
    if($SQL->errnum) throw new Exception($SQL->err);
    return $SQL->info();
}

function val_flushavgs($y,&$vals) {
    global $SQL;
    $SQL->query("insert into values_".$y." (v_mid,v_varid,v_date,v_value) values
	".implode(",",$vals)." on duplicate key update v_value=values(v_value)");
    if($SQL->errnum) throw new Exception($SQL->err);
//    return $SQL->info();
}

function val_saveblobvalues(&$vals,$vid,$mid) {
    global $SQL;
    
// assume not so many lines, store it per line
// stupid hard coded cycle
//    $mints2=$vals[0][0];
//    $maxts2=$vals[count($vals)-1][0];
//    $lastrawval=get_ind(@unserialize($vals[count($vals)-1][1]),"value");
//    $lastrawtime=$vals[count($vals)-1][0];

    val_lock($vid,$mid);

    foreach($vals as $val) {
	$cy=gmdate("Y",$val[0]);
	$SQL->query("insert into valuesblob_".$cy." set
	    vb_mid=".$mid.",
	    vb_varid=".$vid.",
	    vb_date=".$val[0].",
	    vb_value=\"".$SQL->escape($val[1])."\"
	on duplicate key update
	    vb_value=\"".$SQL->escape($val[1])."\"");
	if($SQL->errnum) throw new Exception($SQL->err);
    }

    val_unlock();

// update varmeascache
    //$SQL->query("insert into varmeascache set 
//	    vmc_mid=".$mid.",
//	    vmc_varid=".$vid.",
//	    vmc_mintime=".$mints2.",
//	    vmc_maxtime=".$maxts2.",
//	    vmc_lastrawtime=".$lastrawtime.",
//	    vmc_lastrawvalue=\"".$SQL->escape($lastrawval)."\"
//	on duplicate key update 
//	    vmc_mintime=least(".$mints2.",vmc_mintime),
//	    vmc_maxtime=greatest(".$maxts2.",vmc_maxtime),
//	    vmc_lastrawtime=greatest(".$lastrawtime.",vmc_lastrawtime),
//	    vmc_lastrawvalue=if(vmc_lastrawtime>".$lastrawtime.",vmc_lastrawvalue,\"".$SQL->escape($lastrawval)."\")");

    return array('r'=>0,'d'=>0,'w'=>0,'a'=>0);
}

// it has to be sorted by time
function val_saverawvalues(&$vals,$vid,$mid,$sid,$send=false) {
    global $SQL;

    $qe=$SQL->query("select * from variable left join varcodes on vc_text=var_code where var_id=".$vid);
    $vr=$qe->obj();
    if(!$vr) return array('r'=>0,'d'=>0,'w'=>0,'a'=>0);
    if(!count($vals)) return array('r'=>0,'d'=>0,'w'=>0,'a'=>0);
    
    $mints=$vals[0][0];
    $maxts=$vals[count($vals)-1][0];
    $lastrawval=$vals[count($vals)-1][1];
    $lastrawtime=$vals[count($vals)-1][0];
    
    $miny=gmdate("Y",$mints);
    $maxy=gmdate("Y",$maxts);
    val_checkdatatables($miny-1,$maxy+1); // overdrive
    
    val_lock($vid,$mid);
    //if($vr->vc_bin=='Y') {
//	$ret=val_saveblobvalues($vals,$vid,$mid);
//	val_unlock();
//	return $ret;
    //}
    
    $pref="(".$mid.",".$vid.",".$sid.",";
    $sqv=array();
    $lin=array();
    
    $alarms=array();
    $qe=$SQL->query("select * from alarm where a_vid=".$vid." && a_mid=".$mid);
    while($fe=$qe->obj()) {
	$al=c_alarm_gen::getalarmbyname($fe->a_class);
	if($al) {
	    $al->setdata($fe);
	    $alarms[]=$al;
	}
    }
    
    $curry=$miny;
    $nexty=gmmktime(0,0,0,1,1,$curry+1);
    $tota=0;
    foreach($vals as $val) {
// alarm solving
	foreach($alarms as $al) $tota+=$al->workdata($val);

	if($val[0]<$nexty) {
	    $sqv[]=$pref.$val[0].",".$val[1].")";
	    if(!(count($sqv)&0x3ff)) {
		$lin[]=val_flushraws($curry,$sqv);
		$sqv=array();
	    }
	} else { // next year
	    if(count($sqv)) $lin[]=val_flushraws($curry,$sqv);
	    $curry=gmdate("Y",$val[0]);
	    $nexty=gmmktime(0,0,0,1,1,$curry+1);
	    $sqv=array();
	    $sqv[]=$pref.$val[0].",".$val[1].")";
	}
    }
    if(count($sqv)) $lin[]=val_flushraws($curry,$sqv);
    foreach($alarms as $al) $tota+=$al->savedata($send);
    
    if($vr->vc_expperiod) {
// compute avg values into hourly profile table, has to be done after that due to possibility of revritting or adding incompletely read data
	$mints2=$mints-1-(($mints-1)%3600);
	$maxts2=$maxts-1-(($maxts-1)%3600)+3600;
    
	$miny=gmdate("Y",$mints2);
	$maxy=gmdate("Y",$maxts2);

	$pref="(".$mid.",".$vid.",";
	$sqv=array(); // i have to select it one by one due to damn alarm solutions
	$currts=$mints2+3600;
	$curry=gmdate("Y",$currts);
	$nexty=gmmktime(0,0,0,1,1,$curry+1);

	$valavg=0.0;
	$valcnt=0;
	for(;$miny<=$maxy;$miny++) { // or do it through buffered command and insert in another sql object (10MB roughly for 5minute profile, whole year, 100 bytes per record)
	    $qe=$SQL->query("select rv_date,rv_value from rawvalues_".$miny." where rv_mid=".$mid." && rv_varid=".$vid." && rv_date>".$mints2." && rv_date<=".$maxts2." order by rv_date");
	    while($fe=$qe->obj()) {
		if($fe->rv_date<=$currts) {
		    $valavg+=$fe->rv_value;
		    $valcnt++;
		} else {
		    if($valcnt) {
			$sqv[]=$pref.$currts.",".($valavg/$valcnt).")"; // watch out culture specifics during coding delimiter
			if(!(count($sqv)&0x3ff)) {
			    val_flushavgs($curry,$sqv);
			    $sqv=array();
			}
		    }
		    $valavg=$fe->rv_value;
		    $valcnt=1;
		    $currts=$fe->rv_date-1-(($fe->rv_date-1)%3600)+3600;
		    if($currts>=$nexty) { // shift to another year
			if(count($sqv)) val_flushavgs($curry,$sqv);
			$sqv=array();
			$curry=gmdate("Y",$currts);
			$nexty=gmmktime(0,0,0,1,1,$curry+1);
		    }
		}
	    }
	}
	if($valcnt) $sqv[]=$pref.$currts.",".($valavg/$valcnt).")";
	if(count($sqv)) val_flushavgs($curry,$sqv);
    } else {
	$mints2=$mints;
	$maxts2=$maxts;
	
	$pref="(".$mid.",".$vid.",";
	$sqv=array();
	$curry=gmdate("Y",$mints2);
	$nexty=gmmktime(0,0,0,1,1,$curry+1);
	foreach($vals as $val) {
	    if($val[0]<$nexty) {
		$sqv[]=$pref.$val[0].",".$val[1].")";
		if(!(count($sqv)&0x3ff)) {
		    val_flushavgs($curry,$sqv);
		    $sqv=array();
		}
	    } else { // next year
		if(count($sqv)) val_flushavgs($curry,$sqv);
		$curry=gmdate("Y",$val[0]);
		$nexty=gmmktime(0,0,0,1,1,$curry+1);
		$sqv=array();
		$sqv[]=$pref.$val[0].",".$val[1].")";
	    }
	}
	if(count($sqv)) val_flushavgs($curry,$sqv);
    }

// update varmeascache
    $SQL->query("insert into varmeascache set 
	    vmc_mid=".$mid.",
	    vmc_varid=".$vid.",
	    vmc_mintime=".$mints2.",
	    vmc_maxtime=".$maxts2.",
	    vmc_lastrawtime=".$lastrawtime.",
	    vmc_lastrawvalue=\"".$SQL->escape($lastrawval)."\"
	on duplicate key update 
	    vmc_mintime=least(".$mints2.",vmc_mintime),
	    vmc_maxtime=greatest(".$maxts2.",vmc_maxtime),
	    vmc_lastrawtime=greatest(".$lastrawtime.",vmc_lastrawtime),
	    vmc_lastrawvalue=if(vmc_lastrawtime>".$lastrawtime.",vmc_lastrawvalue,\"".$SQL->escape($lastrawval)."\")");
    
// not finally yet, it has to be at least php version 5.5
    val_unlock();
    
    $r=0;
    $d=0;
    $w=0;
    foreach($lin as $val) {
	if(preg_match('/^Records\:\s+(\d+)\s+Duplicates\:\s+(\d+)\s+Warnings\:\s+(\d+)$/',trim($val),$mch)) {
	    $r+=$mch[1];
	    $d+=$mch[2];
	    $w+=$mch[3];
	}
    }
    return array('r'=>$r,'d'=>$d,'w'=>$w,'a'=>$tota);
}
