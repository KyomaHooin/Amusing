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
	if($_SESSION->sensormodel_sort==$ARGV[1]) $_SESSION->sensormodel_sortmode=!$_SESSION->sensormodel_sortmode;
	$_SESSION->sensormodel_sort=$ARGV[1];
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
switch($_SESSION->sensormodel_sort) {
case "name":
    $ord[]="sm_name ".($_SESSION->sensormodel_sortmode?"desc":"asc");
    $ord[]="sm_vendor ".($_SESSION->sensormodel_sortmode?"desc":"asc");
    break;
default:
    $_SESSION->sensormodel_sort="vendor";
    $ord[]="sm_vendor ".($_SESSION->sensormodel_sortmode?"desc":"asc");
    $ord[]="sm_name ".($_SESSION->sensormodel_sortmode?"desc":"asc");
}

echo "<form id=\"smform\" action=\"".root().$PAGE."\" method=\"post\">";

echo input_button("sm_new","Nový model","newbutton");

$whr=array();
if($_SESSION->sensormodel_filterenable) {
    echo "<fieldset><legend>Filtr</legend>";
    echo "<table class=\"nobr\">";
    
    echo "<tr><td>Název:&nbsp;</td><td>".input_text("000_sm_filter_name",get_ind($_SESSION->sensormodel_filter,"000_sm_filter_name"),"finput")."</td></tr>";
    echo "<tr><td>Výrobce:&nbsp;</td><td>".input_text("000_sm_filter_vendor",get_ind($_SESSION->sensormodel_filter,"000_sm_filter_vendor"),"finput")."</td></tr>";
    echo "<tr><td>Poznámka:&nbsp;</td><td>".input_text("000_sm_filter_note",get_ind($_SESSION->sensormodel_filter,"000_sm_filter_note"),"finput")."</td></tr>";

    echo "</table>";

    echo input_button("sm_fapply","Použít")." ".input_button("sm_fall","Zobrazit vše");
    echo "</fieldset>";
    
    $ftmp=get_ind($_SESSION->sensormodel_filter,"000_sm_filter_name");
    if($ftmp) $whr[]="sm_name like \"%".$SQL->escape($ftmp)."%\"";
    $ftmp=get_ind($_SESSION->sensormodel_filter,"000_sm_filter_vendor");
    if($ftmp) $whr[]="sm_vendor like \"%".$SQL->escape($ftmp)."%\"";
    $ftmp=get_ind($_SESSION->sensormodel_filter,"000_sm_filter_note");
    if($ftmp) $whr[]="sm_note like \"%".$SQL->escape($ftmp)."%\"";
}

if($makecsv) {
    ob_clean();
    $_NOHEAD=true;
//    header("Content-type: text/plain");
    header("Content-type: text/x-csv");
    header("Content-Disposition: attachment; filename=".$PAGE.".csv");
    
    ob_start();
    echo csvline(array("#","Název","Výrobce","Poznámka"));
    $qe=$SQL->query("select * from sensormodel ".(count($whr)?"where ".implode(" && ",$whr):"")." order by ".implode(",",$ord));
    while($fe=$qe->obj()) {
	echo csvline(array($fe->sm_id,$fe->sm_name,$fe->sm_vendor,$fe->sm_note));
    }
    $csv=ob_get_contents();
    ob_end_clean();
    echo csvoutput($csv);
    
    exit();
}

echo "<table>";
sortlocalref(array(
    array('n'=>"#",'a'=>false),
    array('n'=>"Název",'a'=>"name"),
    array('n'=>"Výrobce",'a'=>"vendor"),
    array('n'=>"Poznámka",'a'=>false),
    array('n'=>input_button("sm_filter","Filtr"),'a'=>false)
),$_SESSION->sensormodel_sort,$_SESSION->sensormodel_sortmode);

$qe=$SQL->query("select * from sensormodel ".(count($whr)?"where ".implode(" && ",$whr):"")." order by ".implode(",",$ord));
while($fe=$qe->obj()) {
    echo "<tr>
	<td>".$fe->sm_id."</td>
	<td>".htmlspecialchars($fe->sm_name)."</td>
	<td>".htmlspecialchars($fe->sm_vendor)."</td>
	<td>".htmlspecialchars($fe->sm_note)."</td>";
    echo "<td>".input_button("sm_edit[".$fe->sm_id."]","Editovat")."</td>";
    echo "</tr>";
}

echo "</table>";

echo "<br /><a href=\"".root().$PAGE."/csv\">Uložit jako csv</a>";

echo "<script type=\"text/javascript\">
// <![CDATA[
function smgui() {
    $(\"button\").button();
}
// ]]>
</script>";
    $_JQUERY[]="smgui();";

echo "</form>";

if($_SERVER['REQUEST_METHOD']=="POST") {
    $_SESSION->invalid=false;
    $_SESSION->temp_form=false;

    if(get_ind($_POST,"sm_new")) {
	redir(root()."sensormodeltab/edit/0");
    }
    if(get_ind($_POST,"sm_edit")) {
	if(is_array($_POST['sm_edit'])) {
	    $smedit=(int)key($_POST['sm_edit']);
	    if($smedit) {
		$qe=$SQL->query("select * from sensormodel where sm_id=".$smedit);
		$fe=$qe->obj();
		if(!$fe) {
		    $_SESSION->error_text="Model nenalezen";
		    redir();
		} else {
		    $_SESSION->temp_form=array(
			"001_sm_name"=>$fe->sm_name,
			"001_sm_vendor"=>$fe->sm_vendor,
			"000_sm_note"=>$fe->sm_note
		    );
		    redir(root()."sensormodeltab/edit/".$smedit);
		}
	    }
	}
	redir();
    }
    if(get_ind($_POST,"sm_filter")) {
	$_SESSION->sensormodel_filterenable=!$_SESSION->sensormodel_filterenable;
	redir();
    }
    if(get_ind($_POST,"sm_fall")) {
	$_SESSION->sensormodel_filter=false;
	redir();
    }
    if(get_ind($_POST,"sm_fapply")) {
	postcheck($ITEMS,$_POST);
	$_SESSION->sensormodel_filter=$_POST;
	redir();
    }
    redir();
}
