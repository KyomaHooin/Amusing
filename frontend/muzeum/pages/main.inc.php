<?php

pageperm();

showmenu();

showerror();

ajaxsess();

function treespan($id,$txt,$pt=false,$cl=false,$lbl=false) {
    if($pt) $pt=" style=\"cursor:pointer;\"";
    else $pt="";
    if($cl) $cl=" class=\"".$cl."\"";
    else $cl="";
    $ret="<span".$cl." id=\"".htmlspecialchars($id)."\"".$pt.">";
    if($lbl) $ret.="<label for=\"".htmlspecialchars($lbl)."\">";
    $ret.=htmlspecialchars($txt);
    if($lbl) $ret.="</label>";
    return $ret."</span>";
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
	echo "<li style=\"padding-left:0px;\">".input_check("cid_".bin2hex($cfe->b_city)).treespan(bin2hex($cfe->b_city),$cfe->b_city,true);
	$bqe=$SQL->query("select * from building where b_city=\"".$SQL->escape($cfe->b_city)."\" order by b_name");
	echo "<ul style=\"display:none\">";
	while($bfe=$bqe->obj()) {
	    $bchecks=$checkcnt;
	    ob_start();
	    $rqe=$SQL->query("select * from room where r_bid=".$bfe->b_id." order by r_desc");
	    if($rqe->rowcount()) {
		echo "<li>".input_check("bid_".$bfe->b_id).treespan("bid_".$bfe->b_id,$bfe->b_name,true);
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
			echo "<li>".input_check("rid_".$rfe->r_id).treespan("rid_".$rfe->r_id,$rfe->r_desc." ".$rfe->r_floor,false,"notg","rid_".$rfe->r_id);
			echo "<ul class=\"notg\">";
			while($mfe=$mqe->obj()) {
			    $checkcnt++;
			    echo "<li>".input_check("mid_".$mfe->m_id,'Y',get_ind($checks,$mfe->m_id))."<label for=\"mid_".$mfe->m_id."\" style=\"cursor:pointer;\">".htmlspecialchars($mfe->m_desc." ".$mfe->m_depart)."</label></li>";
			}
			echo "</ul>";
		    } else {
			echo "<li>".treespan("rid_".$rfe->r_id,$rfe->r_desc." ".$rfe->r_floor);
		    }
		    echo "</li>";
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

//$(this).prop(\"indeterminate\", true);

    echo "<script type=\"text/javascript\">
// <![CDATA[
var wantdunnos=true;
var lasth=[];
var lastc=[];
function setdunnorek(ulitem) {
    var glyes=0;
    var glno=0;
    var glinter=false;
    ulitem.children('li:not(.lihid)').each(function() {
	$(this).children('input').each(function() {
	    var ulyes=0;
	    var ulno=0;
	    var ulinter=$(this).prop('indeterminate');
	    var ulcheck=$(this).prop('checked');
	    $(this).siblings('ul').each(function() {
		switch(setdunnorek($(this))) {
		case -1:
		    ulinter=true;
		    break;
		case 1:
		    ulyes++;
		    break;
		default:
		    ulno++;
		    break;
		}
	    });
	    if(!ulinter) {
		if(ulyes) {
		    ulcheck=true;
		    if(ulno) ulinter=true;
		} else if(ulno) ulcheck=false;
	    }
	    if(ulinter) {
		glinter=true;
		ulcheck=true;
	    }
	    if(ulcheck) glyes++;
	    else glno++;
	    $(this).prop('checked',ulcheck).prop('indeterminate',ulinter);
	    if(ulcheck) $(this).siblings('span').addClass('treehigh');
	});
    });
    if(glinter) return -1;
    if(glyes) {
	if(glno) return -1;
	return 1;
    }
    return 0;
}
function setdunnos() {
    $('#maintree').find('span').removeClass('treehigh');
    $('#maintree').find('input').prop('indeterminate',false);
    setdunnorek($('#maintree'));
}
function showgraph(data) {
    $(\"#mainresult\").html(data.graph);
    $(\"#mvarscheck span\").css(\"text-decoration\",\"none\");
    for(i=0,l=data.uvars.length;i<l;i++) {
	$(data.uvars[i]).css(\"text-decoration\",\"underline\");
    }
    for(i=0,l=data.checks.length;i<l;i++) {
	var cn=data.checks[i];
	$(cn).prop('checked',true);
	$(cn).parent().parent().show();
	$(cn).parent().parent().parent().parent().show();
	$(cn).parent().parent().parent().parent().parent().parent().show();
    }
    for(i=0,l=lastc.length;i<l;i++) {
	if(data.checks.indexOf(lastc[i])<0) {
	    $(lastc[i]).prop('checked',false);
	}
    }
    lastc=data.checks;
    
// later do some array intersection
    for(i=0,l=lasth.length;i<l;i++) {
	if(data.tohid.indexOf(lasth[i])<0) $(lasth[i]).parent().removeClass('lihid');
    }
    lasth=data.tohid;
    for(i=0,l=lasth.length;i<l;i++) {
	$(lasth[i]).parent().addClass('lihid');
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
    if(wantdunnos) {
	wantdunnos=false;
	setdunnos();
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
    $(\"#main_uprfsave\").button(e);
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
    wantdunnos=true;
    synctree('/treedep/'+sel);
}
function deacheck() {
    var s=$(this).is(':checked');
    wantdunnos=true;
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
function profilesave() {
    if(working) {
	alert('Server je zaneprázdněn');
	return;
    }
    working=true;
    var serdata=$(\"#main_form\").serializeArray();
    guienable(false);
    $.post(\"".root()."ajaxmain/prfsave\",serdata,function(data) {
    }).fail(function(jqXHR,textStatus,errorThrown) {
	alert('error !!! '+textStatus);
    }).always(function() {
	working=false;
	guienable(true);
    });
}
function setchecks(chk,dunno) {
    var nochk=0;
    var yeschk=0;
    
    if(!dunno) {
	chk.siblings('ul').children('li:not(.lihid)').children('input').each(function() {
	    if($(this).prop('checked')) yeschk++;
	    else nochk++;
	});
	if(yeschk) {
	    if(nochk) {
		dunno=true;
		chk.prop('checked',true);
		chk.siblings('span').addClass('treehigh');
		chk.prop('indeterminate',true);
	    } else {
		chk.prop('checked',true);
		chk.siblings('span').addClass('treehigh');
		chk.prop('indeterminate',false);
	    }
	} else {
	    chk.prop('checked',false);
	    chk.siblings('span').removeClass('treehigh');
	    chk.prop('indeterminate',false);
	}
    } else {
	chk.prop('checked',true);
	chk.siblings('span').addClass('treehigh');
	chk.prop('indeterminate',true);
    }
    
    chk.parent().parent().siblings('input').each(function() {
	setchecks($(this),dunno);
    });
}
function setsubcheck(chul,st) {
    if(st) chul.siblings('span').addClass('treehigh');
    else chul.siblings('span').removeClass('treehigh');
    chul.children('li:not(.lihid)').each(function() {
	$(this).children('input').prop('checked',st);
	$(this).children('ul').each(function() {
	    setsubcheck($(this),st);
	});
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
	$(this).prop('indeterminate',false);
	$(this).parent().parent().siblings('input').each(function() {
	    setchecks($(this),false);
	});
	$(this).parent().find('input').prop('indeterminate',false);
	var toch=$(this).prop('checked');
	$(this).siblings('ul').each(function() {
	    setsubcheck($(this),toch);
	});
	postgraph('/settree');
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
    $(\"#prfremconfirm\").dialog({
	resizable: false,
	height: \"auto\",
	width: 400,
	modal: true,
	autoOpen: false,
	buttons: {
	    \"Smazat\":function() {
		$(this).dialog(\"close\");
		profileop('prfremove');
	    },
	    \"Zpět\":function() {
		$(this).dialog(\"close\");
	    }
	}
    });
    $(\"#main_uprfsave\").button({
	icons: {
	    primary: \"ui-icon-disk\"
	},
	text: false
    }).click(function() {
	profilesave();
    });
    $(\"#main_uprfrem\").button({
	icons: {
	    primary: \"ui-icon-trash\"
	},
	text: false
    }).click(function() {
	$(\"#prfremconfirm\").dialog(\"open\");
    });
    $(\"#main_uprfsel\").change(function() {
	$(\"#maintree_depsel\").val('0');
	wantdunnos=true;
	postgraph('/prfchange');
    });
    $(\"#tree_collapse\").button({
	icons: {
	    primary: \"ui-icon-circle-minus\"
	},
	text: false
    }).click(function() {
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
	wantdunnos=true;
	postgraph('/clear');
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

// dialog
echo "<div id=\"prfremconfirm\" title=\"Opravdu smazat ?\"><p>Smazat profil</p></div>";

echo "<form id=\"main_form\" action=\"\" method=\"post\" enctype=\"multipart/form-data\">";

if(!is_array($_SESSION->mainform)) $_SESSION->mainform=array();
$_SESSION->temp_form=$_SESSION->mainform;

echo "<table style=\"width:100%\" class=\"nobr\"><tr><td rowspan=\"2\" style=\"padding:4px; vertical-align:top; width:220px; background-color:rgb(245,250,255); border-right:1px solid black;\">";
$opts=array('0'=>"(žádný)");
foreach($_SESSION->getprofiles() as $key=>$val) {
    $opts[bin2hex($key)]=$key;
}
echo "<table class=\"nobr\">";
echo "<tr><td>".input_select_temp("main_uprfsel",$opts,false,"mainuprf")."</td><td style=\"white-space:nowrap;\">".input_button("main_uprfsave","Uložit profil").input_button("main_uprfrem","Smazat profil").input_button("main_uprfcrt","Vytvořit profil")."</td></tr>";
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
