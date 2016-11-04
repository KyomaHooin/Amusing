<?php

function print_read($v) {
    echo "<pre>";
    print_r($v);
    echo "</pre>";
}

function cleanout() {
    for($i=ob_get_level();$i--;) ob_end_clean();
}

function redir($where=false) {
    global $PAGE;

    cleanout();
//    if($where===false) header("Location: ".$_SERVER['REQUEST_URI']);
    if($where===false) header("Location: ".root().$PAGE);
    else header("Location: ".$where);
    exit();
}

function get_var($var) {
    global $$var;
    return isset($$var)?$$var:false;
}

function get_ind(&$arr,$ind) {
    if(!is_array($arr)) return false;
    return isset($arr[$ind])?$arr[$ind]:false;
}

function showerror() {
    if($_SESSION->error_text) {
	echo "<p class=\"showerr\">".$_SESSION->error_text."</p>";
	$_SESSION->error_text=false;
    }
}

function root() {
    global $_ROOTPATH;
    return $_ROOTPATH;
}

function add_item($name) {
    global $ITEMS;
    $ITEMS[]=$name;
    $ITEMS=array_unique($ITEMS);
}

function label($name,$text) {
    return "<label for=\"".htmlspecialchars($name)."\">".$text."</label>";
}

function makeclass($cl) {
    if($cl===false) $cl=array();
    else if(!is_array($cl)) $cl=array($cl);
    if(!count($cl)) return "";
    return " class=\"".implode(" ",$cl)."\"";
}

function addclass($cl,$ca) {
    if($cl===false) return array($ca);
    if(is_array($cl)) {
	$cl[]=$ca;
	return $cl;
    }
    return array($cl,$ca);
}

function input_check($name,$val='Y',$ch=false,$cl=false,$dis=false) {
    add_item($name);
    $cl=makeclass($cl);
    return "<input type=\"checkbox\" id=\"".htmlspecialchars($name)."\" name=\"".htmlspecialchars($name)."\" value=\"".htmlspecialchars($val)."\"".($ch?" checked":"").$cl.($dis?" disabled":"")." />";
}

function input_check_temp($name,$val='Y',$cl=false,$dis=false) {
    return input_check($name,$val,get_temp($name)==$val,$cl,$dis);
}

function input_check_temp_err($name,$val='Y',$cl=false,$dis=false) {
    if(get_ind($_SESSION->invalid,$name)) $cl=addclass($cl,"herr");
    return input_check($name,$val,get_temp($name)==$val,$cl,$dis);
}

function input_radio($name,$val,$ch=false,$cl=false,$dis=false,$js=false) {
    add_item($name);
    $cl=makeclass($cl);
    if($js===false) $js="";
    else $js=" ".$js;
    if($ch===false) $ch="";
    else if($ch===true) $ch=" checked";
    else if($ch==$val) $ch=" checked";
    return "<input".$cl." type=\"radio\" id=\"".htmlspecialchars($name."_val".$val)."\" name=\"".htmlspecialchars($name)."\" value=\"".htmlspecialchars($val)."\"".$ch.$js." />";
}

function input_radio_temp($name,$val,$cl=false,$dis=false,$js=false) {
    return input_radio($name,$val,get_temp($name)===$val,$cl,$dis,$js);
}

function input_radio_temp_err($name,$val,$cl=false,$dis=false,$js=false) {
    if(get_ind($_SESSION->invalid,$name)) $cl=addclass($cl,"herr");
    return input_radio_temp($name,$val,$cl,$dis,$js);
}

function input_hidden($name,$val,$id=false) {
    return "<input type=\"hidden\"".($id===false?"":" id=\"".htmlspecialchars($id)."\"")." name=\"".htmlspecialchars($name)."\" value=\"".htmlspecialchars($val)."\" />";
}

function input_text($name,$val=false,$cl=false,$title=false) {
    add_item($name);
    $cl=makeclass($cl);
    if($title) $title=" title=\"".htmlspecialchars($title)."\"";
    else $title="";
    return "<input".$title." type=\"text\" id=\"".htmlspecialchars($name)."\" name=\"".htmlspecialchars($name)."\" value=\"".htmlspecialchars($val)."\"".$cl." />";
}

function input_text_temp($name,$cl=false,$title=false) {
    return input_text($name,get_temp($name),$cl,$title);
}

function input_text_temp_err($name,$cl=false) {
    if(get_ind($_SESSION->invalid,$name)) {
	return input_text_temp($name,addclass($cl,"herr"),detag(get_ind($_SESSION->invalid,$name)));
    }
    return input_text_temp($name,$cl);
}

function input_text_err($name,$val=false,$cl=false) {
    if(get_ind($_SESSION->invalid,$name)) $cl=addclass($cl,"herr");
    return input_text($name,$val,$cl);
}

function input_passwd($name,$cl=false) {
    add_item($name);
    $cl=makeclass($cl);
    return "<input type=\"password\" id=\"".htmlspecialchars($name)."\" name=\"".htmlspecialchars($name)."\" value=\"\"".$cl." />";
}

function input_passwd_err($name,$cl=false) {
    if(get_ind($_SESSION->invalid,$name)) {
	return input_passwd($name,addclass($cl,"herr"));
    }
    return input_passwd($name,$cl);
}

function input_submit($name,$val,$cl=false,$js=false) {
    add_item($name);
    $cl=makeclass($cl);
    if($js===false) $js="";
    else $js=" ".$js;
    return "<input type=\"submit\" id=\"".htmlspecialchars($name)."\" name=\"".htmlspecialchars($name)."\" value=\"".htmlspecialchars($val)."\"".$cl.$js." />";
}

function input_button($name,$val,$cl=false,$js=false) {
    add_item($name);
    $cl=makeclass($cl);
    if($js===false) $js="";
    else $js=" ".$js;
    return "<button type=\"submit\" id=\"".htmlspecialchars($name)."\" name=\"".htmlspecialchars($name)."\"".$cl.$js." value=\"val\">".htmlspecialchars($val)."</button>";
}

function input_area($name,$val=false,$cl=false,$title=false) {
    add_item($name);
    $cl=makeclass($cl);
    if($title) $title=" title=\"".htmlspecialchars($title)."\"";
    else $title="";
    return "<textarea".$title." id=\"".htmlspecialchars($name)."\" name=\"".htmlspecialchars($name)."\"".$cl.">".htmlspecialchars($val)."</textarea>";
}

function input_area_temp($name,$cl=false,$title=false) {
    return input_area($name,get_temp($name),$cl,$title);
}

function input_area_temp_err($name,$cl=false) {
    if(get_ind($_SESSION->invalid,$name)) {
	return input_area_temp($name,addclass($cl,"herr"),detag(get_ind($_SESSION->invalid,$name)));
    }
    return input_area_temp($name,$cl);
}

function input_select($name,$opts,$val=false,$mul=false,$cl=false,$title=false) {
    add_item($name);
    $cl=makeclass($cl);
    if($mul===false) $mul="";
    else $mul=" multiple";
    if($val===false) $val=array();
    else if(!is_array($val)) $val=array($val);
    if($title) $title=" title=\"".htmlspecialchars($title)."\"";
    else $title="";
    $ret="<select".$title." id=\"".htmlspecialchars($name)."\" name=\"".htmlspecialchars($name)."\"".$mul.$cl.">";
    foreach($opts as $key=>$fval) {
	if(is_array($fval)) {
	    if(get_ind($fval,"html") && $fval['html']) $vval=$fval['val'];
	    else $vval=htmlspecialchars($fval['val']);
	} else $vval=htmlspecialchars($fval);
	$ret.="<option value=\"".htmlspecialchars($key)."\"".(in_array($key,$val)?" selected":"").">".$vval."</option>";
    }
    return $ret."</select>";
}

function input_select_temp($name,$opts,$mul=false,$cl=false,$title=false) {
    return input_select($name,$opts,get_temp($name),$mul,$cl,$title);
}

function input_select_temp_err($name,$opts,$mul=false,$cl=false) {
    if(get_ind($_SESSION->invalid,$name)) {
	return input_select_temp($name,$opts,$mul,addclass($cl,"herr"),detag(get_ind($_SESSION->invalid,$name)));
    }
    return input_select_temp($name,$opts,$mul,$cl);
}

function input_file($name,$cl=false) {
    $cl=makeclass($cl);
    return "<input id=\"".htmlspecialchars($name)."\" type=\"file\" name=\"".htmlspecialchars($name)."\"".$cl." />";
}

function detag($src) {
    $ret=preg_replace("/\&nbsp;/"," ",$src);
    $ret=preg_replace("/\<br\s*\/?\s*\>/"," ",$ret);
    $ret=preg_replace("/\<[^\>]*\>/","",$ret);
    return html_entity_decode($ret,ENT_QUOTES);
}

function destyle($src) {
    $ret=preg_replace("/\<\s*font[^\>]*\>/","",$src);
    $ret=preg_replace("/\<\s*\/\s*font\s*[^\>]*\>/","",$ret);
    $ret=preg_replace("/\<\s*basefont[^\>]*\>/","",$ret);
    $ret=preg_replace("/\<\s*\/\s*basefont\s*[^\>]*\>/","",$ret);
    do {
	$cmp=$ret;
	$ret=preg_replace("/\<([^\>]*)style\s*\=\s*\"[^\"]*\"\s*([^\>]*)\>/","<$1$2>",$ret);
	$ret=preg_replace("/\<([^\>]*)class\s*\=\s*\"[^\"]*\"\s*([^\>]*)\>/","<$1$2>",$ret);
    } while($cmp!=$ret);
    do {
	$cmp=$ret;
	$ret=preg_replace("/\<([^\>]*)\s\>/","<$1>",$ret);
    } while($cmp!=$ret);
    return $ret;
}

function detag2($src,$tags) {
    if(!is_array($tags)) return $src;
    $ret=$src;
    foreach($tags as $val) {
	$ret=preg_replace("/\<\s*".preg_quote($val,'/')."(\s*\>|\s+[^\>]*\>)/","",$ret);
	$ret=preg_replace("/\<\s*\/\s*".preg_quote($val,'/')."(\s*\>|\s+[^\>]*\>)/","",$ret);
	$ret=preg_replace("/\<\s*".preg_quote($val,'/')."(\s*\/\s*\>|\s+[^\>\/]*\/\s*\>)/","",$ret);
    }
    return $ret;
}

function detagdef($src) {
    return detag2($src,array("a","code","div","span","br"));
}

function urlcode($src) {
    $src=iconv("UTF-8","ISO-8859-2//IGNORE",$src);
    $src=strtr($src,iconv("UTF-8","ISO-8859-2//TRANSLIT","ěščřžýáíéúůňďťöäëüĚŠČŘŽÝÁÍÉÚŮŇĎŤÖÄËÜ "),"escrzyaieuundtoaeuESCRZYAIEUUNDTOAEU_");
    return ($src);
}

function utf8_ascii($src) {
    $src=iconv("UTF-8","ISO-8859-2//IGNORE",$src);
    $src=strtr($src,iconv("UTF-8","ISO-8859-2//TRANSLIT","ěščřžýáíéúůňďťöäëüĚŠČŘŽÝÁÍÉÚŮŇĎŤÖÄËÜ"),"escrzyaieuundtoaeuESCRZYAIEUUNDTOAEU");
    return $src;
}

function postcheck(&$items,&$posted) {
    $rerr=array();
    foreach($items as $val) { // takze projedu pole, podle kodu to checknu tak bo tak
	$kod=substr($val,0,4);
	switch($kod) {
	case "000_":
	    if(get_ind($posted,$val)===false) break;
	    $posted[$val]=trim($posted[$val]);
	    break;
	case "001_":
	    if(get_ind($posted,$val)===false) {
		$rerr[$val]="<b>Nevyplněné povinné položky.</b>";
		break;
	    }
	    $posted[$val]=trim($posted[$val]);
	    if(!strlen($posted[$val])) $rerr[$val]="<b>Nevyplněné povinné položky.</b>";
	    break;
	case "002_":
	    if(get_ind($posted,$val)===false || !strlen(trim($posted[$val]))) {
		$rerr[$val]="<b>Nevyplněné povinné položky.</b>";
		break;
	    }
	    $posted[$val]=trim($posted[$val]);
	    if(!is_numeric($posted[$val])) $rerr[$val]="<b>Nesprávně vyplněné položky.</b>";
	    break;
	case "003_":
	    if(get_ind($posted,$val)===false || !strlen(trim($posted[$val]))) {
		$rerr[$val]="<b>Nevyplněné povinné položky.</b>";
		break;
	    }
	    $posted[$val]=trim($posted[$val]);
	    if(!preg_match('/^[a-zA-Z0-9\.\-_]+@[a-zA-Z0-9\.\-_]+\.[a-zA-Z]+$/',$posted[$val])) $rerr[$val]="<b>Nesprávně vyplněné položky.</b>";
	    break;
	case "004_":
	    if(get_ind($posted,$val)===false) break;
	    $posted[$val]=trim($posted[$val]);
	    if(!strlen($posted[$val])) break;
	    if(!preg_match('/^[a-zA-Z0-9\.\-_]+@[a-zA-Z0-9\.\-_]+\.[a-zA-Z]+$/',$posted[$val])) $rerr[$val]="<b>Nesprávně vyplněné položky.</b>";
	    break;
	case "005_":
	    if(get_ind($posted,$val)===false) break;
	    $posted[$val]=trim($posted[$val]);
	    if(!strlen($posted[$val])) break;
	    if(!is_numeric($posted[$val])) $rerr[$val]="<b>Nesprávně vyplněné položky.</b>";
	    break;
//	default:
//	    $rerr[$val]="Unknown";
	}
    }
    return $rerr;
}

function get_temp($ind) {
    return get_ind($_SESSION->temp_form,$ind);
}

function get_post($ind) {
    return get_ind($_POST,$ind);
}

function ajaxsess() {
    global $_JQUERY;
    if($_SERVER['REQUEST_METHOD']=="POST") return "";

    echo "<script type=\"text/javascript\">
// <![CDATA[

function ajaxsess() {
$.get('".root()."sess',function() {});
setTimeout(\"ajaxsess()\",60000);
}
// ]]>
</script>";
    $_JQUERY[]="ajaxsess();";
}
