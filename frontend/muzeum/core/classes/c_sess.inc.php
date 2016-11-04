<?php

class c_sess {
    var $lasttime;
    var $error_text;

    var $temp_form;
    var $invalid;
    
    var $user;
    var $userpref;
    var $somegraphdone;
    
    var $log_currpage;
    
    var $user_sort;
    var $user_sortmode;
    var $user_filter;
    var $user_filterenable;

    var $building_sort;
    var $building_sortmode;
    var $building_filter;
    var $building_filterenable;
    var $building_currpage;
    
    var $room_sort;
    var $room_sortmode;
    var $room_filter;
    var $room_filterenable;
    var $room_currpage;
    
    var $material_sort;
    var $material_sortmode;
    var $material_filter;
    var $material_filterenable;
    
    var $measpoint_sort;
    var $measpoint_sortmode;
    var $measpoint_filter;
    var $measpoint_filterenable;
    var $measpoint_currpage;
    
    var $sensor_sort;
    var $sensor_sortmode;
    var $sensor_filter;
    var $sensor_filterenable;
    var $sensor_currpage;
    
    var $sensormodel_sort;
    var $sensormodel_sortmode;
    var $sensormodel_filter;
    var $sensormodel_filterenable;
    
    var $variable_sort;
    var $variable_sortmode;
    var $variable_filter;
    var $variable_filterenable;
    
    var $varcode_sort;
    var $varcode_sortmode;
    
    var $alarms_sort;
    var $alarms_sortmode;
    var $alarms_filter;
    var $alarms_filterenable;
    var $alarms_currpage;
    
    var $alarms_midskey;
    var $alarms_midsdata;

    var $alarmslog_sort;
    var $alarmslog_sortmode;
    var $alarmslog_filter;
    var $alarmslog_filterenable;
    var $alarmslog_currpage;

    var $alarmsack_sort;
    var $alarmsack_sortmode;
    var $alarmsack_filter;
    var $alarmsack_filterenable;
    var $alarmsack_currpage;
    
    var $comments_sort;
    var $comments_sortmode;
    var $comments_filter;
    var $comments_filterenable;
    var $comments_currpage;

    var $prevpage;
    
    var $mainform;
    var $maingraph;
    
    var $plot_outputs;
    var $csv_outputs;
    
    var $datatogen_mids;
    
    var $imgsliders;
    
    var $measids;
    
    function __construct() {
	foreach($this as $key=>$val) $this->$key=false;
    }
    
    function clear() {
	foreach($this as $key=>$val) $this->$key=false;
    }
    
    function getprofiles() {
	$prf=get_ind($this->userpref,"profiles");
	if(!is_array($prf)) return array();
	return $prf;
    }
    function getdefaultprofile() {
	$prf=get_ind($this->userpref,"defprof");
	if(!is_array($prf)) return false;
	return $prf;
    }
    function setprofiles($prf) {
	if(!is_array($this->userpref)) $this->userpref=array();
	$this->userpref["profiles"]=$prf;
	saveuserpref();
    }
    function updateprofile() {
	$curr=get_ind($this->mainform,'main_uprfsel');
	if($curr) {
	    $curr=my_hex2bin($curr);
	    $prf=$this->getprofiles();
	    if(get_ind($prf,$curr)) {
		$prf[$curr]['mids']=$this->datatogen_mids;
		$this->setprofiles($prf);
		return;
	    }
	}
// set default profile
	if(!is_array($this->userpref)) $this->userpref=array();
	$this->userpref["defprof"]=array("mids"=>$this->datatogen_mids);
	saveuserpref();
    }
    function updatedefprofile() {
	if(!is_array($this->userpref)) $this->userpref=array();
	$this->userpref["defprof"]=array("mids"=>$this->datatogen_mids);
	saveuserpref();
    }
};
