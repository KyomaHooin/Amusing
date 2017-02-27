<?php

function sess_token() {
    $sesstoken="[removed]";
    return sha1($sesstoken.__FILE__);
}

function sess_test() {
    return get_ind($_SESSION,"c_token")==sess_token();
}

function sess_exit() {
    if(is_object($_SESSION)) $_SESSION=array("c_sess"=>$_SESSION,"c_token"=>sess_token());
    else $_SESSION=array();
    session_write_close();
}

session_start();
register_shutdown_function("sess_exit");

if(sess_test()) {
    $lsess=get_ind($_SESSION,"c_sess");
    if($lsess) $_SESSION=$_SESSION['c_sess'];
    else $_SESSION=new c_sess();
    unset($lsess);
} else {
    $_SESSION=new c_sess();
}

$_SESSION->lasttime=time();
