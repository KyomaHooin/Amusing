<?php

pageperm();
showmenu();

showerror();

ajaxsess();

$makecsv=false;
switch($ARGC) {
case 2:
    switch($ARGV[0]) {
    case "sort":
	if($_SESSION->material_sort==$ARGV[1]) $_SESSION->material_sortmode=!$_SESSION->material_sortmode;
	$_SESSION->material_sort=$ARGV[1];
	redir();
    }
    break;
case 1:
    switch($ARGV[0]) {
    case "csv":
	$makecsv=true;
	break;
    }
}

$ord=array();
switch($_SESSION->material_sort) {
default:
    $_SESSION->material_sort="mat";
}
$ord[]="ma_desc ".($_SESSION->material_sortmode?"desc":"asc");

echo "<form action=\"".root().$PAGE."\" method=\"post\">";

echo input_button("mat_new","Nový materiál","newbutton");

$whr=array();
if($_SESSION->material_filterenable) {
    echo "<fieldset><legend>Filtr</legend>";
    echo "<table class=\"nobr\">";
    echo "<tr><td>Popis:&nbsp;</td><td>".input_text("000_mat_filter_desc",get_ind($_SESSION->material_filter,"000_mat_filter_desc"),"finput")."</td></tr>";
    echo "</table>";

    echo input_button("mat_fapply","Použít")." ".input_button("mat_fall","Zobrazit vše");
    echo "</fieldset>";
    
    $ftmp=get_ind($_SESSION->material_filter,"000_mat_filter_desc");
    if($ftmp) $whr[]="ma_desc like \"%".$SQL->escape($ftmp)."%\"";
}

if($makecsv) {
    ob_clean();
    $_NOHEAD=true;
//    header("Content-type: text/plain");
    header("Content-type: text/x-csv");
    header("Content-Disposition: attachment; filename=".$PAGE.".csv");
    
    ob_start();
    echo csvline(array("#","Materiál"));
    $qe=$SQL->query("select * from material ".(count($whr)?"where ".implode(" && ",$whr):"")." order by ".implode(",",$ord));
    while($fe=$qe->obj()) {
	echo csvline(array($fe->ma_id,$fe->ma_desc));
    }
    $csv=ob_get_contents();
    ob_end_clean();
    echo csvoutput($csv);
    
    exit();
}

echo "<table>";
sortlocalref(array(
    array('n'=>"#",'a'=>false),
    array('n'=>"Materiál",'a'=>"mat"),
    array('n'=>input_button("mat_filter","Filtr"),'a'=>false)
),$_SESSION->material_sort,$_SESSION->material_sortmode);

$qe=$SQL->query("select * from material ".(count($whr)?"where ".implode(" && ",$whr):"")." order by ".implode(",",$ord));
while($fe=$qe->obj()) {
    echo "<tr><td>".$fe->ma_id."</td><td>".htmlspecialchars($fe->ma_desc)."</td>
	<td>".input_button("mat_edit[".$fe->ma_id."]","Editovat")."</td></tr>";
}

echo "</table>";

echo "<br /><a href=\"".root().$PAGE."/csv\">Uložit jako csv</a>";

echo "<script type=\"text/javascript\">
// <![CDATA[
function materialsgui() {
    $(\"button\").button();
}
// ]]>
</script>";
    $_JQUERY[]="materialsgui();";

echo "</form>";

if($_SERVER['REQUEST_METHOD']=="POST") {
    $_SESSION->invalid=false;
    $_SESSION->temp_form=false;

    if(get_ind($_POST,"mat_new")) {
	redir(root()."materialtab/edit/0");
    }
    if(get_ind($_POST,"mat_edit")) {
	if(is_array($_POST['mat_edit'])) {
	    $materialedit=(int)key($_POST['mat_edit']);
	    if($materialedit) {
		$qe=$SQL->query("select * from material where ma_id=".$materialedit);
		$fe=$qe->obj();
		if(!$fe) {
		    $_SESSION->error_text="Materiál nenalezen";
		    redir();
		} else {
		    $_SESSION->temp_form=array(
			"001_mat_desc"=>$fe->ma_desc
		    );
		    redir(root()."materialtab/edit/".$materialedit);
		}
	    }
	}
	redir();
    }
    if(get_ind($_POST,"mat_filter")) {
	$_SESSION->material_filterenable=!$_SESSION->material_filterenable;
	redir();
    }
    if(get_ind($_POST,"mat_fall")) {
	$_SESSION->material_filter=false;
	redir();
    }
    if(get_ind($_POST,"mat_fapply")) {
	postcheck($ITEMS,$_POST);
	$_SESSION->material_filter=$_POST;
	redir();
    }
    redir();
}
