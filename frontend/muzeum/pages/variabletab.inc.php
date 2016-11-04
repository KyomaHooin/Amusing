<?php

pageperm();
showmenu();

showerror();

ajaxsess();

if($ARGC!=2) redir(root()."variables");
if($ARGV[0]!="edit" || !is_numeric($ARGV[1])) redir(root()."variables");
$variableedit=(int)$ARGV[1];
if($variableedit) {
    $qe=$SQL->query("select * from variable where var_id=".$variableedit);
    $fe=$qe->obj();
    if(!$fe) redir(root()."variables");
}

echo "<form action=\"".root().$PAGE."/edit/".$variableedit."\" method=\"post\" enctype=\"multipart/form-data\">";

echo "<fieldset><legend>".($variableedit?"Editace veličiny":"Nová veličina")."</legend>";
echo "<table class=\"nobr\">";
echo "<tr><td>Jednotka:&nbsp;</td><td>".input_text_temp_err("001_var_unit")."</td></tr>";

if($variableedit) {
    $opts=array($fe->var_code=>$fe->var_code);
} else {
    $opts=array(0=>"Zvolte kód veličiny");
    
    $notin=array();
    $qe=$SQL->query("select * from variable");
    while($fe=$qe->obj()) $notin[]="\"".$SQL->escape($fe->var_code)."\"";
    if(count($notin)) $qe=$SQL->query("select * from varcodes where vc_text not in (".implode(",",$notin).") order by vc_text");
    else $qe=$SQL->query("select * from varcodes order by vc_text");
    if(!$qe->rowcount()) {
	$_SESSION->error_text="Není k dispozici nepoužitý kód veličiny";
	redir(root()."variables");
    }
    while($fe=$qe->obj()) $opts[$fe->vc_text]=$fe->vc_text;
}
echo "<tr><td>Kód:&nbsp;</td><td>".input_select_temp_err("000_var_code",$opts)."</td></tr>";

echo "<tr><td>Popis:&nbsp;</td><td>".input_text_temp_err("001_var_desc")."</td></tr>";
echo "<tr><td>Minimum:&nbsp;</td><td>".input_text_temp_err("005_var_min")."</td></tr>";
echo "<tr><td>Maximum:&nbsp;</td><td>".input_text_temp_err("005_var_max")."</td></tr>";
echo "<tr><td>Derivace min.:&nbsp;</td><td>".input_text_temp_err("005_var_dmin")."</td></tr>";
echo "<tr><td>Derivace max.:&nbsp;</td><td>".input_text_temp_err("005_var_dmax")."</td></tr>";
echo "<tr><td>Barva:&nbsp;</td><td>".input_text_temp_err("000_var_color")." (#RRGGBB)</td></tr>";
echo "<tr><td>Implicitní:&nbsp;</td><td>".input_check_temp_err("000_var_default")."</td></tr>";
echo "<tr><td>Pořadí zleva:&nbsp;</td><td>".input_select_temp("000_var_left",array(0,1,2,3,4,5,6,7,8,9))."</td></tr>";
echo "</table>";

echo input_button("var_save","Uložit")." ".input_button("var_cancel","Storno");
echo "</fieldset>";

echo "<script type=\"text/javascript\">
// <![CDATA[
function varsgui() {
    $(\"button\").button();
";
if($variableedit) echo "$(\"#000_var_code\").prop(\"disabled\",true)";
echo "
}
// ]]>
</script>";
    $_JQUERY[]="varsgui();";

echo "</form>";

function sredir() {
    global $PAGE;
    global $variableedit;
    redir(root().$PAGE."/edit/".$variableedit);
}

if($_SERVER['REQUEST_METHOD']=="POST") {
    $_SESSION->invalid=false;
    $_SESSION->temp_form=false;

    if(get_ind($_POST,"var_cancel")) {
	redir(root()."variables");
    }
    if(get_ind($_POST,"var_save")) {
	$rerr=postcheck($ITEMS,$_POST);
	$vmin=get_ind($_POST,"005_var_min");
	$vmax=get_ind($_POST,"005_var_max");
	if(is_numeric($vmin) && !is_numeric($vmax)) $rerr['005_var_max']="Nezadána druhá mez";
	if(!is_numeric($vmin) && is_numeric($vmax)) $rerr['005_var_max']="Nezadána první mez";

	$dvmin=get_ind($_POST,"005_var_dmin");
	$dvmax=get_ind($_POST,"005_var_dmax");
	if(is_numeric($dvmin) && !is_numeric($dvmax)) $rerr['005_var_dmax']="Nezadána druhá mez";
	if(!is_numeric($dvmin) && is_numeric($dvmax)) $rerr['005_var_dmax']="Nezadána první mez";

	$color=get_ind($_POST,"000_var_color");
	if(!strlen($color)) $color=false;
	else if(!preg_match("/^\\#[0-9A-F]{6}$/",$color)) $rerr['000_var_color']="Neplatně zadaná barva";
	
	$vc=get_ind($_POST,"000_var_code");
	if(!$vc && !$variableedit) $rerr['000_var_code']="Nezvolen kód veličiny";
	
	if(count($rerr)) {
	    $_SESSION->error_text=reset($rerr);
	    $_SESSION->invalid=$rerr;
	    $_SESSION->temp_form=$_POST;
	    sredir();
	}
	if(!is_numeric($vmin)) $vmin=false;
	if(!is_numeric($vmax)) $vmax=false;
	if(!is_numeric($dvmin)) $dvmin=false;
	if(!is_numeric($dvmax)) $dvmax=false;
	$pdata=serialize(array("max"=>$vmax,"min"=>$vmin,"color"=>$color,"dmax"=>$dvmax,"dmin"=>$dvmin));
	$vdef=(get_ind($_POST,"000_var_default")=='Y');
	$vleft=get_ind($_POST,"000_var_left");
	switch($vleft) {
	case 1:
	case 2:
	case 3:
	case 4:
	case 5:
	case 6:
	case 7:
	case 8:
	case 9:
	    break;
	default:
	    $vleft=0;
	}
	if(!$variableedit) {
	    $SQL->query("insert into variable set
		var_unit=\"".$SQL->escape(get_ind($_POST,"001_var_unit"))."\",
		var_code=\"".$SQL->escape($vc)."\",
		var_desc=\"".$SQL->escape(get_ind($_POST,"001_var_desc"))."\",
		var_plotdata=\"".$SQL->escape($pdata)."\",
		var_left=\"".$vleft."\",
		var_default=\"".($vdef?"Y":"N")."\"");
	    switch($SQL->errnum) {
	    case 0:
		break;
	    case 1062:
		$rerr['000_var_code']="Duplikátní kód veličiny";
		$_SESSION->error_text=reset($rerr);
		$_SESSION->invalid=$rerr;
		$_SESSION->temp_form=$_POST;
		sredir();
	    default:
		$_SESSION->error_text="Chyba databáze";
		redir(root()."variables");
	    }
	} else {
	    $SQL->query("update variable set
		var_unit=\"".$SQL->escape(get_ind($_POST,"001_var_unit"))."\",
		var_desc=\"".$SQL->escape(get_ind($_POST,"001_var_desc"))."\",
		var_plotdata=\"".$SQL->escape($pdata)."\",
		var_left=\"".$vleft."\",
		var_default=\"".($vdef?"Y":"N")."\"
		where var_id=".$variableedit);
	    switch($SQL->errnum) {
	    case 0:
		break;
	    default:
		$_SESSION->error_text="Chyba databáze";
		redir(root()."variables");
	    }
	}
	$qe=$SQL->query("select count(*) as cnt from variable where var_default='Y'");
	$fe=$qe->obj();
	if($fe->cnt>2) $_SESSION->error_text="Veličina uložena, více než dvě veličiny jsou implicitní";
	else $_SESSION->error_text="Veličina uložena";
	redir(root()."variables");
    }
    sredir();
}
