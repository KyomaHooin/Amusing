<?php

$_NOHEAD=true;

if($_SERVER['REMOTE_ADDR']!=$_SERVER['SERVER_ADDR']) exit();

header("Content-type: text/plain");

// ip check

$locked=false;

function makeemails($ems) {
    $ret=array();
    foreach(explode("\n",$ems) as $val) {
	foreach(explode(",",$val) as $e) {
	    $t=trim($e);
	    if(strlen($t) && preg_match('/[a-zA-Z0-9\.\-_]+@[a-zA-Z0-9\.\-_]+\.[a-zA-Z]+/',$t)) $ret[]=$t;
	}
    }
    return implode(",",$ret);
}

function sendwarning($fe) {
    global $SQL;
    global $_ALARMMAIL;
    global $_DOMAIN;
    global $_SENDMAIL;

    $from=$_ALARMMAIL;
    
    $headers="MIME-Version: 1.0\r\n";
    $headers.="From: Amusing <".$from.">\r\n";
    $headers.="Reply-To: ".$from."\r\n";
    $headers.="Content-type: text/html; charset=utf-8\r\n";
    
    $emails=makeemails($fe->a_email);
    if(!strlen($emails)) {
	logsys("invalid email, no alarm is sent (".strtr($fe->a_email,"\n\r","  ").")");
	return;
    }
    $alrm=c_alarm_gen::getalarmbyname($fe->a_class);
    if(!$alrm) {
	logsys("critical error during alarm processing");
	return; // wtf ?, critical log maybe
    }
    $atext=$alrm->desc($fe->a_data);
    for(;;) {
	$acid=strtr(uniqid(md5(rand()),true),".","_");
	$SQL->query("insert into alarmack set
	    ac_id=\"".$SQL->escape($acid)."\",
	    ac_atext=\"".$SQL->escape($atext)."\",
	    ac_aid=".$fe->a_id.",
	    ac_uid=".$fe->a_uid.",
	    ac_vid=".$fe->a_vid.",
	    ac_state='N',
	    ac_dategen=now(),
	    ac_mid=".$fe->a_mid);
	if(!$SQL->errnum) break;
	if($SQL->errnum!=1062) return; // dup and damn
    }
    $SQL->query("update alarm set a_alarmed='S',a_ackid=\"".$SQL->escape($acid)."\",a_mailed=now() where a_alarmed='Y' && a_id=".$fe->a_id);

    $rfm=array($fe->a_mid."_".$fe->a_vid);
    $rad="/1/1/1/1/0";
    $getref1d=root()."getplotref/".implode("-",$rfm)."/1D".$rad;

    $text='<html><head><meta charset="utf-8"></head><body><br>
    Alarm: '.$fe->a_desc.'<br><br>
    Měřící bod: '.$fe->m_desc.'<br><br>
    Místnost: '.$fe->r_desc.'<br><br>
    Lokalita: '.$fe->b_name.' '.$fe->b_street.' '.$fe->b_city.'<br><br>
    [ <a target="_blank" href="https://'.$_DOMAIN.$getref1d.'">GRAF</a> ] [ <a target="_blank" href="https://'.$_DOMAIN.root().'alarmacktab/m/'.$acid.'>POTVRZENÍ</a> ]<br></body></html>';
    $subject="=?utf-8?B?".base64_encode("Varování - Muzeum senzory")."?=";
    if($_SENDMAIL) {
	logsys("warning alarm mail sent to ".$emails);
	mail($emails,$subject,$text,$headers,"-f ".$from);
    }
//    print_read($fe);
// move to state 'S' for instance, create alarmack id, use it as part of url
}

function sendcritical($fe) {
// hmm, every time generate new alarmack id and delete previous one ?
    global $SQL;
    global $_ALARMMAIL;
    global $_DOMAIN;
    global $_SENDMAIL;

    $from=$_ALARMMAIL;
    
    $headers="MIME-Version: 1.0\r\n";
    $headers.="From: Amusing <".$from.">\r\n";
    $headers.="Reply-To: ".$from."\r\n";
    $headers.="Content-type: text/html; charset=utf-8\r\n";
    
    $emails=makeemails($fe->a_email);
    if(!strlen($emails)) {
	logsys("invalid email, no alarm is sent (".strtr($fe->a_email,"\n\r","  ").")");
	return;
    }
    $alrm=c_alarm_gen::getalarmbyname($fe->a_class);
    if(!$alrm) {
	logsys("critical error during alarm processing");
	return; // wtf ?, critical log maybe
    }
    $atext=$alrm->desc($fe->a_data);
    if(!strlen($fe->a_ackid)) {
	for(;;) {
	    $acid=strtr(uniqid(md5(rand()),true),".","_");
	    $SQL->query("insert into alarmack set
		ac_id=\"".$SQL->escape($acid)."\",
		ac_atext=\"".$SQL->escape($atext)."\",
		ac_aid=".$fe->a_id.",
		ac_uid=".$fe->a_uid.",
		ac_vid=".$fe->a_vid.",
		ac_state='N',
		ac_dategen=now(),
		ac_mid=".$fe->a_mid);
	    if(!$SQL->errnum) break;
	    if($SQL->errnum!=1062) return; // dup and damn
	}
    } else $acid=$fe->a_ackid;
    $SQL->query("update alarm set a_ackid=\"".$SQL->escape($acid)."\",a_mailed=now() where a_alarmed='Y' && a_id=".$fe->a_id);

    $rfm=array($fe->a_mid."_".$fe->a_vid);
    $rad="/1/1/1/1/0";
    $getref1d=root()."getplotref/".implode("-",$rfm)."/1D".$rad;

    $text='<html><head><meta charset="utf-8"></head><body><br>
    Alarm: '.$fe->a_desc.'<br><br>
    Měřící bod: '.$fe->m_desc.'<br><br>
    Místnost: '.$fe->r_desc.'<br><br>
    Lokalita: '.$fe->b_name.' '.$fe->b_street.' '.$fe->b_city.'<br><br>
    [ <a target="_blank" href="https://'.$_DOMAIN.$getref1d.'">GRAF</a> ] [ <a target="_blank" href="https://'.$_DOMAIN.root().'alarmacktab/m/'.$acid.'>POTVRZENÍ</a> ]<br></body></html>';
    $subject="=?utf-8?B?".base64_encode("Kritické - Muzeum senzory")."?=";
    if($_SENDMAIL) {
	logsys("critical alarm mail sent to ".$emails);
	mail($emails,$subject,$text,$headers,"-f ".$from);
    }
}

do {
    if(!cronlock()) {
	echo "cron lock";
	break;
    }
    echo "processing cron\n";
    $qe=$SQL->query("select * from sensor left join sensortype on s_type=st_id left join measuring on s_mid=m_id where s_active='Y' && m_active='Y'");
    while($fe=$qe->obj()) {
	$stt=c_stype_base::getsensorbyid($fe->s_type);
	if(!$stt) continue;
//	$stt->cron($fe);
	freshlock();
    }

// alarm crons (nodata class only ?)
    $qe=$SQL->query("select * from alarm where a_class=\"c_alarm_nodata\"");
    while($fe=$qe->obj()) {
	$al=c_alarm_gen::getalarmbyname($fe->a_class);
	if($al) $al->cron($fe);
    }
    
// alarm solving
    $qe=$SQL->query("select *,if(adddate(a_mailed,interval 1 day)<now(),0,1) as ag from alarm left join user on a_uid=u_id left join variable on var_id=a_vid left join measuring on a_mid=m_id left join room on r_id=m_rid left join building on b_id=r_bid where a_alarmed!='N'");
    while($fe=$qe->obj()) {
	if($fe->a_crit=='Y') { // send email every day till ack
	    if($fe->a_alarmed=='Y' && !$fe->ag) sendcritical($fe);
	} else { // warning, only one mail, wait for ack and set state to N
	    if($fe->a_alarmed=='Y') sendwarning($fe);
	}
    }
} while(false);
