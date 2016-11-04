<?php

require_once "core/db_config.inc.php";

$SQL_show_errors=0;

class c_sql {
    var $handle;
    var $host;
    var $user;
    var $pass;
    var $dbname;
    var $err;
    var $errnum;
    
    function __construct($host=false,$user=false,$pass=false,$db=false,$se=0) {
	$this->handle=false;
	$this->show_errors=$se;
	$this->host=($host===false?_SQL_HOST:$host);
	$this->user=($user===false?_SQL_USER:$user);
	$this->pass=($pass===false?_SQL_PASS:$pass);
	$this->dbname=($db===false?_SQL_DBNAME:$db);
    }
    
    function __destruct() {
	if($this->handle) @mysqli_close($this->handle);
    }
    
    function connect($nc=false) {
	global $SQL_show_errors;
    
	if(!$this->handle) {
	    $this->handle=@mysqli_connect($this->host,$this->user,$this->pass,$this->dbname);
	    if(!$this->handle) {
		if($SQL_show_errors) echo "<br /><pre>SQL cannot connect</pre><br />";
		else error_log("SQL cannot connect");
		return false;
	    }
	    if(!mysqli_query($this->handle,"set names utf8")) {
		@mysqli_close($this->handle);
		$this->handle=false;
		if($SQL_show_errors) echo "<br /><pre>SQL cannot set charset</pre><br />";
		else error_log("SQL cannot set charset");
		return false;
	    }
	}
	return true;
    }
    
    function query($query,$view=false) {
	global $SQL_show_errors;
    
	if(!$this->connect()) return new c_sqlres();

	if($view) echo "<br /><pre>SQL: ".$query."</pre><br />";
	$res=@mysqli_query($this->handle,$query);
	$this->errnum=mysqli_errno($this->handle);
	if($this->errnum) {
	    $this->err=mysqli_error($this->handle);
	    if($SQL_show_errors) {
		cleanout();
		echo "<br /><pre>".$query."<br /><font color=\"#FF0000\"><b>".$this->err."</b></font><br />";
		exit();
	    } else {
		if($this->errnum!=1062) {
		    error_log($query);
		    error_log($this->err);
		}
	    }
	    return new c_sqlres();
	} else $this->err=false;
	if(is_object($res)) return new c_sqlres($res);
	return new c_sqlres();
    }

    function buffered($query,$view=false) {
	global $SQL_show_errors;
    
	if(!$this->connect()) return new c_sqlres();

	if($view) echo "<br /><pre>SQL: ".$query."</pre><br />";
	$res=@mysqli_query($this->handle,$query,MYSQLI_USE_RESULT);
	$this->errnum=mysqli_errno($this->handle);
	if($this->errnum) {
	    $this->err=mysqli_error($this->handle);
	    if($SQL_show_errors) {
		cleanout();
		echo "<br /><pre>".$query."<br /><font color=\"#FF0000\"><b>".$this->err."</b></font><br />";
		exit();
	    } else {
		if($this->errnum!=1062) {
		    error_log($query);
		    error_log($this->err);
		}
	    }
	    return new c_sqlres();
	} else $this->err=false;
	if(is_object($res)) return new c_sqlres($res);
	return new c_sqlres();
    }
    
    function lastid() {
	return mysqli_insert_id($this->handle);
    }
    
    function affected() {
	return mysqli_affected_rows($this->handle);
    }
    
    function info() {
	return mysqli_info($this->handle);
    }
    
    function escape($str) {
	if(!$this->connect()) return false;
	return mysqli_real_escape_string($this->handle,$str);
    }
    
    function unlock() {
	if(!$this->connect()) return false;
	return @mysqli_query($this->handle,"unlock tables");
    }
}
