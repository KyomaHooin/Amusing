<?php

pageperm();
showmenu();

showerror();

ajaxsess();

switch($ARGC) {
case 2:
    switch($ARGV[0]) {
    case "sort":
	if($_SESSION->measpoint_sort==$ARGV[1]) $_SESSION->measpoint_sortmode=!$_SESSION->measpoint_sortmode;
	$_SESSION->measpoint_sort=$ARGV[1];
	redir();
    case "page":
	$_SESSION->measpoint_currpage=(int)$ARGV[1];
	redir();
    }
    break;
}

$ord=array();
switch($_SESSION->measpoint_sort) {
case "build":
    $ord[]="b_name ".($_SESSION->measpoint_sortmode?"desc":"asc");
    break;
case "desc":
    $ord[]="m_desc ".($_SESSION->measpoint_sortmode?"desc":"asc");
    break;
case "depart":
    $ord[]="m_depart ".($_SESSION->measpoint_sortmode?"desc":"asc");
    break;
case "sen":
    $ord[]="s_serial ".($_SESSION->measpoint_sortmode?"desc":"asc");
    break;
case "act":
    $ord[]="m_active ".($_SESSION->measpoint_sortmode?"desc":"asc");
    break;
case "room":
    $ord[]="r_desc ".($_SESSION->measpoint_sortmode?"desc":"asc");
    break;
case "vfrom":
    $ord[]="m_validfrom ".($_SESSION->measpoint_sortmode?"desc":"asc");
    break;
case "vto":
    $ord[]="m_validto ".($_SESSION->measpoint_sortmode?"desc":"asc");
    break;
case "maxt":
    $ord[]="maxt ".($_SESSION->measpoint_sortmode?"desc":"asc");
    break;
case "floor":
    break;
default:
    $_SESSION->measpoint_sort="city";
    $ord[]="b_city ".($_SESSION->measpoint_sortmode?"desc":"asc");
}
$ord[]="r_floor ".($_SESSION->measpoint_sortmode?"desc":"asc");

echo "<form id=\"measform\" action=\"".root().$PAGE."\" method=\"post\">";

switch(urole()) {
case 'A':
case 'D':
    echo input_button("meas_new","Nový měřící bod","newbutton");
    break;
}

$whr=array();
if($_SESSION->measpoint_filterenable) {
    echo "<fieldset><legend>Filtr</legend>";
    echo "<table class=\"nobr\">";

    $opts=array(0=>"Všechna města");
    $qe=$SQL->query("select * from building group by b_city order by b_city");
    while($fe=$qe->obj()) {
	$opts[bin2hex($fe->b_city)]=$fe->b_city;
    }
    $sb=get_ind($_SESSION->measpoint_filter,"000_meas_filter_city");
    echo "<tr><td>Město:&nbsp;</td><td>".input_select("000_meas_filter_city",$opts,$sb)."</td></tr>";
    
    $opts=array(0=>"Všechny budovy");
    if($sb) {
	$qe=$SQL->query("select * from building where b_city=\"".$SQL->escape(my_hex2bin($sb))."\" order by b_name");
	while($fe=$qe->obj()) {
	    $opts[$fe->b_id]=$fe->b_name;
	}
	$sb=get_ind($_SESSION->measpoint_filter,"001_ajax_build");
    } else $sb=false;
    echo "<tr><td>Budova:&nbsp;</td><td><span id=\"measbuildc\">".input_select("001_ajax_build",$opts,$sb)."</span></td></tr>";
    
    $opts=array(0=>"Všechny místnosti");
    if($sb) {
	$qe=$SQL->query("select * from room where r_bid=\"".$SQL->escape($sb)."\" order by r_desc");
	while($fe=$qe->obj()) {
	    $opts[$fe->r_id]=$fe->r_desc;
	}
    }
    echo "<tr><td>Místnost:&nbsp;</td><td><span id=\"measroomc\">".input_select("001_ajax_room",$opts,get_ind($_SESSION->measpoint_filter,"001_ajax_room"))."</span></td></tr>";
    echo "<tr><td>Oddělení:&nbsp;</td><td>".input_text("000_meas_filter_depart",get_ind($_SESSION->measpoint_filter,"000_meas_filter_depart"),"finput")."</td></tr>";
    echo "<tr><td>Popis:&nbsp;</td><td>".input_text("000_meas_filter_desc",get_ind($_SESSION->measpoint_filter,"000_meas_filter_desc"),"finput")."</td></tr>";
    echo "</table>";

    echo input_button("meas_fapply","Použít")." ".input_button("meas_fall","Zobrazit vše");
    echo "</fieldset>";
    
    $ftmp=get_ind($_SESSION->measpoint_filter,"000_meas_filter_desc");
    if($ftmp) $whr[]="m_desc like \"%".$SQL->escape($ftmp)."%\"";
    $ftmp=get_ind($_SESSION->measpoint_filter,"000_meas_filter_depart");
    if($ftmp) $whr[]="m_depart like \"%".$SQL->escape($ftmp)."%\"";
    $fb=get_ind($_SESSION->measpoint_filter,"001_ajax_build");
    if($fb) $whr[]="b_id=\"".$SQL->escape($fb)."\"";
    $fb=get_ind($_SESSION->measpoint_filter,"001_ajax_room");
    if($fb) $whr[]="r_id=\"".$SQL->escape($fb)."\"";
    $ftmp=get_ind($_SESSION->measpoint_filter,"000_meas_filter_city");
    if($ftmp) $whr[]="b_city=\"".$SQL->escape(my_hex2bin($ftmp))."\"";

    echo "<script type=\"text/javascript\">
// <![CDATA[
function buildchange() {
    $.get(\"".root()."ajax/getroomsel2/\"+$(\"#001_ajax_build\").val(),function(data) {
	$(\"#measroomc\").html(data);
    });
}
function buildsub() {
    $(\"#001_ajax_build\").change(buildchange);
    $(\"#000_meas_filter_city\").change(function() {
	$.get(\"".root()."ajax/getbuildsel2/\"+$(this).val(),function(data) {
	    $(\"#measbuildc\").html(data);
	    $(\"#001_ajax_build\").change(buildchange);
	    buildchange();
	});
    });
}
// ]]>
</script>";
    $_JQUERY[]="buildsub();";

}

ob_start();
$offset=(int)($_SESSION->measpoint_currpage*$_PERPAGE);
$limit=(int)$_PERPAGE;

echo "<table id=\"measpointstab\">";
sortlocalref(array(
    array('n'=>"&nbsp;",'a'=>false),
    array('n'=>"Město",'a'=>"city"),
    array('n'=>"Budova",'a'=>"build"),
    array('n'=>"Místnost",'a'=>"room"),
    array('n'=>"Patro",'a'=>"floor"),
    array('n'=>"Oddělení",'a'=>"depart"),
    array('n'=>"Popis",'a'=>"desc"),
    array('n'=>"Senzor",'a'=>"sen"),
    array('n'=>"Platný od",'a'=>"vfrom"),
    array('n'=>"Platný do",'a'=>"vto"),
    array('n'=>"Čas posl. hod.",'a'=>"maxt"),
    array('n'=>"akt.",'a'=>"act"),
    array('n'=>"Oprávnění",'a'=>false),
    array('n'=>"&nbsp;",'a'=>false),
    array('n'=>input_button("meas_filter","Filtr"),'a'=>false)
),$_SESSION->measpoint_sort,$_SESSION->measpoint_sortmode);

$m_col=getcolumns("measuring");
$r_col=getcolumns("room");
$b_col=getcolumns("building");
$s_col=getcolumns("sensor");
$st_col=getcolumns("sensortype");

$gen_cols=implode(",",$m_col).",".implode(",",$r_col).",".implode(",",$b_col).",".implode(",",$s_col).",".implode(",",$st_col);

switch(urole()) {
case 'A':
case 'D':
    $qe=$SQL->query("select SQL_CALC_FOUND_ROWS ".$gen_cols.",max(vmc_lastrawtime) as maxt from measuring left join room on m_rid=r_id left join building on r_bid=b_id left join sensor on m_id=s_mid left join sensortype on s_type=st_id left join varmeascache on m_id=vmc_mid ".(count($whr)?"where ".implode(" && ",$whr):"")." group by m_id order by ".implode(",",$ord)." limit ".$offset.",".$limit);
    break;
default:
    $p_col=getcolumns("permission");
    $whr[]="(pe_uid=0 || pe_uid=".uid().")";
    $qe=$SQL->query("select SQL_CALC_FOUND_ROWS ".$gen_cols.",".implode(",",$p_col).",max(vmc_lastrawtime) as maxt from measuring left join room on m_rid=r_id left join building on r_bid=b_id left join sensor on m_id=s_mid left join sensortype on s_type=st_id left join varmeascache on m_id=vmc_mid left join permission on m_id=pe_mid ".(count($whr)?"where ".implode(" && ",$whr):"")." group by m_id order by ".implode(",",$ord)." limit ".$offset.",".$limit);
}
$qer=$SQL->query("select FOUND_ROWS() as rows");
$fe=$qer->obj();
$totalrows=$fe->rows;

while($fe=$qe->obj()) {
    echo "<tr><td>".input_check("meas_id[".$fe->m_id."]").input_hidden("meas_hid[]",$fe->m_id)."</td>
	<td>".htmlspecialchars($fe->b_city)."</td>
	<td>".htmlspecialchars($fe->b_name)."</td>
	<td>".htmlspecialchars($fe->r_desc)."</td>
	<td>".htmlspecialchars($fe->r_floor)."</td>
	<td>".htmlspecialchars($fe->m_depart)."</td>
	<td>".htmlspecialchars($fe->m_desc)."</td>
	<td>".htmlspecialchars($fe->s_serial)."</td>
	<td>".showdate($fe->m_validfrom)."</td>
	<td>".showdate($fe->m_validto)."</td>
	<td>".showtime2($fe->maxt)."</td>";
    echo "<td>".input_check("meas_av[".$fe->m_id."]",'Y',$fe->m_active=='Y',false,true)."</td>";

    $canimport=false;
    $qeacc=$SQL->query("select * from permission left join user on pe_uid=u_id where pe_mid=".$fe->m_id." order by u_fullname");
    if(!$qeacc->rowcount()) echo "<td>&nbsp;</td>";
    else {
	echo "<td style=\"white-space:nowrap;\">";
	while($feacc=$qeacc->obj()) {
	    if($feacc->pe_uid) echo $feacc->u_fullname." (".$feacc->u_uname.")";
	    else echo "Všichni";
	    echo " | ";
	    switch($feacc->pe_type) {
	    case 'I':
		if($feacc->pe_uid==uid()) $canimport=true;
		echo "import";
		break;
	    case 'V':
		echo "prohlížení";
		break;
	    default:
		echo "invalidní";
	    }
	    echo "<br />";
	}
	echo "</td>";
    }
    
    if($fe->m_img) echo "<td><a href=\"".root()."image/".$fe->m_img."\" target=\"_blank\"><img title=\"".$fe->m_img."\" src=\"".root()."image/".$fe->m_img."/max/100/100\" /></a></td>";
    else echo "<td>&nbsp;</td>";
    
    $stt=false;
    if($fe->st_class) {
	$stt=new $fe->st_class();
	$stt->fe=$fe;
    }

    echo "<td>";
    switch(urole()) {
    case 'A':
	echo input_button("meas_edit[".$fe->m_id."]","Editovat");
	if($fe->s_id && $stt && $stt->canimport()) echo " ".input_button("meas_import[".$fe->s_id."]","Importovat data");
	echo " ".input_button("meas_comm[".$fe->m_id."]","Komentář");
	echo " ".input_button("meas_acc[".$fe->m_id."]","Oprávnění");
	echo " ".input_button("meas_del[".$fe->m_id."]","Smazat data")." ".input_button("meas_rem[".$fe->m_id."]","Smazat bod");
	break;
    case 'D':
	echo input_button("meas_edit[".$fe->m_id."]","Editovat");
	if($fe->s_id && $stt && $stt->canimport()) echo " ".input_button("meas_import[".$fe->s_id."]","Importovat data");
	echo " ".input_button("meas_comm[".$fe->m_id."]","Komentář");
	echo " ".input_button("meas_acc[".$fe->m_id."]","Oprávnění");
	break;    
    default:
	if($fe->s_id && $stt && $stt->canimport() && $canimport) {
	    echo input_button("meas_import[".$fe->s_id."]","Importovat data");
	}
	echo "&nbsp;";
    }
    echo "</td>";
    
    echo "</tr>";
}

echo "</table>";
$tbl=ob_get_clean();

if($totalrows) pages($totalrows,$_SESSION->measpoint_currpage,"<a href=\"".root().$PAGE."/page/%d\">%d</a>");
echo $tbl;
if($totalrows) pages($totalrows,$_SESSION->measpoint_currpage,"<a href=\"".root().$PAGE."/page/%d\">%d</a>");

echo input_button("meas_permsel","Oprávnění pro vybrané")." ".input_button("meas_permall","Oprávnění pro zobrazené");

echo "<style>
.ui-tooltip {
    max-width: none;
}
</style>";
echo "<script type=\"text/javascript\">
// <![CDATA[
var todata=false;
var topoint=false;
function measgui() {
    $(\"button\").button().click(function() {
	var bid=$(this).attr('id');
	if(bid.match(/^meas_del\\[\\d+\\]$/)) todata=true;
	else if(bid.match(/^meas_rem\\[\\d+\\]$/)) topoint=true;
    });
    $(\"#measform\").submit(function() {
	if(todata) {
	    todata=false;
	    return confirm('Opravdu nenávratně smazat data?');
	}
	if(topoint) {
	    topoint=false;
	    return confirm('Opravdu nenávratně smazat bod, data a alarmy?');
	}
    });
    $(\"#measpointstab img\").tooltip({
	content: function() {
	    return '<img src=\"".root()."image/'+$(this).attr('title')+'/max/500/500\" />';
	}
    });
    $(\".pagep a\").button();
    $(\".pagep b\").button({disabled:true});
}
// ]]>
</script>";
    $_JQUERY[]="measgui();";

echo "</form>";

function measdelete($mid) {
    global $SQL;
    $qe=$SQL->query("show tables");
    while($fe=$qe->row()) {
	if(preg_match('/^values_\d+$/',$fe[0])) {
	    $SQL->query("delete from ".$fe[0]." where v_mid=".$mid);
	} else if(preg_match('/^valuesblob_\d+$/',$fe[0])) {
	    $SQL->query("delete from ".$fe[0]." where vb_mid=".$mid);
	} else if(preg_match('/^rawvalues_\d+$/',$fe[0])) {
	    $SQL->query("delete from ".$fe[0]." where rv_mid=".$mid);
	}
    }
    $SQL->query("delete from varmeascache where vmc_mid=".$mid);
    $SQL->query("delete from comment where cm_mid=".$mid);
}

function measdelrest($mid) {
    global $SQL;
    $qe=$SQL->query("select * from measuring where m_id=".$mid);
    $fe=$qe->obj();
    if($fe) $SQL->query("delete from image where img_id=".$fe->m_img);
    
    $SQL->query("delete from alarm where a_mid=".$mid);
    $SQL->query("delete from alarmack where ac_mid=".$mid);
    $SQL->query("delete from alarmlog where al_mid=".$mid);
    $SQL->query("delete from measuring where m_id=".$mid);
    $SQL->query("delete from permission where pe_mid=".$mid);
    $SQL->query("update sensor set s_mid=0 where s_mid=".$mid);
}

if($_SERVER['REQUEST_METHOD']=="POST") {
    $_SESSION->invalid=false;
    $_SESSION->temp_form=false;
    
    $_SESSION->measids=false;

    if(get_ind($_POST,"meas_new")) {
	redir(root()."measpointtab/edit/0");
    }
    if(get_ind($_POST,"meas_edit")) {
	if(is_array($_POST['meas_edit'])) {
	    $measpointedit=(int)key($_POST['meas_edit']);
	    if($measpointedit) {
		$qe=$SQL->query("select * from measuring left join room on m_rid=r_id left join building on r_bid=b_id left join sensor on m_id=s_mid where m_id=".$measpointedit);
		$fe=$qe->obj();
		if(!$fe) {
		    $_SESSION->error_text="Měřící bod nenalezen";
		    redir();
		} else {
		    $_SESSION->temp_form=array(
			"001_meas_city"=>bin2hex($fe->b_city),
			"001_ajax_build"=>$fe->b_id,
			"001_ajax_room"=>$fe->r_id,
			"000_meas_depart"=>$fe->m_depart,
			"000_meas_desc"=>$fe->m_desc,
			"001_meas_sensor"=>$fe->s_id,
			"000_meas_validfrom"=>showdate($fe->m_validfrom),
			"000_meas_validto"=>showdate($fe->m_validto),
			"000_meas_state"=>$fe->m_active
		    );
		    redir(root()."measpointtab/edit/".$measpointedit);
		}
	    }
	}
	redir();
    }
    if(get_ind($_POST,"meas_acc")) {
	if(is_array($_POST['meas_acc'])) {
	    $measpointedit=(int)key($_POST['meas_acc']);
	    if($measpointedit) {
		$qe=$SQL->query("select * from measuring where m_id=".$measpointedit);
		$fe=$qe->obj();
		if(!$fe) {
		    $_SESSION->error_text="Měřící bod nenalezen";
		    redir();
		} else {
		    redir(root()."measpointacc/edit/".$measpointedit);
		}
	    }
	}
	redir();
    }
    if(get_ind($_POST,"meas_filter")) {
	$_SESSION->measpoint_filterenable=!$_SESSION->measpoint_filterenable;
	if($_SESSION->measpoint_filterenable) $_SESSION->measpoint_currpage=0;
	redir();
    }
    if(get_ind($_POST,"meas_fall")) {
	$_SESSION->measpoint_filter=false;
	redir();
    }
    if(get_ind($_POST,"meas_fapply")) {
	postcheck($ITEMS,$_POST);
	$_SESSION->measpoint_filter=$_POST;
	$_SESSION->measpoint_currpage=0;
	redir();
    }
    if(get_ind($_POST,"meas_del")) {
	if(urole()!='A') {
	    $_SESSION->error_text="přístup odepřen";
	    redir();
	}
    
	$si=get_ind($_POST,"meas_del");
	if(is_array($si)) {
	    $mid=(int)key($si);
	    // HC
	    measdelete($mid);
	    $_SESSION->error_text="Data smazána";
	}
	redir();
    }
    if(get_ind($_POST,"meas_rem")) {
	if(urole()!='A') {
	    $_SESSION->error_text="přístup odepřen";
	    redir();
	}

	$si=get_ind($_POST,"meas_rem");
	if(is_array($si)) {
	    $mid=(int)key($si);
	    // HC
	    measdelete($mid);
	    measdelrest($mid);
	    $_SESSION->error_text="Bod a data smazána";
	}
	redir();
    }
    if(get_ind($_POST,"meas_import")) {
	$si=get_ind($_POST,"meas_import");
	if(is_array($si)) {
	    $_SESSION->prevpage=$PAGE;
	    redir(root()."import/".key($si));
	}
	redir();
    }
    if(get_ind($_POST,"meas_comm")) {
	$si=get_ind($_POST,"meas_comm");
	if(is_array($si)) {
	    $mid=(int)key($si);

	    $qe=$SQL->query("select * from measuring left join room on m_rid=r_id left join building on r_bid=b_id where m_id=".$mid);
	    $fe=$qe->obj();
	    if($fe) {
		$_SESSION->temp_form=array(
		    "001_comm_city"=>bin2hex($fe->b_city),
		    "001_ajax_build"=>$fe->b_id,
		    "001_ajax_room"=>$fe->r_id,
		    "001_ajax_meas"=>$fe->m_id,
		    "001_comm_date"=>showdate(time()),
		    "001_comm_date_h"=>date("H"),
		    "001_comm_date_m"=>sprintf("%02d",$m-($m%5))
		);
		$_SESSION->prevpage=$PAGE;
		redir(root()."commenttab/edit/0");
	    } else $_SESSION->error_text="Měřící bod nenalezen";
	}
	redir();
    }
    if(get_ind($_POST,"meas_permall")) {
	$mi=get_ind($_POST,"meas_hid");
	$di=array();
	if(is_array($mi)) {
	    foreach($mi as $val) if(is_intnumber($val)) $di[]=$val;
	}
	if(count($di)) {
	    $_SESSION->measids=$di;
	    redir(root()."measpointacc2");
	} else $_SESSION->error_text="Žádný měřící bod";
	redir();
    }
    if(get_ind($_POST,"meas_permsel")) {
	$mi=get_ind($_POST,"meas_id");
	$di=array();
	if(is_array($mi)) {
	    foreach($mi as $key=>$val) if($val=='Y' && is_intnumber($key)) $di[]=$key;
	}
	if(count($di)) {
	    $_SESSION->measids=$di;
	    redir(root()."measpointacc2");
	} else $_SESSION->error_text="Žádný měřící bod";
	redir();
    }
    redir();
}
