<?php

$_NOHEAD=true;

function over() {
    header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
    exit();
}

if(!$_SESSION->user) over();

function sherr($t) {
    echo "<p class=\"showerr\">".$t."</p>";
}

function varleftsort($e1,$e2) {
    if($e1['left']<$e2['left']) return 1;
    if($e1['left']>$e2['left']) return -1;
    return 0;
}

function deltemps() {
    global $_PLOTDIR;
    global $_CSVDIR;

    if(!is_array($_SESSION->plot_outputs)) $_SESSION->plot_outputs=array();
    if(!is_array($_SESSION->csv_outputs)) $_SESSION->csv_outputs=array();
    
    $reft=time();
    $newa=array();
    foreach($_SESSION->plot_outputs as $key=>$val) {
	if($val+3600<$reft) {
	    @unlink($_PLOTDIR."/".$key);
	} else $newa[$key]=$val;
    }
    $_SESSION->plot_outputs=$newa;
    $newa=array();
    foreach($_SESSION->csv_outputs as $key=>$val) {
	if($val+3600<$reft) {
	    @unlink($_CSVDIR."/".$key);
	} else $newa[$key]=$val;
    }
    $_SESSION->csv_outputs=$newa;
    
    if(!is_array($_SESSION->imgsliders)) $_SESSION->imgsliders=array();
    $news=array();
    foreach($_SESSION->imgsliders as $key=>$val) {
	if($val['age']+3600<$reft) {
	    @unlink($_PLOTDIR."/".$key);
	} else $news[$key]=$val;
    }
    $_SESSION->imgsliders=$news;
}

function tdatasort($e1,$e2) {
    if($e1['bid']<$e2['bid']) return -1;
    if($e1['bid']>$e2['bid']) return 1;
    if($e1['rid']<$e2['rid']) return -1;
    if($e1['rid']>$e2['rid']) return 1;
    if($e1['mid']<$e2['mid']) return -1;
    if($e1['mid']>$e2['mid']) return 1;
    if($e1['vid']<$e2['vid']) return -1;
    if($e1['vid']>$e2['vid']) return 1;
    return 0;
}

function plotgraph($togen,$from,$to) {
    global $SQL;
    global $_CSVDIR;
    global $_PLOTDIR;
    global $_PLOTW;
    global $_PLOTH;
    global $_MAXDATAAGE;
    
	$vars=array();
	$mids=array();
	foreach($togen as $val) {
	    $mids[]=array("mid"=>$val[0],"var"=>$val[1]);
	    $vars[$val[1]]=array('d'=>array());
	}
	$usescales=(get_ind($_SESSION->mainform,"main_scales")=='Y');
	$showextremes=(get_ind($_SESSION->mainform,"main_extremealarms")=='Y');
	$showalarms=(get_ind($_SESSION->mainform,"main_showalarms")=='Y');
	$setcolors=(get_ind($_SESSION->mainform,"main_setcolors")=='Y');
	$derivate=(get_ind($_SESSION->mainform,"main_derivate")=='Y');
	$dotconnect=(get_ind($_SESSION->mainform,"main_connectdots")=='Y');
	
	$rfm=array();
	foreach($togen as $val) {
	    $rfm[]=$val[0]."_".$val[1];
	}
	$rad="/".($usescales?'1':'0')."/".($showextremes?'1':'0')."/".($showalarms?'1':'0')."/".($setcolors?'1':'0')."/".($derivate?'1':'0');
	$getref1d=root()."getplotref/".implode("-",$rfm)."/1D".$rad;
	$getref7d=root()."getplotref/".implode("-",$rfm)."/7D".$rad;

	if(!count($vars)) {
	    sherr("Nezvolena veličina / měřící bod");
	    return;
	}
	$qe=$SQL->query("select * from variable left join varcodes on vc_text=var_code where var_id in (".implode(",",array_keys($vars)).")");
	if($qe->rowcount()!=count($vars)) {
	    sherr("Chyba dat");
	    return;
	}
	while($fe=$qe->obj()) {
	    $vdat=unserialize($fe->var_plotdata);
	    $vars[$fe->var_id]['id']=$fe->var_id;
	    $vars[$fe->var_id]['left']=(int)$fe->var_left;
	    $vars[$fe->var_id]['n']=$fe->var_desc;
	    $vars[$fe->var_id]['u']=$fe->var_unit;
	    $vars[$fe->var_id]['code']=$fe->var_code;
	    $vars[$fe->var_id]['min']=get_ind($vdat,"min");
	    $vars[$fe->var_id]['max']=get_ind($vdat,"max");
	    $vars[$fe->var_id]['dmin']=get_ind($vdat,"dmin");
	    $vars[$fe->var_id]['dmax']=get_ind($vdat,"dmax");
	    $vars[$fe->var_id]['color']=get_ind($vdat,"color");
	    $vars[$fe->var_id]['expperiod']=$fe->vc_expperiod;
	}

	$tabs=array();
	dblock();
	$qe=$SQL->query("show tables like \"values\\_%\"");
	while($fe=$qe->row()) {
	    if(preg_match("/^values_(\\d+)$/",$fe[0],$mch)) $tabs[]=$mch[1];
	}
	dbunlock();
	sort($tabs);
	$fromy=gmdate("Y",$from);
	$toy=gmdate("Y",$to);
	
	if(!is_array($_SESSION->plot_outputs)) $_SESSION->plot_outputs=array();
	if(!is_array($_SESSION->csv_outputs)) $_SESSION->csv_outputs=array();
	foreach($mids as $val) {
	    $fn=tempnam($_CSVDIR,md5(uniqid()));
	    if(!$fn) {
		sherr("Nelze vytvořit tempcsv");
		return;
	    }
	    ob_start();
	    $first=true;
	    $max=array(0,0);
	    $min=array(0,0);
	    $avg=0.0;
	    $sq=0.0;
	    $totcnt=0;
	    $firsttime=false;
	    $lasttime=false;
	    $lastrawval=false;
	    $lastrawtime=false;
	    $values=array();
	    $integration=0.0;
	    
	    $qe=$SQL->query("select * from varmeascache where vmc_mid=".$val['mid']." && vmc_varid=".$val['var']);
	    $fe=$qe->obj();
	    if($fe) {
		$lastrawval=$fe->vmc_lastrawvalue;
		$lastrawtime=$fe->vmc_lastrawtime;
	    }
	    
	    $mainperiod=$vars[$val['var']]['expperiod'];
	    if($derivate) {
		foreach($tabs as $yval) {
		    if($yval>=$fromy && $yval<=$toy) { // try select here
			$qe=$SQL->query("select v_date,v_value from values_".$yval." where v_mid=".$val['mid']." && v_varid=".$val['var']." && v_date>=".$from." && v_date<=".$to." order by v_date"); // order needed ?
			$totcnt+=$qe->rowcount();
			while($fe=$qe->obj()) {
			    if($first) {
				$first=false;
				$max=array($fe->v_value,$fe->v_date);
				$min=array($fe->v_value,$fe->v_date);
				$lastval=$fe->v_value;
				$lasttime=$firsttime=$fe->v_date;
				$der="NaN";
//				$der="0";
			    } else {
				if($fe->v_value>$max[0]) $max=array($fe->v_value,$fe->v_date);
				if($fe->v_value<$min[0]) $min=array($fe->v_value,$fe->v_date);
				if($fe->v_date!=$lasttime) {
				    $der=($fe->v_value-$lastval)/(($fe->v_date-$lasttime)/3600);
				} else $der=0;
			// integration
				$integration+=($lastval+$fe->v_value)*0.5*($fe->v_date-$lasttime);
				
				$lastval=$fe->v_value;
				if(!$dotconnect && $mainperiod && $lasttime+$mainperiod<$fe->v_date) {
				    for($lasttime+=$mainperiod;$lasttime<$fe->v_date;$lasttime+=$mainperiod) echo $lasttime.";NaN;NaN\n";
				    $der="NaN";
				}
				$lasttime=$fe->v_date;
			    }
			    $avg+=$fe->v_value;
			    $sq+=$fe->v_value*$fe->v_value;
			    $values[]=$fe->v_value;
			    echo $fe->v_date.";".$fe->v_value.";".$der."\n";
			}
		    }
		}
	    } else {
		foreach($tabs as $yval) {
		    if($yval>=$fromy && $yval<=$toy) { // try select here
			$qe=$SQL->query("select v_date,v_value from values_".$yval." where v_mid=".$val['mid']." && v_varid=".$val['var']." && v_date>=".$from." && v_date<=".$to." order by v_date"); // order needed ?
			$totcnt+=$qe->rowcount();
			while($fe=$qe->obj()) {
			    if($first) {
				$first=false;
				$max=array($fe->v_value,$fe->v_date);
				$min=array($fe->v_value,$fe->v_date);
				$lastval=$fe->v_value;
				$lasttime=$firsttime=$fe->v_date;
			    } else {
				if($fe->v_value>$max[0]) $max=array($fe->v_value,$fe->v_date);
				if($fe->v_value<$min[0]) $min=array($fe->v_value,$fe->v_date);
				if(!$dotconnect && $mainperiod && $lasttime+$mainperiod<$fe->v_date) {
				    for($lasttime+=$mainperiod;$lasttime<$fe->v_date;$lasttime+=$mainperiod) echo $lasttime.";NaN\n";
				}
			// integration
				$integration+=($lastval+$fe->v_value)*0.5*($fe->v_date-$lasttime);
				
				$lastval=$fe->v_value;
				$lasttime=$fe->v_date;
			    }
			    $avg+=$fe->v_value;
			    $sq+=$fe->v_value*$fe->v_value;
			    $values[]=$fe->v_value;
			    echo $fe->v_date.";".$fe->v_value."\n";
			}
		    }
		}
	    }
	    if($totcnt) {
		if(file_put_contents($fn,ob_get_clean())===false) {
		    sherr("Nelze uložit tempcsv");
		    return;
		}
	    } else ob_end_clean();
	    
	    $fna=false;
	    if($totcnt && $showalarms) {
		$qe=$SQL->query("select * from alarmlog where al_vid=".$val['var']." && al_mid=".$val['mid']." && al_date>=".$from." && al_date<=".$to." && al_edge='R' order by al_date");
		if($qe->rowcount()) {
		    $fna=tempnam($_CSVDIR,md5(uniqid()));
		    if(!$fna) {
			sherr("Nelze vytvořit tempcsv pro alarm");
			return;
		    }
		    ob_start();
		    while($fe=$qe->obj()) {
			echo $fe->al_date.";".$fe->al_value.";".c_alarm_gen::getdescbyname($fe->al_class)."\n"; // could be separated edges, now only rising edge
		    }
		    if(file_put_contents($fna,ob_get_clean())===false) {
			sherr("Nelze uložit tempcsv pro alarm");
			return;
		    }
		}
	    }
	    
	    $qe=$SQL->query("select * from measuring left join room on m_rid=r_id left join building on r_bid=b_id where m_id=".$val['mid']);
	    $fe=$qe->obj();
	    if(!$fe) {
		sherr("Chyba dat, neexistující měřící bod");
		return;
	    }
	    sort($values);
	    $median=0.0;
	    if($totcnt) {
		$avg/=$totcnt;
		if($totcnt&1) { // odd
		    $median=$values[$totcnt/2];
		} else { // even
		    $median=($values[$totcnt/2-1]+$values[$totcnt/2])/2;
		}
	    } else $avg=0.0;
	    
	    $vars[$val['var']]['d'][]=array(
		"mid"=>$val['mid'],
		"mimg"=>$fe->m_img,
		"mname"=>htmlspecialchars($fe->m_desc),
		"csv"=>$fn,
		"csva"=>$fna,
		"desc"=>$fe->b_name." ".$fe->r_desc." ".$fe->r_floor." ".$fe->m_desc." ".$vars[$val['var']]['n'],
//		"desc2"=>htmlspecialchars($fe->b_name)."<br />".htmlspecialchars($fe->r_desc." ".$fe->r_floor)."<br />".htmlspecialchars($fe->m_desc),
		"avg"=>$avg,
		"max"=>$max,
		"min"=>$min,
		"bid"=>$fe->b_id,
		"bimg"=>$fe->b_img,
		"bname"=>htmlspecialchars($fe->b_name),
		"rid"=>$fe->r_id,
		"rimg"=>$fe->r_img,
		"rname"=>htmlspecialchars($fe->r_desc." ".$fe->r_floor),
		"datalines"=>$totcnt,
		"firsttime"=>$firsttime,
		"lasttime"=>$lasttime,
		"lastrawval"=>$lastrawval,
		"lastrawtime"=>$lastrawtime,
		"sigma"=>($totcnt?sqrt(($sq-$totcnt*$avg*$avg)/$totcnt):0.0),
		"median"=>$median,
		"integration"=>$integration/3600.0
	    );
	}
	$vars=array_values($vars);
	usort($vars,"varleftsort");
	
//	print_read($vars);
//	return;

// generating output html, files should be cleaned every midnight cca
	$plotpng=tempnam($_PLOTDIR,md5(uniqid()));
	$plotsvg=tempnam($_PLOTDIR,md5(uniqid()));
	$plotjs=tempnam($_PLOTDIR,md5(uniqid()));
	$fn=tempnam($_PLOTDIR,md5(uniqid()));
	if(!$fn || !$plotpng || !$plotsvg || !$plotjs) {
	    @unlink($plotpng);
	    @unlink($plotsvg);
	    @unlink($plotjs);
	    @unlink($fn);
	    sherr("Nelze vytvořit graf");
	    return;
	}
	$timezone=3600;

	ob_start();
	echo "set terminal png size ".$_PLOTW.",".$_PLOTH."\n";
	echo "set output \"".$plotpng."\"\n";
	
	ob_start();
	echo "set datafile missing \"NaN\"\n";
	echo "set grid front\n";
	echo "set datafile separator \";\"\n";
	
	$havemulti=false;
	if($derivate) {
	    ob_start();
	    echo "set lmargin 10\n";
	    echo "set rmargin 10\n";
	    
	    echo "set multiplot layout 2,1\n";

	    echo "set xdata time\n";
	    echo "set timefmt \"%s\"\n";
	    
	    echo "set format x \"\"\n";
//	echo "set format x \"%Y-%m-%d\\n%H:%02M:%02S\"\n";
	    echo "unset xlabel\n";
	    
// has to draw all derivation graphs in two y axis
	    $cnt=1;
	    $plot=array();
	    foreach($vars as $val) {
		if($cnt==1) {
		    echo "set ytics auto\n";
		    echo "set ylabel \"".addcslashes($val['n']." / hod","\"")."\"\n";
		    if($usescales && get_ind($val,'dmax')!==false && get_ind($val,'dmin')!==false) {
			echo "set yrange [".$val['dmin'].":".$val['dmax']."]\n";
		    } else echo "set yrange [*:*]\n";
		} else {
		    echo "set y2tics auto\n";
		    echo "set y2label \"".addcslashes($val['n']." / hod","\"")."\"\n";
		    if($usescales && get_ind($val,'dmax')!==false && get_ind($val,'dmin')!==false) {
			echo "set y2range [".$val['dmin'].":".$val['dmax']."]\n";
		    } else echo "set y2range [*:*]\n";
		}
		$totcnt=0;
		foreach($val['d'] as $dat) {
		    if(!$dat['datalines']) continue;

		    if($setcolors) $plot[]="\"".$dat['csv']."\" using ($1+".$timezone."):($3) with lines".($val['color']?" linecolor rgb \"".$val['color']."\"":"")." title \"".addcslashes("derivace ".$dat['desc'],"\"")."\" axes x1y".$cnt;
		    else $plot[]="\"".$dat['csv']."\" using ($1+".$timezone."):($3) with lines title \"".addcslashes("derivace ".$dat['desc'],"\"")."\" axes x1y".$cnt;
		    $totcnt++;
		}
		$cnt++;
	    }
	    echo "set size 1.0,0.4\n";
	    echo "set origin 0.0,0.0\n";
	    
	    echo "set xrange [] writeback\n";
	    echo "plot ".implode(",\\\n",$plot)."\n";
	    
	    echo "set size 1.0,0.6\n";
	    echo "set origin 0.0,0.4\n";
	    
	    echo "set xrange restore\n";
	    
	    if(!$totcnt) {
		ob_end_clean();
		echo "set xdata time\n";
		echo "set timefmt \"%s\"\n";
	    } else {
		ob_end_flush();
		$havemulti=true;
	    }
	} else {
	    echo "set xdata time\n";
	    echo "set timefmt \"%s\"\n";
	}
	
	echo "set xlabel \"Time (SEČ)\"\n";
	echo "set format x \"%Y-%m-%d\\n%H:%02M:%02S\"\n";
	echo "set ytics 0,10,100\n";

	$cnt=0;
	foreach($vars as $val) {
	    if(!$cnt) {
		if($usescales && get_ind($val,'max')!==false && get_ind($val,'min')!==false) {
		    echo "set yrange [".$val['min'].":".$val['max']."]\n";
		} else echo "set yrange [*:*]\n";
		echo "set ylabel \"".addcslashes($val['n'],"\"")."\"\n";
	    } else {
		if($usescales && get_ind($val,'max')!==false && get_ind($val,'min')!==false) {
		    echo "set y2range [".$val['min'].":".$val['max']."]\n";
		} else echo "set y2range [*:*]\n";
		echo "set y2label \"".addcslashes($val['n'],"\"")."\"\n";
		echo "set y2tics 0,10,100\n";
	    }
	    $cnt++;
	}
	$plot=array();
// alarms (only extrems)
	if($showextremes) {
	    $cnt=1;
	    foreach($vars as $val) {
		foreach($val['d'] as $dat) {
		    if(!$dat['datalines']) continue; // dont show for no data
		    $qe=$SQL->query("select * from alarm where a_mid=".$dat['mid']." && a_vid=".$val['id']." && a_class=\"c_alarm_extreme\"");
		    while($fe=$qe->obj()) {
			$adata=unserialize($fe->a_data);
			$ext=get_ind($adata,"ext");
			$rel=get_ind($adata,"rel");
			if($ext===false || $rel===false) continue;
			switch($rel) {
			    case 0:
				$plot[]="\"".$dat['csv']."\" using ($1+".$timezone."):($2>".$ext."?$2:1/0) title \"".addcslashes($dat['desc'],"\"")." > ".$ext."\" axes x1y".$cnt." w filledcurves above y".$cnt."=".$ext." fs transparent solid .1";
				break;
			    case 1:
				$plot[]="\"".$dat['csv']."\" using ($1+".$timezone."):($2<".$ext."?$2:1/0) title \"".addcslashes($dat['desc'],"\"")." < ".$ext."\" axes x1y".$cnt." w filledcurves below y".$cnt."=".$ext." fs transparent solid .1";
				break;
			}
		    }
		}
		$cnt++;
	    }
	}

	$cnt=1;
	$totcnt=0;
	foreach($vars as $val) {
	    foreach($val['d'] as $dat) {
		if(!$dat['datalines']) continue;
		if($setcolors) $plot[]="\"".$dat['csv']."\" using ($1+".$timezone."):($2) with lines".($val['color']?" linecolor rgb \"".$val['color']."\"":"")." title \"".addcslashes($dat['desc'],"\"")."\" axes x1y".$cnt;
		else $plot[]="\"".$dat['csv']."\" using ($1+".$timezone."):($2) with lines title \"".addcslashes($dat['desc'],"\"")."\" axes x1y".$cnt;
		$totcnt++;
	    }
	    $cnt++;
	}

	if($showalarms) {
	    $cnt=1;
	    foreach($vars as $val) {
		foreach($val['d'] as $dat) {
		    if(!$dat['datalines']) continue;
		    if(get_ind($dat,"csva")) {
			$plot[]="\"".$dat['csva']."\" using ($1+".$timezone."):($2) title \"alarm ".addcslashes($dat['desc'],"\"")."\" pt 7 axes x1y".$cnt;
		    }
		}
		$cnt++;
	    }
	}

	echo "plot ".implode(",\\\n",$plot)."\n";
	if($havemulti) echo "unset multiplot\n";
	$tmpgnu=ob_get_flush();

// svg part
	echo "set terminal svg size ".$_PLOTW.",".$_PLOTH."\n";
	echo "set output \"".$plotsvg."\"\n";
	
	echo $tmpgnu;
	
	echo "set terminal canvas mousing jsdir '".root()."js/gp' size ".$_PLOTW.",".$_PLOTH."\n";
	echo "set output \"".$plotjs."\"\n";
	
	$jsgp=recode_string("utf8..flat",$tmpgnu);
	if($jsgp===false) echo $tmpgnu;
	else echo $jsgp;

	if($totcnt) {
	    $gnu=ob_get_clean();
	    if(file_put_contents($fn,$gnu)===false) {
		@unlink($plotpng);
		@unlink($plotsvg);
		@unlink($plotjs);
		@unlink($fn);
		sherr("Nelze vytvořit graf");
		return;
	    }
//	    ob_start();
//	    passthru("/usr/bin/gnuplot ".$fn." 2>&1",$st);
//	    $gerr=ob_end_clean();
	    $gout=array();
	    @exec("/usr/bin/gnuplot ".$fn." 2>&1",$gout,$st);
	    $gerr=implode("\n",$gout);

	    if($st) {
		@unlink($plotpng);
		@unlink($plotsvg);
		@unlink($plotjs);
		@unlink($fn);
		sherr("Nelze spustit gnuplot");
		echo "<br />";
		echo "<pre>".$gerr."</pre>";
		return;
	    }
	    $_SESSION->plot_outputs[basename($plotpng)]=time();
	    $_SESSION->plot_outputs[basename($plotsvg)]=time();
	    $_SESSION->plot_outputs[basename($plotjs)]=time();
	} else {
	    ob_end_clean();
	    @unlink($plotpng);
	    @unlink($plotsvg);
	    @unlink($plotjs);
	    @unlink($fn);
	}
// output page
// brutal sort per building, room and measpoint ?, madafaka...
	$tdata=array();
	foreach($vars as $val) foreach($val['d'] as $dat) {
	    $dat['vid']=$val['id'];
	    $dat['varname']=$val['n'];
	    $dat['varunit']=$val['u'];
	    $dat['varcode']=$val['code'];
	    $tdata[]=$dat;
	}
	usort($tdata,"tdatasort");
	$bspan=array();
	$rspan=array();
	$mspan=array();
	foreach($tdata as $dat) {
	    if(get_ind($bspan,$dat['bid'])===false) $bspan[$dat['bid']]=array(0,$dat['bname'],$dat['bimg']);
	    if(get_ind($rspan,$dat['rid'])===false) $rspan[$dat['rid']]=array(0,$dat['rname'],$dat['rimg']);
	    if(get_ind($mspan,$dat['mid'])===false) $mspan[$dat['mid']]=array(0,$dat['mname'],$dat['mimg']);
	    $bspan[$dat['bid']][0]++;
	    $rspan[$dat['rid']][0]++;
	    $mspan[$dat['mid']][0]++;
	}
	$current=time();
	echo "<table class=\"graphtable\">";
	echo "<tr>";
	if($totcnt) echo "<td rowspan=\"17\" style=\"vertical-align:top;\"><a href=\"".root()."getplotpng/".basename($plotpng).".png\" target=\"_blank\"><img src=\"".root()."getplotpng/".basename($plotpng).".png\" width=\"800\" /></a>";
	else echo "<td rowspan=\"17\" style=\"vertical-align:top;\">žádná data";
	echo "<br /><a target=\"_blank\" href=\"".$getref1d."\">graf 1D</a><br /><a target=\"_blank\" href=\"".$getref7d."\">graf 7D</a>";
	echo "</td>";
	echo "<td>budova</td>";
	foreach($bspan as $val) {
	    echo "<td style=\"vertical-align:top;\" colspan=\"".$val[0]."\">";
	    if($val[2]) echo "<a href=\"".root()."image/".$val[2]."\" target=\"_blank\"><img style=\"margin:2px;\" src=\"".root()."image/".$val[2]."/max/100/100\" /></a><br />";
	    echo $val[1];
	    echo "</td>";
	}
	echo "</tr><tr>";
	echo "<td>místnost</td>";
	foreach($rspan as $val) {
	    echo "<td style=\"vertical-align:top;\" colspan=\"".$val[0]."\">";
	    if($val[2]) echo "<a href=\"".root()."image/".$val[2]."\" target=\"_blank\"><img style=\"margin:2px;\" src=\"".root()."image/".$val[2]."/max/100/100\" /></a><br />";
	    echo $val[1];
	    echo "</td>";
	}
	echo "</tr><tr>";
	echo "<td>měřící bod</td>";
	foreach($mspan as $val) {
	    echo "<td style=\"vertical-align:top;\" colspan=\"".$val[0]."\">";
	    if($val[2]) echo "<a href=\"".root()."image/".$val[2]."\" target=\"_blank\"><img style=\"margin:2px;\" src=\"".root()."image/".$val[2]."/max/100/100\" /></a><br />";
	    echo $val[1];
	    echo "</td>";
	}
	echo "</tr><tr>";
	echo "<td>veličina</td>";
	foreach($tdata as $dat) {
	    echo "<td style=\"text-align:center;\">".htmlspecialchars($dat['varname']);
	    if($dat['lastrawtime']!=false) {
		if($dat['lastrawtime']+$_MAXDATAAGE*60>=$current) echo "<br /><b style=\"color:#0f0\">online</b>";
		else echo "<br /><b style=\"color:#f00\">offline</b>";
	    }
	    echo "</td>";
	}
	echo "</tr><tr>";
	echo "<td>průměr</td>";
	foreach($tdata as $dat) {
	    if($dat['datalines']) echo "<td><nobr>".sprintf("%.1f %s",$dat['avg'],$dat['varunit'])."</nobr></td>";
	    else echo "<td rowspan=\"11\"><nobr>žádná data</nobr></td>";
	}
	echo "</tr><tr>";
	echo "<td>max.</td>";
	foreach($tdata as $dat) {
	    if($dat['datalines']) echo "<td><nobr>".sprintf("%.1f %s",$dat['max'][0],$dat['varunit'])."</nobr></td>";
	}
	echo "</tr><tr>";
	echo "<td>max.datum</td>";
	foreach($tdata as $dat) {
	    if($dat['datalines']) echo "<td>".showtime2($dat['max'][1])."</td>";
	}
	echo "</tr><tr>";
	echo "<td>min.</td>";
	foreach($tdata as $dat) {
	    if($dat['datalines']) echo "<td><nobr>".sprintf("%.1f %s",$dat['min'][0],$dat['varunit'])."</nobr></td>";
	}
	echo "</tr><tr>";
	echo "<td>min.datum</td>";
	foreach($tdata as $dat) {
	    if($dat['datalines']) echo "<td>".showtime2($dat['min'][1])."</td>";
	}
	echo "</tr><tr>";
	echo "<td>sm. odch.</td>";
	foreach($tdata as $dat) {
	    if($dat['datalines']) echo "<td><nobr>".sprintf("%.1f",$dat['sigma'])."</nobr></td>";
	}
	echo "</tr><tr>";
	echo "<td>medián</td>";
	foreach($tdata as $dat) {
	    if($dat['datalines']) echo "<td><nobr>".sprintf("%.1f",$dat['median'])."</nobr></td>";
	}
	echo "</tr><tr>";
	echo "<td>integrál (*hod)";
	$havelight=false;
	foreach($tdata as $dat) {
	    if($dat['varcode']=="light") {
		$havelight=true;
		echo "<br />roční predikce";
		break;
	    }
	}
	echo "</td>";
	foreach($tdata as $dat) {
	    if($dat['datalines']) {
		echo "<td><nobr>".sprintf("%.1f",$dat['integration']);
		if($havelight) {
		    echo "<br />";
		    if($dat['varcode']=="light" && $dat['firsttime']<$dat['lasttime']) echo sprintf("%.1f",$dat['integration']/(($dat['lasttime']-$dat['firsttime'])/3600.0)*8765.81);
		    else echo "&nbsp;";
		}
		echo "</nobr></td>";
	    }
	}
	echo "</tr><tr>";
	echo "<td>data od</td>";
	foreach($tdata as $dat) {
	    if($dat['datalines']) echo "<td><nobr>".showtime2($dat['firsttime'])."</nobr></td>";
	}
	echo "</tr><tr>";
	echo "<td>data do</td>";
	foreach($tdata as $dat) {
	    if($dat['datalines']) echo "<td><nobr>".showtime2($dat['lasttime'])."</nobr></td>";
	}
	echo "</tr><tr>";
	echo "<td>vzorků</td>";
	foreach($tdata as $dat) {
	    if($dat['datalines']) echo "<td><nobr>".$dat['datalines']."</nobr></td>";
	}
	echo "</tr><tr>";
	echo "<td>posl.raw</td>";
	foreach($tdata as $dat) {
	    if($dat['datalines']) echo "<td><nobr>".$dat['lastrawval']."</nobr></td>";
	}
	echo "</tr><tr>";
	echo "<td colspan=\"".(count($tdata)+1)."\">";
	if($totcnt) {
	    echo "<a href=\"".root()."getplotpng/".basename($plotpng).".png\" target=\"_blank\">Plná velikost</a><br />";
	    echo "<a href=\"".root()."getplotsvg/".basename($plotsvg).".svg\" target=\"_blank\">Jako SVG</a><br />";
	    echo "<a href=\"".root()."getplotjs/".basename($plotjs).".html\" target=\"_blank\">Interaktivní graf</a></br />";
	    echo "<br />Data jako CSV:<br />";
	    foreach($tdata as $dat) {
		    if(!$dat['datalines']) continue;
		    echo "<a href=\"".root()."getplotcsv/".basename($dat['csv']).".csv\">".htmlspecialchars($dat['desc'])."</a>";
		    if($showalarms && $dat['csva']) {
			echo " - <a href=\"".root()."getplotcsv/".basename($dat['csva']).".csv\">alarmy</a>";
			$_SESSION->csv_outputs[basename($dat['csva'])]=time();
		    }
		    echo "<br />";
		    $_SESSION->csv_outputs[basename($dat['csv'])]=time(); // weak security
	    }
	    $_SESSION->plot_outputs[basename($fn)]=time();
	    echo "<br />Gnuplot <a href=\"".root()."getplotplot/".basename($fn).".plot\" target=\"_blank\">skript</a><br />";
	} else echo "&nbsp;";
	
//	@unlink($fn);
	echo "</td></tr></table>";
//	print_read($vars);
//	print_read($_SESSION);
}

function plotbin($mv,$from,$to) {
    global $SQL;
    global $_PLOTDIR;

	$qe=$SQL->query("select * from measuring left join room on m_rid=r_id left join building on r_bid=b_id where m_id=".$mv[0]);
	$meas=$qe->obj();
	if(!$meas) {
	    sherr("Chyba dat, neexistující měřící bod");
	    return;
	}
	$qe=$SQL->query("select * from variable where var_id=".$mv[1]);
	$var=$qe->obj();
	if(!$var) {
	    sherr("Chyba dat, neexistující veličina");
	    return;
	}
	
	$plotbase=tempnam($_PLOTDIR,md5(uniqid()));
	if($plotbase===false) {
	    sherr("Nelze vytvořit dočasný soubor");
	    return;
	}
    
	$tabs=array();
	dblock();
	$qe=$SQL->query("show tables like \"valuesblob\\_%\"");
	while($fe=$qe->row()) {
	    if(preg_match("/^valuesblob_(\\d+)$/",$fe[0],$mch)) $tabs[]=$mch[1];
	}
	dbunlock();
	sort($tabs);
	$fromy=gmdate("Y",$from);
	$toy=gmdate("Y",$to);
    
	$slider=array();
	$opts=array();
	$jsarr=array();
	$first=true;
	$iwidth=false;
	$iheight=false;
	foreach($tabs as $yval) {
	    if($yval>=$fromy && $yval<=$toy) { // try select here
		$qe=$SQL->query("select count(*) as cnt from valuesblob_".$yval." where vb_mid=".$mv[0]." && vb_varid=".$mv[1]." && vb_date>=".$from." && vb_date<=".$to);
		$fe=$qe->obj();
		$totcnt=0;
		if($fe) $totcnt=$fe->cnt;
	    
		$chunk=20;
		for($i=0;$i<$totcnt;$i+=$chunk) {
		    $qe=$SQL->query("select * from valuesblob_".$yval." where vb_mid=".$mv[0]." && vb_varid=".$mv[1]." && vb_date>=".$from." && vb_date<=".$to." order by vb_date limit ".$i.",".$chunk);
		    if($SQL->errnum) {
			sherr("Chyba databáze: ".$SQL->errnum);
			return;
		    }
		    while($fe=$qe->obj()) {
			$slider[]=$fe->vb_date;
			$opts[]=showtime($fe->vb_date);
			$data=unserialize($fe->vb_value);
			$jsarr[]="\"".get_ind($data,"value")."\"";
			if($first) {
			    $first=false;
			    $bimg=@imagecreatefromstring(get_ind($data,"data"));
			    if(is_resource($bimg)) {
				$iwidth=imagesx($bimg);
				$iheight=imagesy($bimg);
			    }
			}
		    }
		}
	    }
	}
	echo "<table class=\"graphtable\">";
	echo "<tr>";
	echo "<td rowspan=\"5\" style=\"vertical-align:top;\">";
	if(count($opts)) {
	    echo "<img".($iwidth?" width=\"".$iwidth."\"":"").($iheight?" height=\"".$iheight."\"":"")." id=\"imgview_".$mv[0]."_".$mv[1]."\" src=\"".root()."getplotbin/".basename($plotbase)."/".$slider[count($opts)-1]."\" />";
	}
	else echo "žádná data";
	echo "</td>";
	echo "<td style=\"vertical-align:top;\">";
	if($meas->b_img) echo "<a href=\"".root()."image/".$meas->b_img."\" target=\"_blank\"><img style=\"margin:2px;\" src=\"".root()."image/".$meas->b_img."/max/100/100\" /></a><br />";
	echo htmlspecialchars($meas->b_name);
	echo "</td>";
	echo "</tr>";
	echo "<tr><td style=\"vertical-align:top;\">";
	if($meas->r_img) echo "<a href=\"".root()."image/".$meas->r_img."\" target=\"_blank\"><img style=\"margin:2px;\" src=\"".root()."image/".$meas->r_img."/max/100/100\" /></a><br />";
	echo htmlspecialchars($meas->r_desc." ".$meas->r_floor);
	echo "</td></tr><tr><td style=\"vertical-align:top;\">";
	if($meas->m_img) echo "<a href=\"".root()."image/".$meas->m_img."\" target=\"_blank\"><img style=\"margin:2px;\" src=\"".root()."image/".$meas->m_img."/max/100/100\" /></a><br />";
	echo htmlspecialchars($meas->m_desc);
	echo "</td></tr><tr><td style=\"text-align:center;\">".htmlspecialchars($var->var_desc)."</td></tr>";
	
	if(count($opts)) {
	    echo "<tr><td>Data od: ".$opts[0]."<br />Data do: ".$opts[count($opts)-1]."<br />".input_select("imgsel_".$mv[0]."_".$mv[1],$opts,count($opts)-1);
	//    echo "<br />Hodnota: <span id=\"imgval_".$mv[0]."_".$mv[1]."\">".trim($jsarr[count($opts)-1],"\"")."</span>";
	    echo "<br /><br /><a href=\"".root()."getplotbin/".basename($plotbase)."\" target=\"_blank\">Všechny snímky</a>";
	    echo "</td></tr>";
	    echo "<tr><td colspan=\"2\"><div style=\"margin:6px 10px 6px 10px\"><div id=\"imgslid_".$mv[0]."_".$mv[1]."\"></div></div></td></tr>";
	} else echo "<tr><td>&nbsp;</td></tr>";
	echo "</table>";

    if(count($opts)) {
	echo "<script type=\"text/javascript\">
// <![CDATA[
var binvalues_".$mv[0]."_".$mv[1]."=[".implode(",",$jsarr)."];
var bintimes_".$mv[0]."_".$mv[1]."=[".implode(",",$slider)."];
function changebin_".$mv[0]."_".$mv[1]."() {
    var val=$(\"#imgslid_".$mv[0]."_".$mv[1]."\").slider(\"value\");
    $(\"#imgsel_".$mv[0]."_".$mv[1]."\").val(val);
    $(\"#imgview_".$mv[0]."_".$mv[1]."\").attr(\"src\",\"".root()."getplotbin/".basename($plotbase)."/\"+bintimes_".$mv[0]."_".$mv[1]."[val]);
    $(\"#imgval_".$mv[0]."_".$mv[1]."\").html(binvalues_".$mv[0]."_".$mv[1]."[val]);
}
function makebins_".$mv[0]."_".$mv[1]."() {
    $(\"#imgslid_".$mv[0]."_".$mv[1]."\").slider({
	min:0,
	max:".(count($opts)-1).",
	step:1,
	value:".(count($opts)-1).",
	change: changebin_".$mv[0]."_".$mv[1].",
	slide: changebin_".$mv[0]."_".$mv[1]."
    });
    $(\"#imgsel_".$mv[0]."_".$mv[1]."\").change(function() {
	$(\"#imgslid_".$mv[0]."_".$mv[1]."\").slider(\"option\",\"value\",$(this).val());
    });
}
makebins_".$mv[0]."_".$mv[1]."();
// ]]>
</script>";
	if(!is_array($_SESSION->imgsliders)) $_SESSION->imgsliders=array();
	$_SESSION->imgsliders[basename($plotbase)]=array("age"=>time(),"mid"=>$mv[0],"vid"=>$mv[1],"from"=>$from,"to"=>$to);
    }
//    print_read($_SESSION->imgsliders);
}

function formattext($str) {
    $ret=array();
    foreach(explode("\n",$str) as $val) $ret[]=htmlspecialchars(strtr($val,array("\r"=>"")));
    return implode("<br />",$ret);
}

function plotcomments($mids,$from,$to) {
    global $SQL;
    if(get_ind($_SESSION->mainform,"main_comments")!='Y' || !count($mids)) return;
    
    $cids=array();
    foreach($mids as $val) $cids[]=$val[0];
    $qe=$SQL->query("select * from comment left join measuring on cm_mid=m_id left join room on m_rid=r_id left join building on r_bid=b_id left join user on cm_uid=u_id where cm_mid in (".implode(",",$cids).") && cm_date>=".$from." && cm_date<=".$to." order by cm_date");
    if(!$qe->rowcount()) echo "Žádný komentář";
    else {
	echo "<table class=\"comments\"><tr><th>Datum</th><th>Měřící bod</th><th>Uživatel</th><th>Text</th></tr>";
	while($fe=$qe->obj()) {
	    echo "<tr><td>".showtime($fe->cm_date)."</td>
	    <td>".htmlspecialchars($fe->m_desc)."</td>
	    <td>".htmlspecialchars($fe->u_fullname)."</td>
	    <td>".formattext($fe->cm_text)."</td></tr>";
	}
	echo "</table>";
    }
}

function varordersort($e1,$e2) {
    if($e1->var_left<$e2->var_left) return 1;
    if($e1->var_left>$e2->var_left) return -1;
    return 0;
}

function setguirange($from,$to) {
    global $_IGNOREDST;
    if($_IGNOREDST) {
	$fromdate=gmdate("Y-m-d",$from+3600);
	$todate=gmdate("Y-m-d",$to+3600);
	$fromh=gmdate("G",$from+3600);
	$toh=gmdate("G",$to+3600);
    } else {
	$fromdate=date("Y-m-d",$from);
	$todate=date("Y-m-d",$to);
	$fromh=date("G",$from);
	$toh=date("G",$to);
    }
    $_SESSION->maingraph['settimerange']=true;
    $_SESSION->maingraph['rangefrom']=array($fromdate,$fromh,0);
    $_SESSION->maingraph['rangeto']=array($todate,$toh,0);
// set that also for nextpost
    $_SESSION->mainform['001_main_from']=$fromdate;
    $_SESSION->mainform['001_main_from_h']=$fromh;
    $_SESSION->mainform['001_main_from_m']=0;
    $_SESSION->mainform['001_main_to']=$todate;
    $_SESSION->mainform['001_main_to_h']=$toh;
    $_SESSION->mainform['001_main_to_m']=0;
}

function dograph() {
    global $SQL;

	$vars=array();
	foreach($_SESSION->mainform as $key=>$val) {
	    if(preg_match("/^000_main_vargr_(\\d+)$/",$key,$mch) && $val=='Y') {
		$qe=$SQL->query("select * from variable left join varcodes on vc_text=var_code where var_id=".$mch[1]);
		$fe=$qe->obj();
		if(!$fe) {
		    sherr("Neznámá veličina");
		    return;
		}
		$vars[]=$fe;
	    }
	}
	usort($vars,"varordersort");
	
	$relativfrom=(get_ind($_SESSION->mainform,"main_lastrawto")=='Y');
	if($relativfrom) {
	    $current=0;
	    foreach($_SESSION->datatogen_mids as $val) {
		foreach($vars as $vv) {
		    $qe=$SQL->query("select * from varmeascache where vmc_mid=".$val." && vmc_varid=".$vv->var_id);
		    $fe=$qe->obj();
		    if($fe && $fe->vmc_lastrawtime>$current) $current=$fe->vmc_lastrawtime;
		}
	    }
	} else $current=time();
	
	$from=false;
	$to=false;
	$uphour=floor($current/3600);
	$uphour=($uphour+1)*3600;
	switch(get_ind($_SESSION->mainform,"main_time")) {
	case 1:
	    $from=0;
	    $to=gmmktime(0,0,0,1,1,2100);
	    break;
	case 2:
	    $from=gettime(get_ind($_SESSION->mainform,"001_main_from")." ".get_ind($_SESSION->mainform,"001_main_from_h").":".get_ind($_SESSION->mainform,"001_main_from_m"));
	    $to=gettime(get_ind($_SESSION->mainform,"001_main_to")." ".get_ind($_SESSION->mainform,"001_main_to_h").":".get_ind($_SESSION->mainform,"001_main_to_m"));
	    break;
	case 3:
	    $to=$uphour;
	    $from=$to-(31536000*3);
	    setguirange($from,$to);
	    break;
	case 4:
	    $to=$uphour;
	    $from=$to-31536000;
	    setguirange($from,$to);
	    break;
	case 5:
	    $to=$uphour;
	    $from=$to-15768000;
	    setguirange($from,$to);
	    break;
	case 6:
	    $to=$uphour;
	    $from=$to-2628000;
	    setguirange($from,$to);
	    break;
	case 7:
	    $to=$uphour;
	    $from=$to-(86400*7);
	    setguirange($from,$to);
	    break;
	case 8:
	    $to=$uphour;
	    $from=$to-86400;
	    setguirange($from,$to);
	    break;
	}

	if(!count($vars)) {
	    sherr("Nezvolena veličina");
	    return;
	}
	if(!count($_SESSION->datatogen_mids)) {
	    sherr("Nezvolen měřící bod");
	    return;
	}
	if($to===false || $from===false) {
	    sherr("Neplatný časový rozsah");
	    return;
	}

	deltemps();
	$yesbin=array();
	$nobin=array();
        foreach($_SESSION->datatogen_mids as $val) {
	    foreach($vars as $vv) {
		if($vv->vc_bin!='Y') $nobin[]=array($val,$vv->var_id);
		else $yesbin[]=array($val,$vv->var_id);
	    }
	}
	
	if(count($nobin)) {
		if(get_ind($_SESSION->mainform,"main_groupmeas")=='Y') {
		    if(get_ind($_SESSION->mainform,"main_groupvars")=='Y') {
			$i=0;
			$lgen=array();
			foreach($vars as $vv) {
			    $p=count($lgen);
			    foreach($nobin as $val) {
				if($val[1]==$vv->var_id) $lgen[]=$val;
			    }
			    if(count($lgen)!=$p) $i=1-$i;
			    if(!$i && count($lgen)) {
				plotgraph($lgen,$from,$to);
				plotcomments($lgen,$from,$to);
				$lgen=array();
			    }
			}
			if(count($lgen)) {
			    plotgraph($lgen,$from,$to);
			    plotcomments($lgen,$from,$to);
			}
		    } else {
			foreach($vars as $vv) {
			    $lgen=array();
			    foreach($nobin as $val) {
				if($val[1]==$vv->var_id) $lgen[]=$val;
			    }
			    if(count($lgen)) {
				plotgraph($lgen,$from,$to);
				plotcomments($lgen,$from,$to);
			    }
			}
		    }
		} else {
		    if(get_ind($_SESSION->mainform,"main_groupvars")=='Y') {
			$fm=0;
			foreach($nobin as $gm) {
			    if($fm==$gm[0]) continue;
			    $fm=$gm[0];
			    $i=0;
			    $lgen=array();
			    foreach($vars as $vv) {
				$p=count($lgen);
				foreach($nobin as $val) {
				    if($val[0]==$gm[0] && $val[1]==$vv->var_id) $lgen[]=$val;
				}
				if(count($lgen)!=$p) $i=1-$i;
				if(!$i && count($lgen)) {
				    plotgraph($lgen,$from,$to);
				    plotcomments($lgen,$from,$to);
				    $lgen=array();
				}
			    }
			    if(count($lgen)) {
				plotgraph($lgen,$from,$to);
				plotcomments($lgen,$from,$to);
			    }
			}
		    } else {
			foreach($nobin as $val) {
			    plotgraph(array($val),$from,$to);
			    plotcomments(array($val),$from,$to);
			}
		    }
		}
	}
	if(count($yesbin)) {
	    foreach($yesbin as $val) {
		plotbin($val,$from,$to);
		plotcomments(array($val),$from,$to);
	    }
	}
}

if(!is_array($_SESSION->datatogen_mids)) $_SESSION->datatogen_mids=array();
if(!is_array($_SESSION->maingraph)) {
    $json=array("graph"=>"&nbsp;","uvars"=>array(),"checks"=>array(),"tohid"=>array());
    $_SESSION->maingraph=$json;
}
if(!is_array($_SESSION->mainform)) $_SESSION->mainform=array();
$_SESSION->maingraph['settimerange']=false;

function genchecks() {
    $ret=array();
    foreach($_SESSION->datatogen_mids as $val) $ret[]="#mid_".$val;
    $_SESSION->maingraph['checks']=$ret;
}

function genhidden($force=false) {
    global $SQL;
    global $_IGNOREDST;
    
    $depsel=false;
    if(strlen(get_ind($_SESSION->mainform,"maintree_depsel"))>1) $depsel=hex2bin(get_ind($_SESSION->mainform,"maintree_depsel"));
    $mdact=(get_ind($_SESSION->mainform,"maintree_showdea")=='Y');

    $cities=array();
    $builds=array();
    $rooms=array();
    $meas=array();
    
    $currm=array();
    foreach($_SESSION->datatogen_mids as $val) $currm[$val]=true;
    
    $qe=$SQL->query("select * from building");
    while($fe=$qe->obj()) {
	$cities[$fe->b_city]=0;
	$builds[$fe->b_id]=0;
    }
    $qe=$SQL->query("select * from room");
    while($fe=$qe->obj()) $rooms[$fe->r_id]=0;
    
    $qe=$SQL->query("select * from measuring left join room on m_rid=r_id left join building on r_bid=b_id left join sensor on s_mid=m_id");
    while($fe=$qe->obj()) {
	$meas[$fe->m_id]=0;
	if(!$depsel || $fe->m_depart==$depsel) {
	    if($fe->m_active=='Y' || $mdact) {
		// this measpoint will be here
		$cities[$fe->b_city]++;
		$builds[$fe->b_id]++;
		$rooms[$fe->r_id]++;
		$meas[$fe->m_id]++;
	    } else {
		if(get_ind($currm,$fe->m_id)) $currm[$fe->m_id]=false;
	    }
	} else {
	    if(get_ind($currm,$fe->m_id)) $currm[$fe->m_id]=false;
	}
    }
    $tohid=array();
    foreach($cities as $key=>$val) if(!$val) $tohid[]="#".bin2hex($key);
    foreach($builds as $key=>$val) if(!$val) $tohid[]="#bid_".$key;
    foreach($rooms as $key=>$val) if(!$val) $tohid[]="#rid_".$key;
    foreach($meas as $key=>$val) if(!$val) $tohid[]="#mid_".$key;
    $_SESSION->maingraph['tohid']=$tohid;
    
// ----------------
    $remids=array();
    foreach($currm as $key=>$val) {
	if($val) $remids[]=$key;
    }
    if(count($remids)!=count($_SESSION->datatogen_mids) || $force) {
	$_SESSION->datatogen_mids=$remids;
	
	$_SESSION->maingraph['graph']="Obnovte zobrazení grafu";
	ob_start();
	$_IGNOREDST=true;
	dograph();
	$_IGNOREDST=false;
	$graph=ob_get_clean();
	$undervars=array();
	if(count($_SESSION->datatogen_mids)) {
	    $avars=array();
	    $qe=$SQL->query("select * from varmeascache where vmc_mid in (".implode(",",$_SESSION->datatogen_mids).")");
	    while($fe=$qe->obj()) $avars[$fe->vmc_varid]=true;
	    foreach($avars as $key=>$val) $undervars[]="#mcheck_".$key;
	}
	$_SESSION->maingraph['graph']=$graph;
	$_SESSION->maingraph['uvars']=$undervars;
    }
}

function profilecreate() {
    global $SQL;
    header("Content-type: application/json");
    
    $prf=trim(get_ind($_POST,"main_uprfname"));
    if(!strlen($prf)) {
	echo json_encode(array("res"=>false,"txt"=>"Nezadán název profilu"));
	return;
    }
    $profs=$_SESSION->getprofiles();
    $profs[$prf]=array("mids"=>$_SESSION->datatogen_mids);
    $_SESSION->setprofiles($profs);
    
    $sarr=array(0=>"(žádný)");
    foreach($profs as $key=>$val) $sarr[bin2hex($key)]=htmlspecialchars($key);
    if(!is_array($_SESSION->mainform)) $_SESSION->mainform=array();
    $_SESSION->mainform['main_uprfsel']=bin2hex($prf);
    
    echo json_encode(array("res"=>true,"profs"=>$sarr,"sel"=>bin2hex($prf)));
}

function profileremove() {
    global $SQL;
    header("Content-type: application/json");
    
    $prf=hex2bin(get_ind($_POST,"main_uprfsel"));
    $profs=$_SESSION->getprofiles();
    if(get_ind($profs,$prf)) {
	unset($profs[$prf]);
	if(!is_array($_SESSION->mainform)) $_SESSION->mainform=array();
	$_SESSION->mainform['main_uprfsel']=false;
	$_SESSION->setprofiles($profs);
    }
    
    $sarr=array(0=>"(žádný)");
    foreach($profs as $key=>$val) $sarr[bin2hex($key)]=htmlspecialchars($key);
    
    echo json_encode(array("res"=>true,"profs"=>$sarr,"sel"=>0));
}

if($_SERVER['REQUEST_METHOD']=="POST") {
    $_SESSION->temp_form=false;
    $_SESSION->invalid=false;
    // otherwise, only refresh with other time settings or something

    $_SESSION->mainform=$_POST;

	switch($ARGC) {
	case 0:
	    header("Content-type: application/json");
	    genhidden(true);
	    genchecks();
	    echo json_encode($_SESSION->maingraph);
	    break;
	case 1:
	    switch(get_ind($ARGV,0)) {
	    case "clear":
		$_SESSION->datatogen_mids=array();
		header("Content-type: application/json");
		genhidden(true);
		genchecks();
		$_SESSION->updateprofile();
		echo json_encode($_SESSION->maingraph);
		break;
	    case "prfcreate":
		profilecreate();
		break;
	    case "prfremove":
		profileremove();
		break;
	    case "prfchange":
		$curr=hex2bin(get_ind($_POST,"main_uprfsel"));// get current profile from $_SESSION not $_POST
		$prf=$_SESSION->getprofiles();
		$cp=get_ind($prf,$curr);
		if($cp) {
		    $_SESSION->datatogen_mids=$cp['mids'];
		    genhidden(true);
		    genchecks();
		}
		$_SESSION->updateprofile(); // dont restore from no profile
		header("Content-type: application/json");
		echo json_encode($_SESSION->maingraph);
		break;
	    case "gettree":
		$curr=hex2bin(get_ind($_POST,"main_uprfsel"));// gets current profile from $_SESSION not $_POST
		$prf=$_SESSION->getprofiles();
		$cp=get_ind($prf,$curr);
		if($cp) $_SESSION->datatogen_mids=$cp['mids'];
		else { // try default
		    $cp=$_SESSION->getdefaultprofile();
		    if(is_array($cp)) $_SESSION->datatogen_mids=$cp['mids'];
		}
		header("Content-type: application/json");
		genhidden(!$_SESSION->somegraphdone); // maybe not with true
		genchecks();
		$_SESSION->updateprofile();
		$_SESSION->somegraphdone=true;
		echo json_encode($_SESSION->maingraph);
		break;
	    default:
		header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
	    }
	    break;
	case 2:
	    switch(get_ind($ARGV,0)) {
	    case "add":
// permission check should be here
		if(!preg_match("/^mid_(\\d+)$/",get_ind($ARGV,1),$mch)) break;
		$mid=$mch[1];
		$qe=$SQL->query("select * from measuring where m_id=".$mid);
		if(!$qe->rowcount()) break;
		$has=false;
		foreach($_SESSION->datatogen_mids as $val) if($val==$mid) { $has=true; break; }
		if(!$has) $_SESSION->datatogen_mids[]=$mid;
		break;
	    case "rem":
		if(!preg_match("/^mid_(\\d+)$/",get_ind($ARGV,1),$mch)) break;
		$mid=$mch[1];
		$mids=array();
		foreach($_SESSION->datatogen_mids as $val) {
		    if($val!=$mid) $mids[]=$val;
		}
		$_SESSION->datatogen_mids=$mids;
		break;
	    }
	    header("Content-type: application/json");
	    genhidden(true);
	    genchecks();
	    $_SESSION->updateprofile();
	    echo json_encode($_SESSION->maingraph);
	    break;
	default:
	    header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
	}
} else {

switch($ARGC) {
case 2:
    switch(get_ind($ARGV,0)) {
    case "treedep":
	$_SESSION->mainform['maintree_depsel']=get_ind($ARGV,1);
	header("Content-type: application/json");
	genhidden();
	genchecks();
	$_SESSION->updateprofile();
	echo json_encode($_SESSION->maingraph);
	break;
    case "treeshowdea":
	$_SESSION->mainform['maintree_showdea']=get_ind($ARGV,1);
	header("Content-type: application/json");
	genhidden();
	genchecks();
	$_SESSION->updateprofile();
	echo json_encode($_SESSION->maingraph);
	break;
    default:
	over();
    }
    break;
default:
    over();
}

}
