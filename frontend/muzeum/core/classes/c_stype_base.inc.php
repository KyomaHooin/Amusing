<?php

abstract class c_stype_base {
    var $fe;

    protected function myid() {
	global $SQL;
	$qe=$SQL->query("select * from sensortype where st_class=\"".$SQL->escape(get_class($this))."\"");
	$fe=$qe->obj();
	if(!$fe) return 0;
	return $fe->st_id;
    }

    static function getsensorbyid($i) {
	global $SQL;
	$qe=$SQL->query("select * from sensortype where st_id=\"".$SQL->escape($i)."\"");
	$fe=$qe->obj();
	if(!$fe) return false;
	$ret=new $fe->st_class();
	$ret->fe=$fe;
	return $ret;
    }

    abstract function showform($nw);
    abstract function checkform(&$rerr);
    abstract function newsensor($meas,$mod);
    abstract function editsensor($id,$meas,$mod);
    
    abstract function addtempform($sfe);
    
    abstract function canimport();
    
    abstract function imprequire();
    
    abstract function cron($sfe);
    
    abstract function attach($sfe);
}
