<?php

pageperm();

echo "<script type=\"text/javascript\">
// <![CDATA[
function printgui() {
    $(\"#divheader\").hide();
    $(\"#divfooter\").hide();
}
// ]]>
</script>";
$_JQUERY[]="printgui();";

require_once __DIR__."/../inc/header_print.inc.php";

$prn=get_ind($_SESSION->maingraph,'graph');
$st=false;

foreach(explode("\n",$prn) as $ln) {
    if($st) {
	if(trim($ln)=="<!-- noprint_} -->") $st=false;
    } else {
	if(trim($ln)=="<!-- noprint_{ -->") $st=true;
	else echo $ln;
    }
}

require_once __DIR__."/../inc/footer_print.inc.php";

if($_SERVER['REQUEST_METHOD']=="POST") {
    $_SESSION->invalid=false;
    $_SESSION->temp_form=false;
    redir(root()."main");
}
