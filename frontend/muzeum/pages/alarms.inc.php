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
	if($_SESSION->alarms_sort==$ARGV[1]) $_SESSION->alarms_sortmode=!$_SESSION->alarms_sortmode;
	$_SESSION->alarms_sort=$ARGV[1];
	redir();
    case "page":
	$_SESSION->alarms_currpage=(int)$ARGV[1];
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
switch($_SESSION->alarms_sort) {
case "build":
    $ord[]="b_name ".($_SESSION->alarms_sortmode?"desc":"asc");
    break;
case "city":
    $ord[]="b_city ".($_SESSION->alarms_sortmode?"desc":"asc");
    break;
case "desc":
    $ord[]="m_desc ".($_SESSION->alarms_sortmode?"desc":"asc");
    break;
case "sen":
    $ord[]="s_serial ".($_SESSION->alarms_sortmode?"desc":"asc");
    break;
case "act":
    $ord[]="m_active ".($_SESSION->alarms_sortmode?"desc":"asc");
    break;
case "room":
    $ord[]="r_desc ".($_SESSION->alarms_sortmode?"desc":"asc");
    break;
default:
    $_SESSION->alarms_sort="floor";
}
$ord[]="r_floor ".($_SESSION->alarms_sortmode?"desc":"asc");

echo "<form id=\"alarmform\" action=\"".root().$PAGE."\" method=\"post\">";

$whr=array();
if($_SESSION->alarms_filterenable) { // using same filter variables
    echo "<fieldset><legend>Filtr</legend>";
    echo "<table class=\"nobr\">";

    $opts=array(0=>"Všechna města");
    $qe=$SQL->query("select * from building group by b_city order by b_city");
    while($fe=$qe->obj()) {
	$opts[bin2hex($fe->b_city)]=$fe->b_city;
    }
    $sb=get_ind($_SESSION->alarms_filter,"000_alarm_filter_city");
    echo "<tr><td>Město:&nbsp;</td><td>".input_select("000_alarm_filter_city",$opts,$sb)."</td></tr>";

    $opts=array(0=>"Všechny budovy");
    if($sb) {
	$qe=$SQL->query("select * from building where b_city=\"".$SQL->escape(my_hex2bin($sb))."\" order by b_name");
	while($fe=$qe->obj()) {
	    $opts[$fe->b_id]=$fe->b_name;
	}
        $sb=get_ind($_SESSION->alarms_filter,"001_ajax_build");
    } else $sb=false;
    echo "<tr><td>Budova:&nbsp;</td><td><span id=\"measbuildc\">".input_select("001_ajax_build",$opts,$sb)."</span></td></tr>";
    
    $opts=array(0=>"Všechny místnosti");
    if($sb) {
	$qe=$SQL->query("select * from room where r_bid=\"".$SQL->escape($sb)."\" order by r_desc");
	while($fe=$qe->obj()) {
	    $opts[$fe->r_id]=$fe->r_desc;
	}
    }
    echo "<tr><td>Místnost:&nbsp;</td><td><span id=\"measroomc\">".input_select("001_ajax_room",$opts,get_ind($_SESSION->alarms_filter,"001_ajax_room"))."</span></td></tr>";
    echo "<tr><td>Popis místnosti:&nbsp;</td><td>".input_text("000_alarm_filter_desc",get_ind($_SESSION->alarms_filter,"000_alarm_filter_desc"),"finput")."</td></tr>";

    $opts=array();
    $qe=$SQL->query("select * from material order by ma_desc");
    while($fe=$qe->obj()) {
	$opts[$fe->ma_id]=$fe->ma_desc;
    }
    if(count($opts)) {
	echo "<tr><td>Materiál(y):&nbsp;</td><td>".input_select("000_alarm_filter_mat[]",$opts,get_ind($_SESSION->alarms_filter,"000_alarm_filter_mat"),true)."</td></tr>";
    }
    echo "</table>";

    echo input_button("alarm_fapply","Použít")." ".input_button("alarm_fall","Zobrazit vše");
    echo "</fieldset>";
    
    $ftmp=get_ind($_SESSION->alarms_filter,"000_alarm_filter_desc");
    if($ftmp) $whr[]="m_desc like \"%".$SQL->escape($ftmp)."%\"";
    $fb=get_ind($_SESSION->alarms_filter,"001_ajax_build");
    if($fb) $whr[]="b_id=\"".$SQL->escape($fb)."\"";
    $fb=get_ind($_SESSION->alarms_filter,"001_ajax_room");
    if($fb) $whr[]="r_id=\"".$SQL->escape($fb)."\"";
    $ftmp=get_ind($_SESSION->alarms_filter,"000_alarm_filter_city");
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
    $(\"#000_alarm_filter_city\").change(function() {
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

if($makecsv) {
    ob_clean();
    $_NOHEAD=true;
//    header("Content-type: text/plain");
    header("Content-type: text/x-csv");
    header("Content-Disposition: attachment; filename=".$PAGE.".csv");
    
    $ord[]="a_desc";
    ob_start();
    echo csvline(array("#","Město","Budova","Místnost","Patro","Popis","Senzor","akt.","#","charakter","popis","veličina","uživatel","email","typ"));

    switch(urole()) {
    case 'A':
	$grp=false;
	$qpref="select *,if(isnull(ap_desc),\"\",ap_desc) as apdesc from alarm left join alarm_preset on a_preset=ap_id left join variable on a_vid=var_id left join user on u_id=a_uid left join  measuring on a_mid=m_id left join room on m_rid=r_id left join building on r_bid=b_id left join sensor on m_id=s_mid left join sensortype on s_type=st_id";
	break;
    case 'D':
	$grp=false;
	$whr[]="a_uid=".uid();
	$qpref="select *,if(isnull(ap_desc),\"\",ap_desc) as apdesc from alarm left join alarm_preset on a_preset=ap_id left join variable on a_vid=var_id left join user on u_id=a_uid left join  measuring on a_mid=m_id left join room on m_rid=r_id left join building on r_bid=b_id left join sensor on m_id=s_mid left join sensortype on s_type=st_id";
	break;
    default:
	$grp="a_id";
	$whr[]="(pe_uid=0 || pe_uid=".uid().")";
	$whr[]="a_uid=".uid();
	$qpref="select *,if(isnull(ap_desc),\"\",ap_desc) as apdesc from alarm left join alarm_preset on a_preset=ap_id left join variable on a_vid=var_id left join user on u_id=a_uid left join  measuring on a_mid=m_id left join room on m_rid=r_id left join building on r_bid=b_id left join sensor on m_id=s_mid left join sensortype on s_type=st_id left join permission on m_id=pe_mid";
    }
    if($_SESSION->alarms_filterenable) {
	$ms=get_ind($_SESSION->alarms_filter,"000_alarm_filter_mat");
	if(is_array($ms) && count($ms)) {
	    $wo=array();
	    foreach($ms as $val) $wo[]="\"".$SQL->escape($val)."\"";
	    $whr[]="rm_mid in (".implode(",",$wo).")";
	    $qe=$SQL->query($qpref." left join roommat on rm_rid=r_id ".(count($whr)?"where ".implode(" && ",$whr):"")." group by a_id order by ".implode(",",$ord));
	} else $qe=$SQL->query($qpref." ".(count($whr)?"where ".implode(" && ",$whr):"").($grp?" group by ".$grp:"")." order by ".implode(",",$ord));
    } else $qe=$SQL->query($qpref." ".(count($whr)?"where ".implode(" && ",$whr):"").($grp?" group by ".$grp:"")." order by ".implode(",",$ord));
    while($fe=$qe->obj()) {
	$alrm=c_alarm_gen::getalarmbyname($fe->a_class);
	echo csvline(array($fe->m_id,$fe->b_city,$fe->b_name,$fe->r_desc,$fe->r_floor,$fe->m_desc,$fe->s_serial,$fe->m_active=='Y'?"ano":"ne",
	    $fe->a_id,$fe->a_crit=='Y'?"Kritický":"Varování",$fe->a_desc,$fe->var_desc,$fe->u_fullname,$fe->a_email,$alrm->desc($fe->a_data)));
    }
    $csv=ob_get_contents();
    ob_end_clean();
    echo csvoutput($csv);

    exit();
}

ob_start();
$offset=(int)($_SESSION->alarms_currpage*$_PERPAGE);
$limit=(int)$_PERPAGE;
echo "<table>";
sortlocalref(array(
    array('n'=>"#",'a'=>false),
    array('n'=>"&nbsp;",'a'=>false),
    array('n'=>"Město",'a'=>"city"),
    array('n'=>"Budova",'a'=>"build"),
    array('n'=>"Místnost",'a'=>"room"),
    array('n'=>"Patro",'a'=>"floor"),
    array('n'=>"Popis",'a'=>"desc"),
    array('n'=>"Senzor",'a'=>"sen"),
    array('n'=>"akt.",'a'=>"act"),
    array('n'=>input_button("alarm_filter","Filtr"),'a'=>false)
),$_SESSION->alarms_sort,$_SESSION->alarms_sortmode);

switch(urole()) {
case 'A':
case 'D':
    $grp=false;
    $qpref="select SQL_CALC_FOUND_ROWS * from measuring left join room on m_rid=r_id left join building on r_bid=b_id left join sensor on m_id=s_mid left join sensortype on s_type=st_id";
    break;
default:
    $grp="m_id";
    $whr[]="(pe_uid=0 || pe_uid=".uid().")";
    $qpref="select SQL_CALC_FOUND_ROWS * from measuring left join room on m_rid=r_id left join building on r_bid=b_id left join sensor on m_id=s_mid left join sensortype on s_type=st_id left join permission on m_id=pe_mid";
}

if($_SESSION->alarms_filterenable) {
    $ms=get_ind($_SESSION->alarms_filter,"000_alarm_filter_mat");
    if(is_array($ms) && count($ms)) {
	$wo=array();
	foreach($ms as $val) $wo[]="\"".$SQL->escape($val)."\"";
	$whr[]="rm_mid in (".implode(",",$wo).")";
	$qe=$SQL->query($qpref." left join roommat on rm_rid=r_id ".(count($whr)?"where ".implode(" && ",$whr):"")." group by m_id order by ".implode(",",$ord)." limit ".$offset.",".$limit);
    } else $qe=$SQL->query($qpref." ".(count($whr)?"where ".implode(" && ",$whr):"").($grp?" group by ".$grp:"")." order by ".implode(",",$ord)." limit ".$offset.",".$limit);
} else $qe=$SQL->query($qpref." ".(count($whr)?"where ".implode(" && ",$whr):"").($grp?" group by ".$grp:"")." order by ".implode(",",$ord)." limit ".$offset.",".$limit);
$qer=$SQL->query("select FOUND_ROWS() as rows");
$fe=$qer->obj();
$totalrows=$fe->rows;

while($fe=$qe->obj()) {
    echo "<tr><td>".$fe->m_id."</td><td>".input_check("meas_id[".$fe->m_id."]").input_hidden("meas_hid[]",$fe->m_id)."</td>
	<td>".htmlspecialchars($fe->b_city)."</td>
	<td>".htmlspecialchars($fe->b_name)."</td>
	<td>".htmlspecialchars($fe->r_desc)."</td>
	<td>".htmlspecialchars($fe->r_floor)."</td>
	<td>".htmlspecialchars($fe->m_desc)."</td>
	<td>".htmlspecialchars($fe->s_serial)."</td>";
    echo "<td>".input_check("meas_av[".$fe->m_id."]",'Y',$fe->m_active=='Y',false,true)."</td>";
    echo "<td>";

    $stt=false;
    if($fe->st_class) {
	$stt=new $fe->st_class();
	$stt->fe=$fe;
    }
    if($fe->s_id && $stt && $stt->canimport()) {
	switch(urole()) {
	case 'A':
	case 'D':
	    echo input_button("meas_import[".$fe->s_id."]","Importovat data");
	    break;
	default:
	    echo "&nbsp;";
	}
    } else echo "&nbsp;";
    echo "</td></tr>";
// next line about alarms
    echo "<tr><td colspan=\"8\">";
    if(urole()=='A' or urole()=='D') $qe2=$SQL->query("select *,if(isnull(ap_desc),\"\",ap_desc) as apdesc from alarm left join alarm_preset on a_preset=ap_id left join variable on a_vid=var_id left join user on u_id=a_uid where a_mid=".$fe->m_id." order by a_desc");
    else $qe2=$SQL->query("select *,if(isnull(ap_desc),\"\",ap_desc) as apdesc from alarm left join alarm_preset on a_preset=ap_id left join variable on a_vid=var_id left join user on u_id=a_uid where a_mid=".$fe->m_id." && a_uid=".uid()." order by a_desc");
    if(!$qe2->rowcount()) echo "žádné alarmy";
    else {
	echo "<ul>";
	while($fe2=$qe2->obj()) {
	    $alrm=c_alarm_gen::getalarmbyname($fe2->a_class);
	    if(!$alrm) {
		echo "<li>invalidni alarm: ".htmlspecialchars($fe2->a_class)."</li>";
	    } else {
		echo "<li>".$fe2->a_id." ".input_check("000_alarm_id[".$fe2->a_id."]")." ";
		if($fe2->a_crit=='Y') echo "<b>Kritický</b><br />";
		else echo "Varování<br />";
		if(strlen($fe2->apdesc)) echo "<b>".htmlspecialchars("definice: ".$fe2->apdesc)."</b> ";
//		if(urole()=='A') echo htmlspecialchars($fe2->a_desc." ".$fe2->var_desc." ".$fe2->u_fullname);
//		else echo htmlspecialchars($fe2->a_desc." ".$fe2->var_desc);
		echo htmlspecialchars($fe2->a_desc);
		echo "<br />".htmlspecialchars($fe2->a_email)."<br />".$alrm->desc($fe2->a_data)."</li>";
//		echo "<br />".htmlspecialchars($fe2->a_email)."<br /></li>";
	    }
	}
	echo "</ul>";
    }
    echo "</td><td>".input_button("alarm_new[".$fe->m_id."]","Nový alarm")."</td><td>&nbsp;</td></tr>";
}

echo "</table>";
$tbl=ob_get_clean();
if($totalrows) pages($totalrows,$_SESSION->alarms_currpage,"<a href=\"".root().$PAGE."/page/%d\">%d</a>");
echo $tbl;
if($totalrows) pages($totalrows,$_SESSION->alarms_currpage,"<a href=\"".root().$PAGE."/page/%d\">%d</a>");

echo input_button("alarm_rem","Smazat vybrané")." ".input_button("alarm_remall","Smazat zobrazené")." ".input_button("alarm_multinew","Nový alarm pro vybrané")." ".input_button("alarm_allnew","Nový alarm pro zobrazené");

echo "<br /><a href=\"".root().$PAGE."/csv\">Uložit jako csv</a>";

echo "<script type=\"text/javascript\">
// <![CDATA[
var arem=false;
function alarmsgui() {
    $(\"button\").button();
    $(\"#alarmform\").submit(function() {
	if(arem) {
	    arem=false;
	    return confirm('Opravdu nenávratně smazat alarmy?');
	}
    });
    $(\"#alarm_rem\").click(function() {
	arem=true;
    });
    $(\"#alarm_remall\").click(function() {
	arem=true;
    });
    $(\".pagep a\").button();
    $(\".pagep b\").button({disabled:true});
}
// ]]>
</script>";
    $_JQUERY[]="alarmsgui();";

echo "</form>";

function setmidskey($mids) {
    if($_SESSION->alarms_midskey===false) $_SESSION->alarms_midskey=0;
    $_SESSION->alarms_midskey++;
    if(!is_array($_SESSION->alarms_midsdata)) $_SESSION->alarms_midsdata=array();
    $_SESSION->alarms_midsdata[$_SESSION->alarms_midskey]=$mids;
    return $_SESSION->alarms_midskey;
}

if($_SERVER['REQUEST_METHOD']=="POST") {
    $_SESSION->invalid=false;
    $_SESSION->temp_form=false;

    if(get_ind($_POST,"alarm_filter")) {
	$_SESSION->alarms_filterenable=!$_SESSION->alarms_filterenable;
	if($_SESSION->alarms_filterenable) $_SESSION->alarms_currpage=0;
	redir();
    }
    if(get_ind($_POST,"alarm_fall")) {
	$_SESSION->alarms_filter=false;
	redir();
    }
    if(get_ind($_POST,"alarm_fapply")) {
	postcheck($ITEMS,$_POST);
	$_SESSION->alarms_filter=$_POST;
	$_SESSION->alarms_currpage=0;
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
    if(get_ind($_POST,"alarm_new")) {
	$mid=$_POST['alarm_new'];
	if(!is_array($mid)) redir();
	$_SESSION->temp_form=array("000_alarm_email"=>$_SESSION->user->u_email);
	redir(root()."alarmtab/edit/".key($mid)."/0");
    }
    if(get_ind($_POST,"alarm_multinew")) {
	$mids=get_ind($_POST,"meas_id");
	if(!is_array($mids) || !count($mids)) {
	    $_SESSION->error_text="Vyberte alespoň jeden měřící bod";
	    redir();
	}
	$rids=array();
	foreach($mids as $key=>$val) if($val=='Y') $rids[]=$key;
	if(!count($rids)) {
	    $_SESSION->error_text="Vyberte alespoň jeden měřící bod";
	    redir();
	}
	$_SESSION->temp_form=array("000_alarm_email"=>$_SESSION->user->u_email);
	redir(root()."alarmtab/edit/sess/".setmidskey($rids)."/0");
    }
    if(get_ind($_POST,"alarm_allnew")) {
	$rids=get_ind($_POST,"meas_hid");
	if(!is_array($rids) || !count($rids)) {
	    $_SESSION->error_text="Žádný měřící bod k dispozici";
	    redir();
	}
	$_SESSION->temp_form=array("000_alarm_email"=>$_SESSION->user->u_email);
	redir(root()."alarmtab/edit/sess/".setmidskey($rids)."/0");
    }
    if(get_ind($_POST,"alarm_remall")) {
	$rids=get_ind($_POST,"meas_hid");
	if(is_array($rids) && count($rids)) {
	    $aids=array();
	    foreach($rids as $val) $aids[]="\"".$SQL->escape($val)."\"";
	    if(urole()=='A') $SQL->query("delete from alarm where a_mid in (".implode(",",$aids).")");
	    else $SQL->query("delete from alarm where a_mid in (".implode(",",$aids).") && a_uid=".uid());
	}
	$_SESSION->error_text="Alarmy smazány";
	redir();
    }
    if(get_ind($_POST,"alarm_rem")) {
	$ids=get_ind($_POST,"000_alarm_id");
	if(is_array($ids)) { // check permissions
	    $aids=array();
	    foreach($ids as $key=>$val) {
		if($val=='Y') $aids[]="\"".$SQL->escape($key)."\"";
	    }
	    if(count($aids)) {
		if(urole()=='A') $SQL->query("delete from alarm where a_id in (".implode(",",$aids).")");
		else $SQL->query("delete from alarm where a_id in (".implode(",",$aids).") && a_uid=".uid());
	    }
	}
	$mids=get_ind($_POST,"meas_id");
	if(is_array($mids)) {
	    $aids=array();
	    foreach($mids as $key=>$val) {
		if($val=='Y') $aids[]="\"".$SQL->escape($key)."\"";
	    }
	    if(count($aids)) {
		if(urole()=='A') $SQL->query("delete from alarm where a_mid in (".implode(",",$aids).")");
		else $SQL->query("delete from alarm where a_mid in (".implode(",",$aids).") && a_uid=".uid());
	    }
	}
	$_SESSION->error_text="Alarmy smazány";
	redir();
    }
    redir();
}
