<?php

pageperm();
showmenu();

showerror();

ajaxsess();

switch($ARGC) {
case 2:
    switch($ARGV[0]) {
    case "sort":
	if($_SESSION->room_sort==$ARGV[1]) $_SESSION->room_sortmode=!$_SESSION->room_sortmode;
	$_SESSION->room_sort=$ARGV[1];
	redir();
    case "page":
	$_SESSION->room_currpage=(int)$ARGV[1];
	redir();
    }
    break;
}

$ord=array();
switch($_SESSION->room_sort) {
case "build":
    $ord[]="b_name ".($_SESSION->room_sortmode?"desc":"asc");
    $ord[]="r_floor ".($_SESSION->room_sortmode?"desc":"asc");
    $ord[]="r_desc ".($_SESSION->room_sortmode?"desc":"asc");
    break;
case "desc":
    $ord[]="r_desc ".($_SESSION->room_sortmode?"desc":"asc");
    break;
case "floor":
    $_SESSION->room_sort="floor";
    $ord[]="r_floor ".($_SESSION->room_sortmode?"desc":"asc");
    $ord[]="r_desc ".($_SESSION->room_sortmode?"desc":"asc");
    break;
default:
    $_SESSION->room_sort="city";
    $ord[]="b_city ".($_SESSION->room_sortmode?"desc":"asc");
    $ord[]="b_name ".($_SESSION->room_sortmode?"desc":"asc");
    $ord[]="r_floor ".($_SESSION->room_sortmode?"desc":"asc");
    $ord[]="r_desc ".($_SESSION->room_sortmode?"desc":"asc");
}

echo "<form action=\"".root().$PAGE."\" method=\"post\">";

echo input_button("room_new","Nová místnost","newbutton");

$whr=array();
if($_SESSION->room_filterenable) {
    echo "<fieldset><legend>Filtr</legend>";
    echo "<table class=\"nobr\">";

    $opts=array(0=>"Všechna města");
    $qe=$SQL->query("select * from building group by b_city order by b_city");
    while($fe=$qe->obj()) {
	$opts[bin2hex($fe->b_city)]=$fe->b_city;
    }
    $sb=get_ind($_SESSION->room_filter,"000_room_filter_city");
    echo "<tr><td>Město:&nbsp;</td><td>".input_select("000_room_filter_city",$opts,$sb)."</td></tr>";

    $opts=array(0=>"Všechny budovy");
    if($sb) {
	$qe=$SQL->query("select * from building where b_city=\"".$SQL->escape(my_hex2bin($sb))."\" order by b_name");
	while($fe=$qe->obj()) {
	    $opts[$fe->b_id]=$fe->b_name;
	}
    }
    echo "<tr><td>Budova:&nbsp;</td><td><span id=\"measbuildc\">".input_select("001_ajax_build",$opts,get_ind($_SESSION->room_filter,"001_ajax_build"))."</span></td></tr>";
    echo "<tr><td>Označení:&nbsp;</td><td>".input_text("000_room_filter_desc",get_ind($_SESSION->room_filter,"000_room_filter_desc"),"finput")."</td></tr>";
    echo "<tr><td>Patro:&nbsp;</td><td>".input_text("000_room_filter_floor",get_ind($_SESSION->room_filter,"000_room_filter_floor"),"finput")."</td></tr>";

    $opts=array();
    $qe=$SQL->query("select * from material order by ma_desc");
    while($fe=$qe->obj()) {
	$opts[$fe->ma_id]=$fe->ma_desc;
    }
    if(count($opts)) {
	echo "<tr><td>Materiál(y):&nbsp;</td><td>".input_select("000_room_filter_mat[]",$opts,get_ind($_SESSION->room_filter,"000_room_filter_mat"),true)."</td></tr>";
    }
    echo "</table>";

    echo input_button("room_fapply","Použít")." ".input_button("room_fall","Zobrazit vše");
    echo "</fieldset>";
    
    $ftmp=get_ind($_SESSION->room_filter,"000_room_filter_desc");
    if($ftmp) $whr[]="r_desc like \"%".$SQL->escape($ftmp)."%\"";
    $ftmp=get_ind($_SESSION->room_filter,"000_room_filter_floor");
    if($ftmp) $whr[]="r_floor like \"%".$SQL->escape($ftmp)."%\"";
    $fb=get_ind($_SESSION->room_filter,"001_ajax_build");
    if($fb) $whr[]="b_id=\"".$SQL->escape($fb)."\"";
    $ftmp=get_ind($_SESSION->room_filter,"000_room_filter_city");
    if($ftmp) $whr[]="b_city=\"".$SQL->escape(my_hex2bin($ftmp))."\"";
}

//print_read($_SESSION->room_filter);
ob_start();
echo "<table id=\"roomtable\">";
sortlocalref(array(
    array('n'=>"#",'a'=>false),
    array('n'=>"Město",'a'=>"city"),
    array('n'=>"Budova",'a'=>"build"),
    array('n'=>"Označení",'a'=>"desc"),
    array('n'=>"Patro",'a'=>"floor"),
    array('n'=>"Materiály",'a'=>false),
    array('n'=>"Půdorys",'a'=>false),
    array('n'=>"Popis",'a'=>false),
    array('n'=>input_button("room_filter","Filtr"),'a'=>false)
),$_SESSION->room_sort,$_SESSION->room_sortmode);

$offset=(int)($_SESSION->room_currpage*$_PERPAGE);
$limit=(int)$_PERPAGE;

if($_SESSION->room_filterenable) {
    $ms=get_ind($_SESSION->room_filter,"000_room_filter_mat");
    if(is_array($ms) && count($ms)) {
	$wo=array();
	foreach($ms as $val) $wo[]="\"".$SQL->escape($val)."\"";
	$whr[]="rm_mid in (".implode(",",$wo).")";
	$qe=$SQL->query("select SQL_CALC_FOUND_ROWS * from room left join building on r_bid=b_id left join roommat on rm_rid=r_id ".(count($whr)?"where ".implode(" && ",$whr):"")." group by r_id order by ".implode(",",$ord)." limit ".$offset.",".$limit);
    } else $qe=$SQL->query("select SQL_CALC_FOUND_ROWS * from room left join building on r_bid=b_id ".(count($whr)?"where ".implode(" && ",$whr):"")." order by ".implode(",",$ord)." limit ".$offset.",".$limit);
} else $qe=$SQL->query("select SQL_CALC_FOUND_ROWS * from room left join building on r_bid=b_id order by ".implode(",",$ord)." limit ".$offset.",".$limit);

$qer=$SQL->query("select FOUND_ROWS() as rows");
$fe=$qer->obj();
$totalrows=$fe->rows;

while($fe=$qe->obj()) {
    echo "<tr><td>".$fe->r_id."</td>
	<td>".htmlspecialchars($fe->b_city)."</td>
	<td>".htmlspecialchars($fe->b_name)."</td>";
	echo "<td>".htmlspecialchars(strtr($fe->r_desc,"\n","<br />"))."</td>";
	echo "<td>".htmlspecialchars($fe->r_floor)."</td>";
	
	$qe2=$SQL->query("select * from roommat left join material on rm_mid=ma_id where rm_rid=".$fe->r_id);
	if($qe2->rowcount()) {
	    $mats=array();
	    while($fe2=$qe2->obj()) {
		$mats[]=htmlspecialchars($fe2->ma_desc);
	    }
	    echo "<td>".implode("<br />",$mats)."</td>";
	} else echo "<td>&nbsp;</td>";
	
	if($fe->r_img) echo "<td><a href=\"".root()."image/".$fe->r_img."\" target=\"_blank\"><img title=\"".$fe->r_img."\" src=\"".root()."image/".$fe->r_img."/max/100/100\" /></a></td>";
	else echo "<td>&nbsp;</td>";
	echo "<td>".htmlspecialchars($fe->r_note)."</td>";
	echo "<td>".input_button("room_edit[".$fe->r_id."]","Editovat")." ".input_button("room_acc[".$fe->r_id."]","Oprávnění")."</td></tr>";
}

echo "</table>";
$tbl=ob_get_clean();

if($totalrows) pages($totalrows,$_SESSION->room_currpage,"<a href=\"".root().$PAGE."/page/%d\">%d</a>");
echo $tbl;
if($totalrows) pages($totalrows,$_SESSION->room_currpage,"<a href=\"".root().$PAGE."/page/%d\">%d</a>");

echo "<style>
.ui-tooltip {
    max-width: none;
}
</style>";
echo "<script type=\"text/javascript\">
// <![CDATA[
function roomsgui() {
    $(\"button\").button();
    $(\"#000_room_filter_city\").change(function() {
	$.get(\"".root()."ajax/getbuildsel2/\"+$(this).val(),function(data) {
	    $(\"#measbuildc\").html(data);
	});
    });
    $(\"#roomtable img\").tooltip({
	content: function() {
	    return '<img src=\"".root()."image/'+$(this).attr('title')+'/max/500/500\" />';
	}
    });
    
    $(\".pagep a\").button();
    $(\".pagep b\").button({disabled:true});
}
// ]]>
</script>";
    $_JQUERY[]="roomsgui();";

echo "</form>";

if($_SERVER['REQUEST_METHOD']=="POST") {
    $_SESSION->invalid=false;
    $_SESSION->temp_form=false;

    if(get_ind($_POST,"room_new")) {
	redir(root()."roomtab/edit/0");
    }
    if(get_ind($_POST,"room_edit")) {
	if(is_array($_POST['room_edit'])) {
	    $roomedit=(int)key($_POST['room_edit']);
	    if($roomedit) {
		$qe=$SQL->query("select * from room left join building on b_id=r_bid where r_id=".$roomedit);
		$fe=$qe->obj();
		if(!$fe) {
		    $_SESSION->error_text="Místnost nenalezena";
		    redir();
		} else {
		    $_SESSION->temp_form=array(
			"001_room_city"=>bin2hex($fe->b_city),
			"001_ajax_build"=>$fe->r_bid,
			"001_room_floor"=>$fe->r_floor,
			"001_room_desc"=>$fe->r_desc
		    );
		    $qe=$SQL->query("select * from roommat where rm_rid=".$fe->r_id);
		    while($fe=$qe->obj()) $_SESSION->temp_form["000_room_mat_".$fe->rm_mid]='Y';
		    redir(root()."roomtab/edit/".$roomedit);
		}
	    }
	}
	redir();
    }
    if(get_ind($_POST,"room_acc")) {
	if(is_array($_POST['room_acc'])) {
	    $racc=key($_POST['room_acc']);
	    redir(root()."roomacc/edit/".$racc);
	}
	redir();
    }
    if(get_ind($_POST,"room_filter")) {
	$_SESSION->room_filterenable=!$_SESSION->room_filterenable;
	if($_SESSION->room_filterenable) $_SESSION->room_currpage=0;
	redir();
    }
    if(get_ind($_POST,"room_fall")) {
	$_SESSION->room_filter=false;
	redir();
    }
    if(get_ind($_POST,"room_fapply")) {
	postcheck($ITEMS,$_POST);
	$_SESSION->room_filter=$_POST;
	$_SESSION->room_currpage=0;
	redir();
    }
    redir();
}
