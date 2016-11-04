<?php

require_once "core/db_config.inc.php";

class c_sqlres {
    var $result;
    
    function __construct($res=false) {
	$this->result=$res;
    }
    
    function __destruct() {
	$this->free();
    }

    function obj() {
	if($this->result===false) return false;
	return mysqli_fetch_object($this->result);
    }
    
    function assoc() {
	if($this->result===false) return false;
	return mysqli_fetch_assoc($this->result);
    }
    
    function row() {
	if($this->result===false) return false;
	return mysqli_fetch_row($this->result);
    }
    
    function rowcount() {
	if($this->result===false) return false;
	return mysqli_num_rows($this->result);
    }
    
    function seek($i) {
	if($this->result===false) return false;
	return mysqli_data_seek($this->result,$i);
    }
    
    function free() {
	if($this->result) {
	    mysqli_free_result($this->result);
	    $this->result=false;
	}
    }
}
