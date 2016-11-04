<?php

pageperm();
showmenu();

showerror();

ajaxsess();

function treespan($id,$txt,$pt=false,$cl=false) {
    if($pt) $pt=" style=\"cursor:pointer;\"";
    else $pt="";
    if($cl) $cl=" class=\"".$cl."\"";
    else $cl="";
    return "<span".$cl." id=\"".htmlspecialchars($id)."\"".$pt.">".htmlspecialchars($txt)."</span>";
}

function generatetree() {
    global $SQL;
    global $_JQUERY;

    $qe=$SQL->query("select count(*) as cnt from measuring");
    $fe=$qe->obj();
    if(!$fe->cnt) return;
    
    $checks=array();
    
    if(is_array($_SESSION->datatogen_mids)) {
	foreach($_SESSION->datatogen_mids as $val) $checks[$val]=true;
    }
    
    echo "<nobr>".input_button("tree_collapse","Sbalit vše").input_button("tree_expand","Rozbalit vše").input_button("tree_clear","Vymazat výběr")."</nobr>";
    $opts=array('0'=>"Všechna oddělení");
    $qe=$SQL->query("select m_depart from measuring group by m_depart order by m_depart");
    while($fe=$qe->obj()) {
	if(!strlen($fe->m_depart)) continue;
	$opts[bin2hex($fe->m_depart)]=$fe->m_depart;
    }
    if(count($opts)>1) echo "<br /><br />".input_select_temp("maintree_depsel",$opts);
    echo "<br />".input_check_temp("maintree_showdea")." ".label("maintree_showdea","zobrazit neaktivní")."<br />";
    echo "<hr />";
    echo "<div id=\"treeenv\">";
    echo "<ul id=\"maintree\">";
    $cqe=$SQL->query("select * from building group by b_city order by b_city");
    while($cfe=$cqe->obj()) {
	$checkcnt=0;
	ob_start();
	echo "<li style=\"padding-left:0px;\">".treespan(bin2hex($cfe->b_city),$cfe->b_city,true);
	$bqe=$SQL->query("select * from building where b_city=\"".$SQL->escape($cfe->b_city)."\" order by b_name");
	echo "<ul style=\"display:none\">";
	while($bfe=$bqe->obj()) {
	    $bchecks=$checkcnt;
	    ob_start();
	    $rqe=$SQL->query("select * from room where r_bid=".$bfe->b_id." order by r_desc");
	    if($rqe->rowcount()) {
		echo "<li>".treespan("bid_".$bfe->b_id,$bfe->b_name,true);
		echo "<ul style=\"display:none\">";
		while($rfe=$rqe->obj()) {
		    $rchecks=$checkcnt;
		    ob_start();
		    switch(urole()) {
		    case 'A':
		    case 'D':
			$mqe=$SQL->query("select * from measuring where m_rid=".$rfe->r_id." order by m_desc");
			break;
		    default:
			$mqe=$SQL->query("select * from measuring left join permission on m_id=pe_mid where (pe_uid=0 || pe_uid=".uid().") && m_rid=".$rfe->r_id." group by m_id order by m_desc");
		    }
		    if($mqe->rowcount()) {
		//	echo "<li>".treespan("rid_".$rfe->r_id,$rfe->r_desc." ".$rfe->r_floor,false,"notg");
		//	echo "<li>".treespan("rid_".$rfe->r_id,$rfe->r_desc,false,"notg");
		//	echo "<ul class=\"notg\">";
			while($mfe=$mqe->obj()) {
			    $checkcnt++;
		//	    echo "<li>".input_check("mid_".$mfe->m_id,'Y',get_ind($checks,$mfe->m_id))."<label for=\"mid_".$mfe->m_id."\" style=\"cursor:pointer;\">".htmlspecialchars($mfe->m_desc." ".$mfe->m_depart)."</label></li>";
		//	    echo "<li>".$rfe->r_desc." ".input_check("mid_".$mfe->m_id,'Y',get_ind($checks,$mfe->m_id))."<label for=\"mid_".$mfe->m_id."\" style=\"cursor:pointer;\">".htmlspecialchars($mfe->m_desc)."</label></li>";
			    echo "<li>".input_check("mid_".$mfe->m_id,'Y',get_ind($checks,$mfe->m_id)).$rfe->r_desc." "."<label for=\"mid_".$mfe->m_id."\" style=\"cursor:pointer;\">".htmlspecialchars($mfe->m_desc)."</label></li>";
			}
		//	echo "</ul>";
		    } else {
			echo "<li>".treespan("rid_".$rfe->r_id,$rfe->r_desc." ".$rfe->r_floor)."</li>";
		    }
		    //echo "</li>";
		    if($rchecks==$checkcnt) ob_end_clean();
		    else ob_end_flush();
		}
		echo "</ul>";
	    } else {
		echo "<li>".treespan("bid_".$bfe->b_id,$bfe->b_name);
	    }
	    echo "</li>";
	    if($bchecks==$checkcnt) ob_end_clean();
	    else ob_end_flush();
	}
	echo "</ul></li>";
	if($checkcnt) ob_end_flush();
	else ob_end_clean();
    }
    echo "</ul>";
    echo "</div>";

    echo "<script type=\"text/javascript\">
// <![CDATA[
var lasth=[];
var lastc=[];
function showgraph(data) {
    $(\"#mainresult\").html(data.graph);
    $(\"#mvarscheck span\").css(\"text-decoration\",\"none\");
    for(i=0,l=data.uvars.length;i<l;i++) {
	$(data.uvars[i]).css(\"text-decoration\",\"underline\");
    }
    for(i=0,l=data.checks.length;i<l;i++) {
	var cn=data.checks[i];
	$(cn).prop('checked',true);
	$(cn).parent().parent().siblings('span').addClass('treehigh');
	$(cn).parent().parent().parent().parent().siblings('span').addClass('treehigh');
	$(cn).parent().parent().parent().parent().parent().parent().siblings('span').addClass('treehigh');
	$(cn).parent().parent().show();
	$(cn).parent().parent().parent().parent().show();
	$(cn).parent().parent().parent().parent().parent().parent().show();
    }
    for(i=0,l=lastc.length;i<l;i++) {
	if(data.checks.indexOf(lastc[i])<0) {
	    $(lastc[i]).prop('checked',false);
	    $(lastc[i]).parents('li').each(function() {
		if(!$(this).find(':checked').length) {
		    $(this).children('span').removeClass('treehigh');
		    return true;
		}
		return false;
	    });
	}
    }
    lastc=data.checks;
    
// later do some array intersection
    for(i=0,l=lasth.length;i<l;i++) {
	if(data.tohid.indexOf(lasth[i])<0) $(lasth[i]).parent().show();
    }
    lasth=data.tohid;
    for(i=0,l=lasth.length;i<l;i++) {
	$(lasth[i]).parent().hide();
    }
    
// range set
    if(data.settimerange) {
	$('#001_main_from').val(data.rangefrom[0]);
	$('#001_main_from_h').val(data.rangefrom[1]);
	$('#001_main_from_m').val(data.rangefrom[2]);
	$('#001_main_to').val(data.rangeto[0]);
	$('#001_main_to_h').val(data.rangeto[1]);
	$('#001_main_to_m').val(data.rangeto[2]);
    }
}
function guienable(en) {
    var e=en?'enable':'disable';
    $(\"#maintree input\").prop('disabled',!en);
    $(\"#tree_clear\").button(e);
    $(\"#main_apply\").button(e);
    $(\"#timeset\").buttonset(e);
    $(\"#maintree_showdea\").prop('disabled',!en);
    $(\"#maintree_depsel\").prop('disabled',!en);
    $(\"#main_uprfsel\").prop('disabled',!en);
    $(\"#main_uprfrem\").button(e);
    $(\"#main_uprfcrt\").button(e);
}
var working=false;
function postgraph(uri) {
    if(working) return false;
    working=true;
    var serdata=$(\"#main_form\").serializeArray();
    guienable(false);
    $(\"#mainresult\").html('generuji...');
    $.post(\"".root()."ajaxmain\"+uri,serdata,function(data) {
	showgraph(data);
    }).fail(function(jqXHR,textStatus,errorThrown) {
	$(\"#mainresult\").html('error !!! '+textStatus);
    }).always(function() {
	working=false;
	guienable(true);
    });
    return true;
}
function synctree(uri) {
    if(working) {
	alert('Neočekavaná chyba');
	return;
    }
    working=true;
    guienable(false);
    $(\"#mainresult\").html('generuji...');
    $.get(\"".root()."ajaxmain\"+uri,function(data) {
	showgraph(data);
    }).fail(function(jqXHR,textStatus,errorThrown) {
	$(\"#mainresult\").html('error !!! '+textStatus);
    }).always(function() {
	working=false;
	guienable(true);
    });
}
function depsel() {
    var sel=$(this).val();
    synctree('/treedep/'+sel);
}
function deacheck() {
    var s=$(this).is(':checked');
    synctree('/treeshowdea/'+(s?'Y':'N'));
}
function profileop(op) {
    if(working) {
	alert('Server je zaneprázdněn');
	return;
    }
    working=true;
    var serdata=$(\"#main_form\").serializeArray();
    guienable(false);
    $.post(\"".root()."ajaxmain/\"+op,serdata,function(data) {
	if(!data.res) alert(data.txt);
	else {
	    $(\"#main_uprfsel\").empty();
	    for(var key in data.profs) {
		var val=data.profs[key];
		$(\"#main_uprfsel\").append('<option selected value=\"'+key+'\">'+val+'</option>');
    	    }
    	    $(\"#main_uprfsel\").val(data.sel);
    	    $(\"#main_uprfname\").val('');
	}
    }).fail(function(jqXHR,textStatus,errorThrown) {
	alert('error !!! '+textStatus);
    }).always(function() {
	working=false;
	guienable(true);
    });
}
var prfdial;
function treegui() {
    $(\"#maintree_showdea\").change(deacheck);
    $(\"#maintree_depsel\").change(depsel);
    $(\"#maintree span:not(.notg)\").click(function() {
	$(this).siblings(\"ul\").toggle();
    });
    $(\"#maintree input\").click(function() {
	    var ppath;
	    if($(this).is(':checked')) {
		ppath='add/'+$(this).attr('id');
		$(this).parents('li').children('span').addClass('treehigh');
	    } else {
		ppath='rem/'+$(this).attr('id');
		$(this).parents('li').each(function() {
		    if(!$(this).find(':checked').length) {
			$(this).children('span').removeClass('treehigh');
			return true;
		    }
		    return false;
		});
	    }
	    postgraph('/'+ppath);
    });
    $(\"#main_form\").submit(function() {
	return false;
    });
    $(\"#001_main_from\").datepicker({dateFormat: \"yy-mm-dd\", changeMonth: true, changeYear: true, yearRange: \"2000:2050\"});
    $(\"#001_main_to\").datepicker({dateFormat: \"yy-mm-dd\", changeMonth: true, changeYear: true, yearRange: \"2000:2050\"});
    $(\"#timeset\").buttonset();
    $(\"#timeset input\").click(function() {
	postgraph('');
    });
    $(\"#main_apply\").button().click(function() {
	postgraph('');
    });
    $(\"#main_uprfcrt\").button({
	icons: {
	    primary: \"ui-icon-document\"
	},
	text: false
    }).click(function() {
	prfdial.dialog('open');
    });
    $(\"#main_uprfrem\").button({
	icons: {
	    primary: \"ui-icon-trash\"
	},
	text: false
    }).click(function() {
	profileop('prfremove');
    });
    $(\"#main_uprfsel\").change(function() {
	$(\"#maintree_depsel\").val('0');
	postgraph('/prfchange');
    });
    $(\"#tree_collapse\").button({
	icons: {
	    primary: \"ui-icon-circle-minus\"
	},
	text: false
    }).click(function() {
//	$(\"#treeenv ul:not(.notg)\").each(function() { // not work in chrome
//	    if($(this).attr('id')!='maintree') $(this).hide();
//	});
	$(\"#maintree\").children(\"li\").each(function() {
	    $(this).children(\"ul\").each(function() {
		$(this).children(\"li\").each(function() {
		    $(this).children(\"ul\").hide();
		});
		$(this).hide();
	    });
	});
    });
    $(\"#tree_expand\").button({
	icons: {
	    primary: \"ui-icon-circle-plus\"
	},
	text: false
    }).click(function() {
//	$(\"#treeenv ul:not(.notg)\").each(function() {
//	    if($(this).attr('id')!='maintree') $(this).show();
//	});
	$(\"#maintree\").children(\"li\").each(function() {
	    $(this).children(\"ul\").each(function() {
		$(this).children(\"li\").each(function() {
		    $(this).children(\"ul\").show();
		});
		$(this).show();
	    });
	});
    });
    $(\"#tree_clear\").button({
	icons: {
	    primary: \"ui-icon-circle-close\"
	},
	text: false
    }).click(function() {
	if(postgraph('/clear')) {
	    $(\"#treeenv input\").prop('checked',false);
	    $(\"#treeenv span\").removeClass('treehigh');
	}
    });
    prfdial=$(\"#main_newuprf\").dialog({
	autoOpen: false,
	modal: true,
	buttons: {
	    \"Vytvořit\": function() {
		$(\"#main_uprfname\").val($(\"#main_dialprfname\").val());
		profileop('prfcreate');
		prfdial.dialog(\"close\");
	    },
	    \"Zpět\": function() {
		prfdial.dialog(\"close\");
	    }
	},
	close: function() {
	    $(\"#main_uprfname\").val('');
	    $(\"#main_dialprfname\").val('');
	}
    });
    postgraph('/gettree');
}
// ]]>
</script>";
    $_JQUERY[]="treegui();";
}

echo "<form id=\"main_form\" action=\"\" method=\"post\">";

if(!is_array($_SESSION->mainform)) $_SESSION->mainform=array();
$_SESSION->temp_form=$_SESSION->mainform;

echo "<table style=\"width:100%\" class=\"nobr\"><tr><td rowspan=\"2\" style=\"padding:4px; vertical-align:top; width:220px; background-color:rgb(245,250,255); border-right:1px solid black;\">";
$opts=array('0'=>"(žádný)");
foreach($_SESSION->getprofiles() as $key=>$val) {
    $opts[bin2hex($key)]=$key;
}
echo "<table class=\"nobr\">";
echo "<tr><td>".input_select_temp("main_uprfsel",$opts,false,"mainuprf")."</td><td>".input_button("main_uprfrem","Smazat profil")." ".input_button("main_uprfcrt","Vytvořit profil")."</td></tr>";
echo "</table>";

echo input_hidden("main_uprfname","","main_uprfname");

echo "<div id=\"main_newuprf\" title=\"Nový profil\">
Název: ".input_text("main_dialprfname",false,"mainuprf")."
</div>";

echo "<hr />";
generatetree();
echo "</td><td style=\"vertical-align:top; padding-top:8px; border-right:1px solid black; height:140px; width:330px;\">";

echo "<table class=\"nobr\"><tr><td style=\"vertical-align:top\">";
	
echo "<div id=\"mvarscheck\">";
$qe=$SQL->query("select * from variable order by var_desc");
while($fe=$qe->obj()) {
    $key="000_main_vargr_".$fe->var_id;
    echo input_check_temp($key)." <span id=\"mcheck_".$fe->var_id."\">".label($key,htmlspecialchars($fe->var_desc." ".$fe->var_unit))."</span><br />";
}
echo "</div>";
echo "</td><td style=\"vertical-align:top; border-left:1px solid black\">";
echo input_check_temp("main_scales")." ".label("main_scales","Použít zadané rozsahy")."<br />";
echo input_check_temp("main_extremealarms")." ".label("main_extremealarms","Zobrazit hranice alarmu")."<br />";
echo input_check_temp("main_showalarms")." ".label("main_showalarms","Zobrazit alarmy")."<br />";
echo input_check_temp("main_setcolors")." ".label("main_setcolors","Aplikovat barvy")."<br />";
echo input_check_temp("main_derivate")." ".label("main_derivate","Derivovat")."<br />";
echo input_check_temp("main_groupvars")." ".label("main_groupvars","Slučovat veličiny")."<br />";
echo input_check_temp("main_groupmeas")." ".label("main_groupmeas","Slučovat měřící body")."<br />";
echo input_check_temp("main_connectdots")." ".label("main_connectdots","Spojovat body")."<br />";
echo input_check_temp("main_comments")." ".label("main_comments","Zobrazit komentáře")."<br />";
echo "</td></tr>";
echo "<tr><td style=\"text-align:center\" colspan=\"2\">".input_button("main_apply","Použít")."</td>";
echo "</table>";


echo "</td>";
echo "<td style=\"vertical-align:top; padding-top:8px; width:420px;\">";
    if(!get_ind($_SESSION->temp_form,"001_main_from")) $_SESSION->temp_form['001_main_from']=sprintf("%d%s",date("Y")-1,date("-m-d"));
    if(!get_ind($_SESSION->temp_form,"001_main_to")) $_SESSION->temp_form['001_main_to']=date("Y-m-d");
    if(!get_ind($_SESSION->temp_form,"main_time")) $_SESSION->temp_form['main_time']="7";
    $hours=array();
    for($i=0;$i<24;$i++) $hours[$i]=sprintf("%02d",$i);
    $mins=array();
    for($i=0;$i<60;$i+=5) $mins[$i]=sprintf("%02d",$i);
    echo "<table class=\"nobr\">";
    echo "<tr><td>Od (SEČ):&nbsp;</td><td>".input_text_temp("001_main_from")."&nbsp;".input_select_temp("001_main_from_h",$hours).":".input_select_temp("001_main_from_m",$mins)."</td></tr>";
    echo "<tr><td>Do (SEČ):&nbsp;</td><td>".input_text_temp("001_main_to")."&nbsp;".input_select_temp("001_main_to_h",$hours).":".input_select_temp("001_main_to_m",$mins)."</td></tr>";
    echo "<tr><td colspan=\"2\" style=\"text-align:center\"><div id=\"timeset\">";
    echo input_radio_temp("main_time","1").label("main_time_val1","Vše");
    echo input_radio_temp("main_time","2").label("main_time_val2","Rozsah");
    echo input_radio_temp("main_time","3").label("main_time_val3","3R");
    echo input_radio_temp("main_time","4").label("main_time_val4","1R");
    echo input_radio_temp("main_time","5").label("main_time_val5","6M");
    echo input_radio_temp("main_time","6").label("main_time_val6","1M");
    echo input_radio_temp("main_time","7").label("main_time_val7","7D");
    echo input_radio_temp("main_time","8").label("main_time_val8","1D");
    echo "</div><br />".input_check_temp("main_lastrawto")." ".label("main_lastrawto","čas relativně od posledního data")."</td></tr>";
    echo "</table>";
echo "</td><td>&nbsp;</td></tr>";

echo "<tr><td id=\"mainresult\" colspan=\"3\" style=\"border-top:1px solid black; vertical-align:top;\">";
echo "&nbsp;";
echo "</td></tr>";

echo "</table>";

$_SESSION->temp_form=false;

echo "</form>";

if($_SERVER['REQUEST_METHOD']=="POST") {
    $_SESSION->invalid=false;
    $_SESSION->temp_form=false;
    redir();
}
