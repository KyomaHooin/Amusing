<?php

pageperm();
showmenu();

showerror();

ajaxsess();

$makecsv=false;
switch($ARGC) {
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
    echo csvline(array("#","Typ","Popis","Email","Data"));
    $qe=$SQL->query("select * from alarm_preset order by ap_desc,ap_class");
    while($fe=$qe->obj()) {
	$alrm=c_alarm_gen::getalarmbyname($fe->ap_class);
	echo csvline(array($fe->ap_id,c_alarm_gen::getdescbyname($fe->ap_class),$fe->ap_desc,$fe->ap_email,$alrm?$alrm->desc($fe->ap_data):"invalidní alarm"));
    }
    $csv=ob_get_contents();
    ob_end_clean();
    echo csvoutput($csv);
    
    exit();
}

echo "<form action=\"".root().$PAGE."\" method=\"post\">";

echo input_button("apreset_new","Nová definice","newbutton");

echo "<table>";

echo "<tr><th>#</th><th>Typ</th><th>Popis</th><th>Email</th><th>Data</th><th>&nbsp;</th></tr>";

$qe=$SQL->query("select * from alarm_preset order by ap_desc,ap_class");
while($fe=$qe->obj()) {
    echo "<tr><td>".$fe->ap_id."</td><td>".c_alarm_gen::getdescbyname($fe->ap_class)."</td>
	<td>".htmlspecialchars($fe->ap_desc)."</td>
	<td>".htmlspecialchars($fe->ap_email)."</td>
	<td>";
	$alrm=c_alarm_gen::getalarmbyname($fe->ap_class);
	if(!$alrm) echo "invalidní alarm";
	else echo $alrm->desc($fe->ap_data);
	echo "</td>
	<td>".input_button("apreset_edit[".$fe->ap_id."]","Editovat")." ".input_button("apreset_rem[".$fe->ap_id."]","Smazat")."</td></tr>";
}

echo "</table>";

echo "<br /><a href=\"".root().$PAGE."/csv\">Uložit jako csv</a>";

echo "<script type=\"text/javascript\">
// <![CDATA[
function alarmsgui() {
    $(\"button\").button();
}
// ]]>
</script>";
    $_JQUERY[]="alarmsgui();";

echo "</form>";

if($_SERVER['REQUEST_METHOD']=="POST") {
    $_SESSION->invalid=false;
    $_SESSION->temp_form=false;

    if(get_ind($_POST,"apreset_new")) {
	$_SESSION->temp_form=array("001_ap_email"=>$_SESSION->user->u_email);
	redir(root()."alarmpresettab/edit/0");
    }
    if(get_ind($_POST,"apreset_rem")) {
	$id=get_ind($_POST,"apreset_rem");
	if(is_array($id)) {
	    $key=(int)key($id);
	    if($key) {
		$SQL->query("update alarm set a_preset=0 where a_preset=".$key);
		$SQL->query("delete from alarm_preset where ap_id=".$key);
	    }
	}
	$_SESSION->error_text="Definice smazána";
	redir();
    }
    if(get_ind($_POST,"apreset_edit")) {
	$id=get_ind($_POST,"apreset_edit");
	if(is_array($id)) {
	    $key=(int)key($id);
	    if($key) {
		$qe=$SQL->query("select * from alarm_preset where ap_id=".$key);
		$fe=$qe->obj();
		if(!$fe) {
		    $_SESSION->error_text="Definice nenalezena";
		    redir();
		}
		$_SESSION->temp_form=array(
		    "001_ap_desc"=>$fe->ap_desc,
		    "001_ap_email"=>$fe->ap_email,
		    "001_ap_type"=>$fe->ap_class
		);
		$a=$fe->ap_class;
		$ac=new $a();
		$ac->loadtempform($_SESSION->temp_form,$fe->ap_data);
		redir(root()."alarmpresettab/edit/".$key);
	    }
	}
	redir();
    }
    redir();
}
