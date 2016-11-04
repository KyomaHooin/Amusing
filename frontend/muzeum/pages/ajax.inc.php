<?php

$_NOHEAD=true;

function over() {
    echo "";
    exit();
//    redir(root().$_DEFAULTPAGE);
}


if(!$_SESSION->user) over();

switch($ARGC) {
case 2:
    switch($ARGV[0]) {
    case "getbuildsel":
	$bcity=$ARGV[1];
	$opts=array(0=>"Zvolte budovu");
	if($bcity!="0") {
	    $qe=$SQL->query("select * from building where b_city=\"".$SQL->escape(hex2bin($bcity))."\" order by b_name");
	    while($fe=$qe->obj()) {
		$opts[$fe->b_id]=$fe->b_name;
	    }
	}
	echo input_select("001_ajax_build",$opts);
	break;
    case "getbuildsel2":
	$bcity=$ARGV[1];
	$opts=array(0=>"Všechny budovy");
	if($bcity!="0") {
	    $qe=$SQL->query("select * from building where b_city=\"".$SQL->escape(hex2bin($bcity))."\" order by b_name");
	    while($fe=$qe->obj()) {
		$opts[$fe->b_id]=$fe->b_name;
	    }
	}
	echo input_select("001_ajax_build",$opts);
	break;
    case "getroomsel":
	$bid=(int)$ARGV[1];
	$opts=array(0=>"Zvolte místnost");
	$qe=$SQL->query("select * from room where r_bid=\"".$SQL->escape($bid)."\" order by r_desc");
	while($fe=$qe->obj()) {
	    $opts[$fe->r_id]=$fe->r_desc;
	}
	echo input_select("001_ajax_room",$opts);
	break;
    case "getroomsel2":
	$bid=(int)$ARGV[1];
	$opts=array(0=>"Všechny místnosti");
	$qe=$SQL->query("select * from room where r_bid=\"".$SQL->escape($bid)."\" order by r_desc");
	while($fe=$qe->obj()) {
	    $opts[$fe->r_id]=$fe->r_desc;
	}
	echo input_select("001_ajax_room",$opts);
	break;
    case "getmeassel":
	$rid=(int)$ARGV[1];
	$opts=array(0=>"Zvolte měřící bod");
	$qe=$SQL->query("select * from measuring where m_rid=\"".$SQL->escape($rid)."\" order by m_desc");
	while($fe=$qe->obj()) {
	    $opts[$fe->m_id]=$fe->m_desc;
	}
	echo input_select("001_ajax_meas",$opts);
	break;
    case "getmeassel2":
	$rid=(int)$ARGV[1];
	$opts=array(0=>"Všechny měřící body");
	$qe=$SQL->query("select * from measuring where m_rid=\"".$SQL->escape($rid)."\" order by m_desc");
	while($fe=$qe->obj()) {
	    $opts[$fe->m_id]=$fe->m_desc;
	}
	echo input_select("001_ajax_meas",$opts);
	break;
    default:
	over();
    }
    break;
case 3:
    switch($ARGV[0]) {
    case "checksenmeas":
	$sid=(int)$ARGV[1];
	$mid=(int)$ARGV[2];
	header("Content-Type: text/plain");
	if(!$mid) echo json_encode(array("mbusy"=>false));
	else {
	    if($sid>0) $qe=$SQL->query("select * from sensor where s_mid=\"".$SQL->escape($mid)."\" && s_id!=\"".$SQL->escape($sid)."\"");
	    else $qe=$SQL->query("select * from sensor where s_mid=\"".$SQL->escape($mid)."\"");
	    echo json_encode(array("mbusy"=>($qe->rowcount()!=0)));
	}
	break;
    default:
	over();
    }
    break;
default:
    over();
}

