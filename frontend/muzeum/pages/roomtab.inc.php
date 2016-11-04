<?php

pageperm();
showmenu();

showerror();

ajaxsess();

if($ARGC!=2) redir(root()."rooms");
if($ARGV[0]!="edit" || !is_numeric($ARGV[1])) redir(root()."rooms");
$roomedit=(int)$ARGV[1];
if($roomedit) {
    $qe=$SQL->query("select * from room where r_id=".$roomedit);
    $fe=$qe->obj();
    if(!$fe) redir(root()."rooms");
    $rimg=$fe->r_img;
} else $rimg=0;

echo "<form action=\"".root().$PAGE."/edit/".$roomedit."\" method=\"post\" enctype=\"multipart/form-data\">";

echo "<fieldset><legend>".($roomedit?"Editace místnosti":"Nová místnost")."</legend>";
echo "<table class=\"nobr\">";

$opts=array(0=>"Zvolte město");
$qe=$SQL->query("select * from building group by b_city order by b_city");
while($fe=$qe->obj()) {
    $opts[bin2hex($fe->b_city)]=$fe->b_city;
}
if(count($opts)<2) {
    $_SESSION->error_text="Není definovaná budova";
    redir(root()."buildings");
}
echo "<tr><td>Město:&nbsp;</td><td>".input_select_temp_err("001_room_city",$opts)."</td></tr>";
$opts=array(0=>"Zvolte budovu");
$sb=get_temp("001_room_city");
if($sb) {
    $qe=$SQL->query("select * from building where b_city=\"".$SQL->escape(my_hex2bin($sb))."\" order by b_name");
    while($fe=$qe->obj()) {
	$opts[$fe->b_id]=$fe->b_name;
    }
}
echo "<tr><td>Budova:&nbsp;</td><td><span id=\"measbuildc\">".input_select_temp_err("001_ajax_build",$opts)."</span></td></tr>";
echo "<tr><td>Patro:&nbsp;</td><td>".input_text_temp_err("001_room_floor","finput")."</td></tr>";
echo "<tr><td>Označení:&nbsp;</td><td>".input_text_temp_err("001_room_desc","finput")."</td></tr>";
echo "<tr><td>Popis:&nbsp;</td><td>".input_text_temp_err("000_room_note","finput")."</td></tr>";
	
echo "<tr><td>Materiály:</td><td>";
$qe=$SQL->query("select * from material order by ma_desc");
while($fe=$qe->obj()) {
    echo "&nbsp;".input_check_temp("000_room_mat_".$fe->ma_id)." ".htmlspecialchars($fe->ma_desc)."<br />";
}
echo "</td></tr>";

echo "<tr><td>Obrázek:&nbsp;</td><td>".input_file("room_picture");
if($rimg) echo " ".input_button("room_imgrem","Odebrat obrázek");
echo "</td></tr>";

echo "</table>";

echo input_button("room_save","Uložit")." ".input_button("room_cancel","Storno");
echo "</fieldset>";

echo "<script type=\"text/javascript\">
// <![CDATA[
function roomsgui() {
    $(\"button\").button();
    $(\"#001_room_city\").change(function() {
	$.get(\"".root()."ajax/getbuildsel/\"+$(this).val(),function(data) {
	    $(\"#measbuildc\").html(data);
	});
    });
}
// ]]>
</script>";
    $_JQUERY[]="roomsgui();";

echo "</form>";

function sredir() {
    global $PAGE;
    global $roomedit;
    redir(root().$PAGE."/edit/".$roomedit);
}

if($_SERVER['REQUEST_METHOD']=="POST") {
    $_SESSION->invalid=false;

    if(get_ind($_POST,"room_imgrem")) {
	if(!$roomedit) sredir();
	$SQL->query("delete from image where img_id=".$rimg);
	$SQL->query("update room set r_img=0 where r_id=".$roomedit);
	sredir();
    }
    $_SESSION->temp_form=false;

    if(get_ind($_POST,"room_cancel")) {
	redir(root()."rooms");
    }
    if(get_ind($_POST,"room_save")) {
	$rerr=postcheck($ITEMS,$_POST);
	$bb=get_ind($_POST,"001_ajax_build");
	if($bb) {
	    $qe=$SQL->query("select * from building where b_id=\"".$SQL->escape($bb)."\"");
	    if(!$qe->rowcount()) $rerr['001_ajax_build']="Neexistující budova";
	} else $rerr['001_ajax_build']="Zvolte budovu";
	if(count($rerr)) {
	    $_SESSION->error_text=reset($rerr);
	    $_SESSION->invalid=$rerr;
	    $_SESSION->temp_form=$_POST;
	    sredir();
	}
	$image=get_ind($_FILES,"room_picture");
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

	$sv=false;
	if(!$roomedit) {
	    $SQL->query("insert into room set
		r_bid=\"".$SQL->escape(get_ind($_POST,"001_ajax_build"))."\",
		r_floor=\"".$SQL->escape(get_ind($_POST,"001_room_floor"))."\",
		r_desc=\"".$SQL->escape(get_ind($_POST,"001_room_desc"))."\",
		r_note=\"".$SQL->escape(get_ind($_POST,"000_room_note"))."\",
		r_img=".$imageid);
	    if($SQL->errnum) {
		$_SESSION->error_text="Chyba databáze";
		if($imageid) $SQL->query("delete from image where img_id=".$imageid);
	    } else {
		$_SESSION->error_text="Místnost uložena";
		$sv=$SQL->lastid();
	    }
	} else {
	    $SQL->query("update room set
		r_bid=\"".$SQL->escape(get_ind($_POST,"001_ajax_build"))."\",
		r_floor=\"".$SQL->escape(get_ind($_POST,"001_room_floor"))."\",
		r_desc=\"".$SQL->escape(get_ind($_POST,"001_room_desc"))."\",
		r_note=\"".$SQL->escape(get_ind($_POST,"000_room_note"))."\",
		r_img=".$imageid."
		where r_id=".$roomedit);
	    if($SQL->errnum) {
		$_SESSION->error_text="Chyba databáze";
		if($imageid) $SQL->query("delete from image where img_id=".$imageid);
	    } else {
		$_SESSION->error_text="Místnost uložena";
		$sv=$roomedit;
		if($rimg && $imageid) $SQL->query("delete from image where img_id=".$rimg);
	    }
	}
	if($sv) { // saving materials
	    $SQL->query("delete from roommat where rm_rid=".$sv);
	    $qe=$SQL->query("select * from material");
	    while($fe=$qe->obj()) {
		if(get_ind($_POST,"000_room_mat_".$fe->ma_id)) $SQL->query("insert into roommat set rm_rid=".$sv.",rm_mid=".$fe->ma_id);
	    }
	}
	redir(root()."rooms");
    }
    sredir();
}
