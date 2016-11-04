<?php

pageperm();
showmenu();

showerror();

ajaxsess();

$makecsv=false;
switch($ARGC) {
case 2:
    switch($ARGV[0]) {
    case "page":
	$_SESSION->log_currpage=(int)$ARGV[1];
	break;
    }
    break;
case 1:
    switch($ARGV[0]) {
    case "csv":
	$makecsv=true;
	break;
    }
}

if($makecsv) {
    ob_clean();
    $_NOHEAD=true;
//    header("Content-type: text/plain");
    header("Content-type: text/x-csv");
    header("Content-Disposition: attachment; filename=".$PAGE.".csv");
    
    ob_start();
    echo csvline(array("datum","uživatel","událost"));
    $qe=$SQL->query("select *,if(isnull(u_id),\"system\",u_fullname) as fn from log left join user on l_uid=u_id order by l_date desc");
    while($fe=$qe->obj()) {
	echo csvline(array($fe->l_date,$fe->fn,$fe->l_text));
    }
    $csv=ob_get_contents();
    ob_end_clean();
    echo csvoutput($csv);
    
    exit();
}

ob_start();
echo "<table>
<tr><th>datum</th><th>uživatel</th><th>událost</th></tr>";

$offset=(int)($_SESSION->log_currpage*$_PERPAGE);
$limit=(int)$_PERPAGE;
$qe=$SQL->query("select SQL_CALC_FOUND_ROWS *,if(isnull(u_id),\"system\",u_fullname) as fn from log left join user on l_uid=u_id order by l_date desc limit ".$offset.",".$limit);
while($fe=$qe->obj()) {
    echo "<tr><td>".$fe->l_date."</td><td>".htmlspecialchars($fe->fn)."</td><td>".htmlspecialchars($fe->l_text)."</td></tr>";
}

echo "</table>";
$tbl=ob_get_clean();

$qe=$SQL->query("select FOUND_ROWS() as rows");
$fe=$qe->obj();
$totalrows=$fe->rows;
if($totalrows) pages($totalrows,$_SESSION->log_currpage,"<a href=\"".root().$PAGE."/page/%d\">%d</a>");
echo $tbl;
if($totalrows) pages($totalrows,$_SESSION->log_currpage,"<a href=\"".root().$PAGE."/page/%d\">%d</a>");

echo "<br /><a href=\"".root().$PAGE."/csv\">Uložit jako csv</a>";

echo "<script type=\"text/javascript\">
// <![CDATA[
function loggui() {
    $(\".pagep a\").button();
    $(\".pagep b\").button({disabled:true});
}
// ]]>
</script>";
    $_JQUERY[]="loggui();";

if($_SERVER['REQUEST_METHOD']=="POST") {
    $_SESSION->invalid=false;
    $_SESSION->temp_form=false;
    redir();
}
