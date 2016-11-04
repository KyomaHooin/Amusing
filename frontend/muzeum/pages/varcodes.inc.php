<?php

pageperm();
showmenu();

showerror();

ajaxsess();

switch($ARGC) {
case 2:
    switch($ARGV[0]) {
    case "sort":
	if($_SESSION->varcode_sort==$ARGV[1]) $_SESSION->varcode_sortmode=!$_SESSION->varcode_sortmode;
	$_SESSION->varcode_sort=$ARGV[1];
	redir();
    }
    break;
}

$ord=array();
switch($_SESSION->varcode_sort) {
case "period":
    $ord[]="vc_expperiod ".($_SESSION->varcode_sortmode?"desc":"asc");
    break;
default:
    $_SESSION->varcode_sort="code";
}
$ord[]="vc_text ".($_SESSION->varcode_sortmode?"desc":"asc");

echo "<form action=\"".root().$PAGE."\" method=\"post\">";

echo input_button("vc_new","Nový kód veličiny","newbutton");

echo "<table>";
sortlocalref(array(
    array('n'=>"Kód",'a'=>"code"),
    array('n'=>"Perioda",'a'=>"period"),
    array('n'=>"Binární",'a'=>false),
    array('n'=>"&nbsp;",'a'=>false)
),$_SESSION->varcode_sort,$_SESSION->varcode_sortmode);

$qe=$SQL->query("select * from varcodes order by ".implode(",",$ord));
while($fe=$qe->obj()) {
    echo "<tr><td>".htmlspecialchars($fe->vc_text)."</td>
	<td>".htmlspecialchars($fe->vc_expperiod)."</td>
	<td>".htmlspecialchars($fe->vc_bin)."</td>
	<td>".input_button("vc_edit[".bin2hex($fe->vc_text)."]","Editovat")."</td></tr>";
}

echo "</table>";

echo "<script type=\"text/javascript\">
// <![CDATA[
function varsgui() {
    $(\"button\").button();
}
// ]]>
</script>";
    $_JQUERY[]="varsgui();";

echo "</form>";

if($_SERVER['REQUEST_METHOD']=="POST") {
    $_SESSION->invalid=false;
    $_SESSION->temp_form=false;

    if(get_ind($_POST,"vc_new")) {
	redir(root()."varcodetab/edit");
    }
    if(get_ind($_POST,"vc_edit")) {
	if(is_array($_POST['vc_edit'])) {
	    $varcodeedit=my_hex2bin(key($_POST['vc_edit']));
	    if($varcodeedit) {
		$qe=$SQL->query("select * from varcodes where vc_text=\"".$SQL->escape($varcodeedit)."\"");
		$fe=$qe->obj();
		if(!$fe) {
		    $_SESSION->error_text="Kód veličiny nenalezen";
		    redir();
		} else {
		    $_SESSION->temp_form=array(
			"000_vc_text"=>$fe->vc_text,
			"002_vc_period"=>$fe->vc_expperiod,
			"000_vc_bin"=>$fe->vc_bin
		    );
		    redir(root()."varcodetab/edit/".$varcodeedit);
		}
	    }
	}
	redir();
    }
    redir();
}
