<?php

pageperm();
showmenu();

showerror();

ajaxsess();

if($ARGC!=2) redir(root()."measpoints");
if($ARGV[0]!="edit" || !is_numeric($ARGV[1])) redir(root()."measpoints");
$measpointedit=(int)$ARGV[1];
if($measpointedit) {
    $qe=$SQL->query("select * from measuring where m_id=".$measpointedit);
    $fe=$qe->obj();
    if(!$fe) redir(root()."measpoints");
    $mimg=$fe->m_img;
} else $mimg=0;

echo "<form action=\"".root().$PAGE."/edit/".$measpointedit."\" method=\"post\" enctype=\"multipart/form-data\">";

	echo "<fieldset><legend>".($measpointedit?"Editace měřícího bodu":"Nový měřící bod")."</legend>";
	echo "<table class=\"nobr\">";
	
	$opts=array(0=>"Zvolte město");
	$qe=$SQL->query("select * from building group by b_city order by b_city");
	while($fe=$qe->obj()) {
	    $opts[bin2hex($fe->b_city)]=$fe->b_city;
	}
	echo "<tr><td>Město:&nbsp;</td><td>".input_select_temp_err("001_meas_city",$opts)."</td></tr>";
	$opts=array(0=>"Zvolte budovu");
	$sb=get_temp("001_meas_city");
	if($sb) {
	    $qe=$SQL->query("select * from building where b_city=\"".$SQL->escape(my_hex2bin($sb))."\" order by b_name");
	    while($fe=$qe->obj()) {
		$opts[$fe->b_id]=$fe->b_name;
	    }
	}
	echo "<tr><td>Budova:&nbsp;</td><td><span id=\"measbuildc\">".input_select_temp_err("001_ajax_build",$opts)."</span></td></tr>";
	$opts=array(0=>"Zvolte místnost");
	$sb=get_temp("001_ajax_build");
	if($sb) { // printout rooms
	    $qe=$SQL->query("select * from room where r_bid=\"".$SQL->escape($sb)."\" order by r_floor");
	    while($fe=$qe->obj()) {
		$opts[$fe->r_id]=$fe->r_desc;
	    }
	}
	echo "<tr><td>Místnost:&nbsp;</td><td><span id=\"measroomc\">".input_select_temp_err("001_ajax_room",$opts)."</span></td></tr>";
	
	$sopts=array(0=>"Zvolte senzor");
	$qe=$SQL->query("select * from sensor where s_mid=0 || s_mid=".$measpointedit." order by s_serial");
	while($fe=$qe->obj()) {
	    $sopts[$fe->s_id]=$fe->s_serial;
	}
	echo "<tr><td>Senzor:&nbsp;</td><td>".input_select_temp_err("001_meas_sensor",$sopts)."</td></tr>";
	
	echo "<tr><td>Oddělení:&nbsp;</td><td>".input_text_temp_err("000_meas_depart","finput")."</td></tr>";
	echo "<tr><td>Popis:&nbsp;</td><td>".input_text_temp_err("000_meas_desc","finput")."</td></tr>";

	// datetimepicker
	echo "<tr><td>Validní od:&nbsp;</td><td>".input_text_temp_err("000_meas_validfrom")."</td></tr>";
	echo "<tr><td>Validní do:&nbsp;</td><td>".input_text_temp_err("000_meas_validto")."</td></tr>";
	
	echo "<tr><td>Obrázek:&nbsp;</td><td>".input_file("meas_picture");
	if($mimg) echo " ".input_button("meas_imgrem","Odebrat obrázek");
	echo "</td></tr>";
	
	echo "<tr><td>Aktivní:&nbsp;</td><td>".input_check_temp("000_meas_state",'Y')."</td></tr>";
	echo "</table>";

	echo input_button("meas_save","Uložit")." ".input_button("meas_cancel","Zpět");
	echo "</fieldset>";
	
    echo "<script type=\"text/javascript\">
// <![CDATA[
function buildchange() {
    $.get(\"".root()."ajax/getroomsel/\"+$(\"#001_ajax_build\").val(),function(data) {
	$(\"#measroomc\").html(data);
    });
}
function buildsub() {
    $(\"#000_meas_validfrom\").datepicker({dateFormat: \"yy-mm-dd\", changeMonth: true, changeYear: true, yearRange: \"2000:2050\"});
    $(\"#000_meas_validto\").datepicker({dateFormat: \"yy-mm-dd\", changeMonth: true, changeYear: true, yearRange: \"2000:2050\"});
    
    $(\"#001_ajax_build\").change(buildchange);
    $(\"#001_meas_city\").change(function() {
	$.get(\"".root()."ajax/getbuildsel/\"+$(this).val(),function(data) {
	    $(\"#measbuildc\").html(data);
	    $(\"#001_ajax_build\").change(buildchange);
	    buildchange();
	});
    });
    $(\"#001_meas_build\").change(function() {
	$.get(\"".root()."ajax/getroomsel/\"+$(this).val(),function(data) {
	    $(\"#measroomc\").html(data);
	});
    });
}
function measgui() {
    $(\"button\").button();
}
// ]]>
</script>";
    $_JQUERY[]="buildsub();";
    $_JQUERY[]="measgui();";

echo "</form>";

function sredir() {
    global $PAGE;
    global $measpointedit;
    redir(root().$PAGE."/edit/".$measpointedit);
}

if($_SERVER['REQUEST_METHOD']=="POST") {
    $_SESSION->invalid=false;

    if(get_ind($_POST,"meas_imgrem")) {
	if(!$measpointedit) sredir();
	$SQL->query("delete from image where img_id=".$mimg);
	$SQL->query("update measuring set m_img=0 where m_id=".$measpointedit);
	sredir();
    }
    $_SESSION->temp_form=false;

    if(get_ind($_POST,"meas_cancel")) {
	redir(root()."measpoints");
    }
    if(get_ind($_POST,"meas_save")) {
	$rerr=postcheck($ITEMS,$_POST);
	$rb=get_ind($_POST,"001_ajax_room");
	if(!$rb) {
	    $rerr['001_ajax_room']="Zvolte místnost";
	} else {
	    $qe=$SQL->query("select * from room where r_id=\"".$SQL->escape($rb)."\"");
	    if(!$qe->rowcount()) $rerr['001_ajax_room']="Neexistující místnost";
	}

	$sfrom=gettime(get_ind($_POST,"000_meas_validfrom"));
	if($sfrom===false) $rerr['000_meas_validfrom']="Nastavte validní datum";
	$sto=trim(get_ind($_POST,"000_meas_validto"));
	if(!strlen($sto)) $sto="2050-12-31"; // distant future
	$sto=gettime($sto);
	if($sto===false) $rerr['000_meas_validto']="Nastavte validní datum";
	else $sto+=86399; // whole day

	if($sfrom!==false && $sto!==false && $sfrom>=$sto) {
	    $rerr['000_sen_validfrom']="Invalidní rosah";
	    $rerr['000_sen_validto']="Invalidní rosah";
	}

	if(count($rerr)) {
	    $_SESSION->error_text=reset($rerr);
	    $_SESSION->invalid=$rerr;
	    $_SESSION->temp_form=$_POST;
	    sredir();
	}
	$image=get_ind($_FILES,"meas_picture");
	$imageid=0;
	if(is_array($image) && !get_ind($image,"error")) {
	    $img=imagecreatefromjpeg(get_ind($image,"tmp_name"));
	    if(!$img) {
		$_SESSION->error_text="Obrázek není jpg";
		$_SESSION->temp_form=$_POST;
		sredir();
	    }
//	    resizeimg($img);
	    ob_start();
	    imagejpeg($img);
	    $imgraw=ob_get_clean();
	    $SQL->query("insert into image set
		img_w=".imagesx($img).",
		img_h=".imagesy($img).",
		img_data=\"".$SQL->escape($imgraw)."\"");
	    $imageid=$SQL->lastid();
	}
	$sid=(int)get_ind($_POST,"001_meas_sensor");
	$mid=false;
	if(!$measpointedit) {
	    $SQL->query("insert into measuring set
		m_rid=\"".$SQL->escape(get_ind($_POST,"001_ajax_room"))."\",
		m_desc=\"".$SQL->escape(get_ind($_POST,"000_meas_desc"))."\",
		m_depart=\"".$SQL->escape(get_ind($_POST,"000_meas_depart"))."\",
		m_validfrom=".(int)$sfrom.",
		m_validto=".(int)$sto.",
		m_active=\"".(get_ind($_POST,"000_meas_state")=='Y'?'Y':'N')."\",
		m_img=".$imageid);
	    if($SQL->errnum) {
		$_SESSION->error_text="Chyba databáze";
		if($imageid) $SQL->query("delete from image where img_id=".$imageid);
	    } else {
		$mid=$SQL->lastid();
		$_SESSION->error_text="Měřící bod uložen";
	    }
	} else {
	    $SQL->query("update measuring set
		m_rid=\"".$SQL->escape(get_ind($_POST,"001_ajax_room"))."\",
		m_desc=\"".$SQL->escape(get_ind($_POST,"000_meas_desc"))."\",
		m_depart=\"".$SQL->escape(get_ind($_POST,"000_meas_depart"))."\",
		m_validfrom=".(int)$sfrom.",
		m_validto=".(int)$sto.",
		m_active=\"".(get_ind($_POST,"000_meas_state")=='Y'?'Y':'N')."\",
		m_img=".($imageid?$imageid:"m_img")."
		where m_id=".$measpointedit);
	    if($SQL->errnum) {
		$_SESSION->error_text="Chyba databáze";
		if($imageid) $SQL->query("delete from image where img_id=".$imageid);
	    } else {
		$mid=$measpointedit;
		$_SESSION->error_text="Měřící bod uložen";
		if($mimg && $imageid) $SQL->query("delete from image where img_id=".$mimg);
	    }
	}
	if($mid) { // sensor set
	    $SQL->query("update sensor set s_mid=0 where s_mid=".$mid." && s_id!=".$sid);
	    $qe=$SQL->query("select * from sensor left join sensortype on s_type=st_id where s_id=".$sid);
	    $fe=$qe->obj();
	    if($fe && $fe->s_mid!=$mid) {
		$stt=new $fe->st_class();
		$stt->attach($fe);
		$SQL->query("update sensor set s_mid=".$mid." where s_id=".$sid);
	    }
	}
	redir(root()."measpoints");
    }
    sredir();
}
