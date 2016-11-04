<?php

pageperm();
showmenu();

showerror();

ajaxsess();

if($ARGC!=2) redir(root()."buildings");
if($ARGV[0]!="edit" || !is_numeric($ARGV[1])) redir(root()."buildings");
$buildingedit=(int)$ARGV[1];
if($buildingedit) {
    $qe=$SQL->query("select * from building where b_id=".$buildingedit);
    $fe=$qe->obj();
    if(!$fe) redir(root()."buildings");
    $bimg=$fe->b_img;
} else $bimg=0;

function sredir() {
    global $PAGE;
    global $buildingedit;
    redir(root().$PAGE."/edit/".$buildingedit);
}

echo "<form action=\"".root().$PAGE."/edit/".$buildingedit."\" method=\"post\" enctype=\"multipart/form-data\">";

echo "<fieldset><legend>".($buildingedit?"Editace budovy":"Nová budova")."</legend>";
echo "<table class=\"nobr\">";
echo "<tr><td>Název:&nbsp;</td><td>".input_text_temp_err("001_build_name","finput")."</td></tr>";
echo "<tr><td>Ulice:&nbsp;</td><td>".input_text_temp_err("001_build_street","finput")."</td></tr>";
echo "<tr><td>Město:&nbsp;</td><td>".input_text_temp_err("001_build_city","finput");

$opts=array(0=>"již zadaná města");
$qe=$SQL->query("select * from building group by b_city order by b_city");
while($fe=$qe->obj()) $opts[]=$fe->b_city;
if(count($opts)>1) {
    echo " ".input_select("000_build_preset",$opts);
}

echo "</td></tr>";
echo "<tr><td>GPS:&nbsp;</td><td>".input_text_temp_err("000_build_gps","finput")."</td></tr>";
echo "<tr><td>Popis:&nbsp;</td><td>".input_area_temp("000_build_desc","farea")."</td></tr>";
echo "<tr><td>Url:&nbsp;</td><td>".input_text_temp_err("000_build_url","finput")."</td></tr>";
	
echo "<tr><td>Obrázek:&nbsp;</td><td>".input_file("build_picture");
if($bimg) echo " ".input_button("build_imgrem","Odebrat obrázek");
echo "</td></tr></table>";
	
echo input_button("build_save","Uložit")." ".input_button("build_cancel","Zpět");
echo "</fieldset>";

    echo "<script type=\"text/javascript\">
// <![CDATA[
function buildingsgui() {
    $(\"button\").button();
    $(\"#000_build_preset\").change(function() {
	if($(this).val()!='0') $(\"#001_build_city\").val($(\"#000_build_preset option:selected\").text());
    });
}
// ]]>
</script>";
    $_JQUERY[]="buildingsgui();";

echo "</form>";

if($_SERVER['REQUEST_METHOD']=="POST") {
    $_SESSION->invalid=false;

    if(get_ind($_POST,"build_imgrem")) {
	if(!$buildingedit) sredir();
	$SQL->query("delete from image where img_id=".$bimg);
	$SQL->query("update building set b_img=0 where b_id=".$buildingedit);
	sredir();
    }

    $_SESSION->temp_form=false;

    if(get_ind($_POST,"build_cancel")) {
	redir(root()."buildings");
    }
    if(get_ind($_POST,"build_save")) {
	$rerr=postcheck($ITEMS,$_POST);
	$url=get_ind($_POST,"000_build_url");
	if(strlen($url) && !preg_match("/^https?\\:\\/\\/.+$/",$url)) $rerr['000_build_url']="Neplatný formát url";
	$gps=get_ind($_POST,"000_build_gps");
	if(strlen($gps)) { // check and correct gps mark
	    if(!preg_match("/(\\-?\\d+\\.?\\d*)\\s*,\\s*(\\-?\\d+\\.?\\d*)$/",$gps,$mch)) $rerr['000_build_gps']="Neplatný formát gps";
	    else $gps=$mch[1].",".$mch[2]; // strip spaces
	}
	if(count($rerr)) {
	    $_SESSION->error_text=reset($rerr);
	    $_SESSION->invalid=$rerr;
	    $_SESSION->temp_form=$_POST;
	    sredir();
	}
	$image=get_ind($_FILES,"build_picture");
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
	if(!$buildingedit) {
	    $SQL->query("insert into building set
		b_name=\"".$SQL->escape(get_ind($_POST,"001_build_name"))."\",
		b_street=\"".$SQL->escape(get_ind($_POST,"001_build_street"))."\",
		b_city=\"".$SQL->escape(get_ind($_POST,"001_build_city"))."\",
		b_desc=\"".$SQL->escape(get_ind($_POST,"000_build_desc"))."\",
		b_gps=\"".$SQL->escape($gps)."\",
		b_url=\"".$SQL->escape($url)."\",
		b_img=".$imageid);
	    if($SQL->errnum) {
		$_SESSION->error_text="Chyba databáze";
		if($imageid) $SQL->query("delete from image where img_id=".$imageid);
	    } else $_SESSION->error_text="Budova uložena";
	} else {
	    $SQL->query("update building set
		b_name=\"".$SQL->escape(get_ind($_POST,"001_build_name"))."\",
		b_street=\"".$SQL->escape(get_ind($_POST,"001_build_street"))."\",
		b_city=\"".$SQL->escape(get_ind($_POST,"001_build_city"))."\",
		b_desc=\"".$SQL->escape(get_ind($_POST,"000_build_desc"))."\",
		b_gps=\"".$SQL->escape($gps)."\",
		b_url=\"".$SQL->escape($url)."\",
		b_img=".($imageid?$imageid:"b_img")."
		where b_id=".$buildingedit);
	    if($SQL->errnum) {
		$_SESSION->error_text="Chyba databáze";
		if($imageid) $SQL->query("delete from image where img_id=".$imageid);
	    } else {
		$_SESSION->error_text="Budova uložena";
		if($bimg && $imageid) $SQL->query("delete from image where img_id=".$bimg);
	    }
	}
	redir(root()."buildings");
    }
    sredir();
}
