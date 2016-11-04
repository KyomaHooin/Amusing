<?php

abstract class c_alarm_gen {
    private static $alarms=false;
    private static $descs;
    private static $idbyname;
    
    private static function getalarms() {
	if(!self::$alarms) {
	    self::$alarms=array(
		1=>"c_alarm_dx",
		2=>"c_alarm_extreme",
		3=>"c_alarm_nodata",
		4=>"c_alarm_newdata"
	    );
	    self::$descs=array(1=>"diference",2=>"extrém",3=>"stáří dat",4=>"nová data");
	    self::$idbyname=array(
		"c_alarm_dx"=>1,"c_alarm_extreme"=>2,"c_alarm_nodata"=>3,"c_alarm_newdata"=>4
	    );
	}
	return self::$alarms;
    }

    static function getalarmbyid($i) {
	self::getalarms();
	$cn=get_ind(self::$alarms,$i);
	if($cn) return new $cn();
	return false;
    }

    static function getalarmbyname($c) {
	self::getalarms();
	$i=get_ind(self::$idbyname,$c);
	if($i) return new $c();
	return false;
    }
    
    static function getalltypes() {
	self::getalarms();
	$ret=array();
	foreach(self::$alarms as $key=>$val) {
	    $ret[$val]=self::$descs[$key];
	}
	return $ret;
    }
    
    static function getdescbyname($cl) {
	self::getalarms();
	$id=get_ind(self::$idbyname,$cl);
	if(!$id) return false;
	return get_ind(self::$descs,$id);
    }
    
    abstract function showform();
    abstract function checkform(&$rerr);
    abstract function checkdef($pid,&$dt);
    abstract function loadtempform(&$tmp,$dt);
    abstract function saveform($vid,$mid,$desc,$em,$crit);
    abstract function savedef($id,$desc,$em);
    
    abstract function setdata($fe);
    abstract function workdata(&$val);
    abstract function savedata($send);
    
    abstract function cron($fe);
    
    abstract function desc(&$dt);
}
