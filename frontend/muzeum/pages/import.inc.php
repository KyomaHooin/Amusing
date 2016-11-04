<?php

pageperm();
showmenu();

showerror();

ajaxsess();

if($ARGC<1) redir(root()."main");

function sredir() {
    global $PAGE;
    global $ARGV;
    redir(root().$PAGE."/".$ARGV[0]);
}
function backredir($e=false) {
    global $_DEFAULTPAGE;
    if($e) $_SESSION->error_text=$e;
    if(strlen($_SESSION->prevpage)) redir(root().$_SESSION->prevpage);
    redir(root().$_DEFAULTPAGE);
}

$qe=$SQL->query("select * from sensor left join sensortype on s_type=st_id left join measuring on s_mid=m_id left join room on m_rid=r_id left join building on r_bid=b_id where s_id=\"".$SQL->escape($ARGV[0])."\"");
$_sensor_data=$qe->obj();
if(!$_sensor_data) backredir("Senzor nenalezen");
if(!$_sensor_data->m_id) backredir("Senzor nemá měřící bod");
if($_sensor_data->s_active!='Y') backredir("Senzor není aktivní");
if($_sensor_data->m_active!='Y') backredir("Měřící bod není aktivní");

switch(urole()) {
case 'A':
case 'D':
    break;
default:
    $acc=$SQL->query("select * from permission where pe_type='I' && pe_mid=".$_sensor_data->m_id." && (pe_uid=0 || pe_uid=".uid().")");
    if(!$acc->rowcount()) backredir("Nemáte oprávnění");
    break;
}

//$ct=time();
//if($ct<$_sensor_data->m_validfrom || $ct>$_sensor_data->m_validto) backredir("Měřící bod není v současné době aktivní");

echo "<table class=\"nobr\">";
echo "<tr><td>Senzor:&nbsp;</td><td>".htmlspecialchars($_sensor_data->s_serial." ".$_sensor_data->s_desc)."</td></tr>";
echo "<tr><td>Měřící bod:&nbsp;</td><td>".htmlspecialchars($_sensor_data->m_desc)."</td></tr>";
echo "<tr><td>Platnost:&nbsp;</td><td>od ".showdate($_sensor_data->m_validfrom)." do ".showdate($_sensor_data->m_validto)."</td></tr>";
echo "<tr><td>Místnost:&nbsp;</td><td>".htmlspecialchars($_sensor_data->r_floor." ".$_sensor_data->r_desc)."</td></tr>";
echo "<tr><td>Budova:&nbsp;</td><td>".htmlspecialchars($_sensor_data->b_name." ".$_sensor_data->b_city)."</td></tr>";
echo "</table>";

// handle and create dst offset in case of ignore
$_sensor_dstoff=0;
if($_sensor_data->s_ignoredst=='Y') {
    try {
    // a bit hardcoded...
	$_sensor_dstoff=-timezone_offset_get(new DateTimeZone($_sensor_data->s_timezone),new DateTime("2015-01-01 01:00:00",new DateTimeZone($_sensor_data->s_timezone)));
    } catch(Exception $e) {
    } // really ?
}

echo "<script type=\"text/javascript\">
// <![CDATA[
function importgui() {
    $(\"button\").button();
}
// ]]>
</script>";
$_JQUERY[]="importgui();";

if($_sensor_data->st_class) {
    $stt=new $_sensor_data->st_class();
    $stt->fe=$_sensor_data;
    if(!$stt->imprequire()) echo "Neplatná třída senzoru";
    else require_once $stt->imprequire();
} else "Neplatná třída senzoru";
