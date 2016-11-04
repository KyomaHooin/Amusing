<?php

pageperm();
showmenu();

showerror();

ajaxsess();

switch($ARGC) {
case 2:
    switch($ARGV[0]) {
    case "sort":
	if($_SESSION->building_sort==$ARGV[1]) $_SESSION->building_sortmode=!$_SESSION->building_sortmode;
	$_SESSION->building_sort=$ARGV[1];
	redir();
    case "page":
	$_SESSION->building_currpage=(int)$ARGV[1];
	redir();
    }
    break;
}

$ord=array();
switch($_SESSION->building_sort) {
case "street":
    $ord[]="b_street ".($_SESSION->building_sortmode?"desc":"asc");
    break;
case "city":
    $ord[]="b_city ".($_SESSION->building_sortmode?"desc":"asc");
    break;
case "desc":
    $ord[]="b_desc ".($_SESSION->building_sortmode?"desc":"asc");
    break;
default:
    $_SESSION->building_sort="name";
}
$ord[]="b_name ".($_SESSION->building_sortmode?"desc":"asc");

echo "<form action=\"".root().$PAGE."\" method=\"post\">";

echo input_button("build_new","Nová budova","newbutton");

$whr=array();
if($_SESSION->building_filterenable) {
    echo "<fieldset><legend>Filtr</legend>";
    echo "<table class=\"nobr\">";

    $opts=array(0=>"Všechna města");
    $qe=$SQL->query("select * from building group by b_city order by b_city");
    while($fe=$qe->obj()) {
	$opts[bin2hex($fe->b_city)]=$fe->b_city;
    }
    $sb=get_ind($_SESSION->building_filter,"000_build_filter_city");
    echo "<tr><td>Město:&nbsp;</td><td>".input_select("000_build_filter_city",$opts,$sb)."</td></tr>";

    echo "<tr><td>Název:&nbsp;</td><td>".input_text("000_build_filter_name",get_ind($_SESSION->building_filter,"000_build_filter_name"),"finput")."</td></tr>";
    echo "<tr><td>Ulice:&nbsp;</td><td>".input_text("000_build_filter_street",get_ind($_SESSION->building_filter,"000_build_filter_street"),"finput")."</td></tr>";
//    echo "<tr><td>Město:&nbsp;</td><td>".input_text("000_build_filter_city",get_ind($_SESSION->building_filter,"000_build_filter_city"),"finput")."</td></tr>";
    echo "</table>";
    echo input_button("build_fapply","Použít")." ".input_button("build_fall","Zobrazit vše");
    echo "</fieldset>";
    
    $ftmp=get_ind($_SESSION->building_filter,"000_build_filter_name");
    if($ftmp) $whr[]="b_name like \"%".$SQL->escape($ftmp)."%\"";
    $ftmp=get_ind($_SESSION->building_filter,"000_build_filter_street");
    if($ftmp) $whr[]="b_street like \"%".$SQL->escape($ftmp)."%\"";
//    $ftmp=get_ind($_SESSION->building_filter,"000_build_filter_city");
//    if($ftmp) $whr[]="b_city like \"%".$SQL->escape($ftmp)."%\"";
    $ftmp=get_ind($_SESSION->building_filter,"000_build_filter_city");
    if($ftmp) $whr[]="b_city=\"".$SQL->escape(hex2bin($ftmp))."\"";
}

ob_start();
echo "<table id=\"buildtable\">";
sortlocalref(array(
    array('n'=>"#",'a'=>false),
    array('n'=>"Název",'a'=>"name"),
    array('n'=>"Ulice",'a'=>"street"),
    array('n'=>"Město",'a'=>"city"),
    array('n'=>"GPS",'a'=>false),
    array('n'=>"Popis",'a'=>"desc"),
    array('n'=>"Url",'a'=>false),
    array('n'=>"&nbsp;",'a'=>false),
    array('n'=>input_button("build_filter","Filtr"),'a'=>false)
),$_SESSION->building_sort,$_SESSION->building_sortmode);

$offset=(int)($_SESSION->building_currpage*$_PERPAGE);
$limit=(int)$_PERPAGE;
$qe=$SQL->query("select SQL_CALC_FOUND_ROWS * from building ".(count($whr)?"where ".implode(" && ",$whr):"")." order by ".implode(",",$ord)." limit ".$offset.",".$limit);
while($fe=$qe->obj()) {
    echo "<tr><td>".$fe->b_id."</td>
	<td>".htmlspecialchars($fe->b_name)."</td>
	<td>".htmlspecialchars($fe->b_street)."</td>
	<td>".htmlspecialchars($fe->b_city)."</td>
	<td>".(strlen($fe->b_gps)?"<a target=\"_blank\" href=\"https://www.google.cz/maps/place/".htmlspecialchars($fe->b_gps)."\">".htmlspecialchars($fe->b_gps)."</a>":"")."</td>
	<td>".htmlspecialchars(strtr($fe->b_desc,"\n","<br />"))."</td>
	<td>".(strlen($fe->b_url)?"<a target=\"_blank\" href=\"".htmlspecialchars($fe->b_url)."\">".htmlspecialchars($fe->b_url)."</a>":"&nbsp;")."</td>
	<td>".($fe->b_img?"<a href=\"".root()."image/".$fe->b_img."\" target=\"_blank\"><img title=\"".$fe->b_img."\" src=\"".root()."image/".$fe->b_img."/max/100/100\" /></a>":"&nbsp;")."</td>
	<td>".input_button("build_edit[".$fe->b_id."]","Editovat")." ".input_button("build_acc[".$fe->b_id."]","Oprávnění")."</td></tr>";
}

echo "</table>";
$tbl=ob_get_clean();

$qe=$SQL->query("select FOUND_ROWS() as rows");
$fe=$qe->obj();
$totalrows=$fe->rows;
if($totalrows) pages($totalrows,$_SESSION->building_currpage,"<a href=\"".root().$PAGE."/page/%d\">%d</a>");
echo $tbl;
if($totalrows) pages($totalrows,$_SESSION->building_currpage,"<a href=\"".root().$PAGE."/page/%d\">%d</a>");

echo "<style>
.ui-tooltip {
    max-width: none;
}
</style>";
echo "<script type=\"text/javascript\">
// <![CDATA[
function buildingsgui() {
    $(\"button\").button();
    $(\"#buildtable img\").tooltip({
	content: function() {
	    return '<img src=\"".root()."image/'+$(this).attr('title')+'/max/500/500\" />';
	}
    });
    $(\".pagep a\").button();
    $(\".pagep b\").button({disabled:true});
}
// ]]>
</script>";
    $_JQUERY[]="buildingsgui();";

echo "</form>";

if($_SERVER['REQUEST_METHOD']=="POST") {
    $_SESSION->invalid=false;
    $_SESSION->temp_form=false;

    if(get_ind($_POST,"build_new")) {
	redir(root()."buildingtab/edit/0");
    }
    if(get_ind($_POST,"build_edit")) {
	if(is_array($_POST['build_edit'])) {
	    $bedit=(int)key($_POST['build_edit']);
	    if($bedit) {
		$qe=$SQL->query("select * from building where b_id=".$bedit);
		$fe=$qe->obj();
		if(!$fe) {
		    $_SESSION->error_text="Budova nenalezena";
		    redir();
		} else {
		    $_SESSION->temp_form=array(
			"001_build_name"=>$fe->b_name,
			"001_build_street"=>$fe->b_street,
			"001_build_city"=>$fe->b_city,
			"000_build_desc"=>$fe->b_desc,
			"000_build_gps"=>$fe->b_gps,
			"000_build_url"=>$fe->b_url
		    );
		    redir(root()."buildingtab/edit/".$bedit);
		}
	    }
	}
	redir();
    }
    if(get_ind($_POST,"build_acc")) {
	if(is_array($_POST['build_acc'])) {
	    $bacc=key($_POST['build_acc']);
	    redir(root()."buildingacc/edit/".$bacc);
	}
	redir();
    }
    if(get_ind($_POST,"build_filter")) {
	$_SESSION->building_filterenable=!$_SESSION->building_filterenable;
	if($_SESSION->building_filterenable) $_SESSION->building_currpage=0;
	redir();
    }
    if(get_ind($_POST,"build_fall")) {
	$_SESSION->building_filter=false;
	redir();
    }
    if(get_ind($_POST,"build_fapply")) {
	postcheck($ITEMS,$_POST);
	$_SESSION->building_filter=$_POST;
	$_SESSION->building_currpage=0;
	redir();
    }
    redir();
}
