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
	if($_SESSION->variable_sort==$ARGV[1]) $_SESSION->variable_sortmode=!$_SESSION->variable_sortmode;
	$_SESSION->variable_sort=$ARGV[1];
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
switch($_SESSION->variable_sort) {
case "desc":
    $ord[]="var_desc ".($_SESSION->variable_sortmode?"desc":"asc");
    break;
case "code":
    $ord[]="var_code ".($_SESSION->variable_sortmode?"desc":"asc");
    break;
default:
    $_SESSION->variable_sort="unit";
}
$ord[]="var_unit ".($_SESSION->variable_sortmode?"desc":"asc");

echo "<form action=\"".root().$PAGE."\" method=\"post\">";

echo input_button("var_new","Nová veličina","newbutton");

$whr=array();
if($_SESSION->variable_filterenable) {
    echo "<fieldset><legend>Filtr</legend>";
    echo "<table class=\"nobr\">";
    echo "<tr><td>Jednotka:&nbsp;</td><td>".input_text("000_var_filter_unit",get_ind($_SESSION->variable_filter,"000_var_filter_unit"),"finput")."</td></tr>";
    echo "<tr><td>Popis:&nbsp;</td><td>".input_text("000_var_filter_desc",get_ind($_SESSION->variable_filter,"000_var_filter_desc"),"finput")."</td></tr>";
    echo "</table>";

    echo input_button("var_fapply","Použít")." ".input_button("var_fall","Zobrazit vše");
    echo "</fieldset>";
    
    $ftmp=get_ind($_SESSION->variable_filter,"000_var_filter_desc");
    if($ftmp) $whr[]="var_desc like \"%".$SQL->escape($ftmp)."%\"";
    $ftmp=get_ind($_SESSION->variable_filter,"000_var_filter_unit");
    if($ftmp) $whr[]="var_unit like \"%".$SQL->escape($ftmp)."%\"";
}

if($makecsv) {
    ob_clean();
    $_NOHEAD=true;
//    header("Content-type: text/plain");
    header("Content-type: text/x-csv");
    header("Content-Disposition: attachment; filename=".$PAGE.".csv");
    
    ob_start();
    echo csvline(array("#","Jednotky","Kód","Popis","Škála","Škála derivace","Barva","Implicitní","Pořadí zleva"));
    $qe=$SQL->query("select * from variable ".(count($whr)?"where ".implode(" && ",$whr):"")." order by ".implode(",",$ord));
    while($fe=$qe->obj()) {
	$vdat=unserialize($fe->var_plotdata);
	$vmin=get_ind($vdat,"min");
	$vmax=get_ind($vdat,"max");
	if($vmin===false || $vmax===false) $scale="nezadáno";
	else $scale=sprintf("%.2f .. %.2f",$vmin,$vmax);

	$vmin=get_ind($vdat,"dmin");
	$vmax=get_ind($vdat,"dmax");
	if($vmin===false || $vmax===false) $dscale="nezadáno";
	else $dscale=sprintf("%.2f .. %.2f",$vmin,$vmax);

	$color=get_ind($vdat,"color");
	if(!$color) $color="auto";
	echo csvline(array($fe->var_id,$fe->var_code,$fe->var_desc,$scale,$dscale,$color,$fe->var_default=='Y'?"ano":"ne",$fe->var_left));
    }
    $csv=ob_get_contents();
    ob_end_clean();
    echo csvoutput($csv);
    
    exit();
}

echo "<table>";
sortlocalref(array(
    array('n'=>"#",'a'=>false),
    array('n'=>"Jednotky",'a'=>"unit"),
    array('n'=>"Kód",'a'=>"code"),
    array('n'=>"Popis",'a'=>"desc"),
    array('n'=>"Škála",'a'=>false),
    array('n'=>"Škála derivace",'a'=>false),
    array('n'=>"Barva",'a'=>false),
    array('n'=>"Implicitní",'a'=>false),
    array('n'=>"Pořadí zleva",'a'=>false),
    array('n'=>input_button("var_filter","Filtr"),'a'=>false)
),$_SESSION->variable_sort,$_SESSION->variable_sortmode);

$qe=$SQL->query("select * from variable ".(count($whr)?"where ".implode(" && ",$whr):"")." order by ".implode(",",$ord));
while($fe=$qe->obj()) {
    $vdat=unserialize($fe->var_plotdata);
    $vmin=get_ind($vdat,"min");
    $vmax=get_ind($vdat,"max");
    if($vmin===false || $vmax===false) $scale="nezadáno";
    else $scale=sprintf("%.2f .. %.2f",$vmin,$vmax);

    $vmin=get_ind($vdat,"dmin");
    $vmax=get_ind($vdat,"dmax");
    if($vmin===false || $vmax===false) $dscale="nezadáno";
    else $dscale=sprintf("%.2f .. %.2f",$vmin,$vmax);

    $color=get_ind($vdat,"color");
    if(!$color) $color="auto";
    else $color="<span style=\"color:".$color."\">".$color."</span>";
    echo "<tr><td>".$fe->var_id."</td><td>".htmlspecialchars($fe->var_unit)."</td>
	<td>".htmlspecialchars($fe->var_code)."</td>
	<td>".htmlspecialchars($fe->var_desc)."</td>
	<td>".$scale."</td>
	<td>".$dscale."</td>
	<td>".$color."</td>
	<td>".input_check("var_def[".$fe->var_id."]",'Y',$fe->var_default=='Y',false,true)."</td>
	<td>".htmlspecialchars($fe->var_left)."</td>
	<td>".input_button("var_edit[".$fe->var_id."]","Editovat")."</td></tr>";
}

echo "</table>";

echo "<br /><a href=\"".root().$PAGE."/csv\">Uložit jako csv</a>";

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

    if(get_ind($_POST,"var_new")) {
	redir(root()."variabletab/edit/0");
    }
    if(get_ind($_POST,"var_edit")) {
	if(is_array($_POST['var_edit'])) {
	    $variableedit=(int)key($_POST['var_edit']);
	    if($variableedit) {
		$qe=$SQL->query("select * from variable where var_id=".$variableedit);
		$fe=$qe->obj();
		if(!$fe) {
		    $_SESSION->error_text="Veličina nenalezena";
		    redir();
		} else {
		    $vdat=unserialize($fe->var_plotdata);
		    $_SESSION->temp_form=array(
			"001_var_desc"=>$fe->var_desc,
			"001_var_unit"=>$fe->var_unit,
			"000_var_code"=>$fe->var_code,
			"005_var_min"=>get_ind($vdat,"min"),
			"005_var_max"=>get_ind($vdat,"max"),
			"005_var_dmin"=>get_ind($vdat,"dmin"),
			"005_var_dmax"=>get_ind($vdat,"dmax"),
			"000_var_color"=>get_ind($vdat,"color"),
			"000_var_default"=>$fe->var_default,
			"000_var_left"=>$fe->var_left
		    );
		    redir(root()."variabletab/edit/".$variableedit);
		}
	    }
	}
	redir();
    }
    if(get_ind($_POST,"var_filter")) {
	$_SESSION->variable_filterenable=!$_SESSION->variable_filterenable;
	redir();
    }
    if(get_ind($_POST,"var_fall")) {
	$_SESSION->variable_filter=false;
	redir();
    }
    if(get_ind($_POST,"var_fapply")) {
	postcheck($ITEMS,$_POST);
	$_SESSION->variable_filter=$_POST;
	redir();
    }
    redir();
}
