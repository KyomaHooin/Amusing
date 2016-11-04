<?php

function rin1getvars($url,$sid) {
    if(!strlen($url)) return false;
    $data=array("sid"=>$sid,"cmd"=>"getvars");
    $options=array(
	"http"=>array(
	    "header"  => "Content-type: application/x-www-form-urlencoded\r\n",
	    "method"  => "POST",
	    "content" => http_build_query($data),
	)
    );
    $context=stream_context_create($options);
    $result=@file_get_contents($url,false,$context);
    if($result===false) return false;
    $ls=explode("\n",$result);
    if(trim($ls[0])!="ok") return false;
    array_shift($ls);
    $ret=array();
    foreach($ls as $v) {
	$vr=trim($v);
	if(!strlen($vr)) continue;
	$ret[]=$vr;
    }
    return $ret;
}

function rin1getdata($url,$sid,$var,$from,$to) {
    global $SQL;
    if(!strlen($url)) return false;
    if(!is_array($var)) $var=array($var);
// check limit due to binary data
    $limit=10000;
    foreach($var as $v) {
	$qe=$SQL->query("select * from varcodes where vc_text=\"".$SQL->escape($v)."\"");
	$fe=$qe->obj();
	if($fe && $fe->vc_bin=='Y') {
	    $limit=50;
	    break;
	}
    }
    
    $data=array("sid"=>$sid,"cmd"=>"getdata","var"=>$var,"from"=>$from,"to"=>$to,"limit"=>$limit);
    $options=array(
	"http"=>array(
	    "header"  => "Content-type: application/x-www-form-urlencoded\r\n",
	    "method"  => "POST",
	    "content" => http_build_query($data),
	)
    );
    $context=stream_context_create($options);
    $result=@file_get_contents($url,false,$context);
    if($result===false) return false;
    $ls=explode("\n",$result);
    if(trim($ls[0])!="ok") return false;
    array_shift($ls);
    $ret=array();
    foreach($ls as $v) {
	$inn=explode(";",trim($v));
	if(count($inn)==count($var)+1) $ret[]=$inn;
    }
    return $ret;
}
