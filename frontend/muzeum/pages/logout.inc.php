<?php

if(!$_SESSION->user) redir(root()."login");
//logtext("odhlášen");

function delalltemps() {
    global $_PLOTDIR;
    global $_CSVDIR;

    if(!is_array($_SESSION->plot_outputs)) $_SESSION->plot_outputs=array();
    if(!is_array($_SESSION->csv_outputs)) $_SESSION->csv_outputs=array();
    
    foreach($_SESSION->plot_outputs as $key=>$val) {
	@unlink($_PLOTDIR."/".$key);
    }
    foreach($_SESSION->csv_outputs as $key=>$val) {
	@unlink($_CSVDIR."/".$key);
    }
    
    if(!is_array($_SESSION->imgsliders)) $_SESSION->imgsliders=array();
    foreach($_SESSION->imgsliders as $key=>$val) {
	@unlink($_PLOTDIR."/".$key);
    }
}

delalltemps();
$_SESSION->clear();
$_SESSION->error_text="Byli jste odhlášeni";

redir(root()."login");
