<?php

$_NOHEAD=true;

function over() {
    header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
    exit();
}

function plotgraph($togen,$from,$to,$usescales=true,$showextremes=true,$showalarms=true,$setcolors=true,$derivate=false) {
    global $SQL;
    global $_PLOTW;
    global $_PLOTH;

	$vars=array();
	$mids=array();
	foreach($togen as $val) {
	    $mids[]=array("mid"=>$val[0],"var"=>$val[1]);
	    $vars[$val[1]]=array('d'=>array());
	}
	if(!count($vars) || count($vars)>2) over();
	$qe=$SQL->query("select * from variable left join varcodes on vc_text=var_code where var_id in (".implode(",",array_keys($vars)).")");
	if($qe->rowcount()!=count($vars)) over();

	while($fe=$qe->obj()) {
	    $vdat=unserialize($fe->var_plotdata);
	    $vars[$fe->var_id]['id']=$fe->var_id;
	    $vars[$fe->var_id]['left']=(int)$fe->var_left;
	    $vars[$fe->var_id]['n']=$fe->var_desc;
	    $vars[$fe->var_id]['u']=$fe->var_unit;
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
	    if(preg_match('/^values_(\d+)$/',$fe[0],$mch)) $tabs[]=$mch[1];
	}
	dbunlock();
	sort($tabs);
	$fromy=gmdate("Y",$from);
	$toy=gmdate("Y",$to);
	
	foreach($mids as $val) {
	    ob_start();
	    $first=true;
	    $max=array(0,0);
	    $min=array(0,0);
	    $avg=0.0;
	    $sq=0.0;
	    $totcnt=0;
	    $firsttime=false;
	    $lasttime=false;
	    $lastval=false;
	    $values=array();
	    
	    $qe=$SQL->query("select * from varmeascache where vmc_mid=".$val['mid']." && vmc_varid=".$val['var']);
	    $fe=$qe->obj();
	    if($fe) $lastval=$fe->vmc_lastrawvalue;
	    
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
			    } else {
				if($fe->v_value>$max[0]) $max=array($fe->v_value,$fe->v_date);
				if($fe->v_value<$min[0]) $min=array($fe->v_value,$fe->v_date);
				if($fe->v_date!=$lasttime) {
				    $der=($fe->v_value-$lastval)/(($fe->v_date-$lasttime)/3600);
				} else $der=0;
				$lastval=$fe->v_value;
				//if($mainperiod && $lasttime+$mainperiod<$fe->v_date) {
				//    for($lasttime+=$mainperiod;$lasttime<$fe->v_date;$lasttime+=$mainperiod) echo $lasttime.";NaN;NaN\n";
				//    $der="NaN";
				//}
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
				$lasttime=$firsttime=$fe->v_date;
			    } else {
				if($fe->v_value>$max[0]) $max=array($fe->v_value,$fe->v_date);
				if($fe->v_value<$min[0]) $min=array($fe->v_value,$fe->v_date);
				//if($mainperiod && $lasttime+$mainperiod<$fe->v_date) {
				//    for($lasttime+=$mainperiod;$lasttime<$fe->v_date;$lasttime+=$mainperiod) echo $lasttime.";NaN\n";
				//}
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
		$csvdata=ob_get_clean();
	    } else {
		ob_end_clean();
		$csvdata=false;
	    }
	    
	    $fna=false;
	    if($totcnt && $showalarms) {
		$qe=$SQL->query("select * from alarmlog where al_vid=".$val['var']." && al_mid=".$val['mid']." && al_date>=".$from." && al_date<=".$to." && al_edge='R' order by al_date");
		if($qe->rowcount()) {
		    ob_start();
		    while($fe=$qe->obj()) {
			echo $fe->al_date.";".$fe->al_value.";".c_alarm_gen::getdescbyname($fe->al_class)."\n"; // could be separated edges, now only rising edge
		    }
		    $fna=ob_get_clean();
		}
	    }
	    
	    $qe=$SQL->query("select * from measuring left join room on m_rid=r_id left join building on r_bid=b_id where m_id=".$val['mid']);
	    $fe=$qe->obj();
	    if(!$fe) over();
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
		"csv"=>$csvdata,
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
		"lastval"=>$lastval,
		"sigma"=>($totcnt?sqrt(($sq-$totcnt*$avg*$avg)/$totcnt):0.0),
		"median"=>$median
	    );
	}
	
// generating output html, files should be cleaned every midnight cca

	$timezone=3600;
	ob_start();
	//echo "set terminal png size ".$_PLOTW.",".$_PLOTH."\n";
	echo "set terminal png size 1754,1240\n";
	
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
	    echo "unset xlabel\n";
	    
// has to draw all derivation graphs in two y axis
	    $cnt=1;
	    $tostdin="";
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

		    $tostdin.=$dat['csv'];
		    $tostdin.="e\n";
		    if($setcolors) $plot[]="\"-\" using ($1+".$timezone."):($3) with lines".($val['color']?" linecolor rgb \"".$val['color']."\"":"")." title \"".addcslashes("derivace ".$dat['desc'],"\"")."\" axes x1y".$cnt;
		    else $plot[]="\"-\" using ($1+".$timezone."):($3) with lines title \"".addcslashes("derivace ".$dat['desc'],"\"")."\" axes x1y".$cnt;
		    $totcnt++;
		}
		$cnt++;
	    }
	    echo "set size 1.0,0.4\n";
	    echo "set origin 0.0,0.0\n";
	    
	    echo "set xrange [] writeback\n";
	    echo "plot ".implode(",\\\n",$plot)."\n";
	    echo $tostdin;
	    
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

	$tostdin="";
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
				$tostdin.=$dat['csv'];
				$tostdin.="e\n";
				$plot[]="\"-\" using ($1+".$timezone."):($2>".$ext."?$2:1/0) title \"".addcslashes($dat['desc'],"\"")." > ".$ext."\" axes x1y".$cnt." w filledcurves above y".$cnt."=".$ext." fs transparent solid .1";
				break;
			    case 1:
				$tostdin.=$dat['csv'];
				$tostdin.="e\n";
				$plot[]="\"-\" using ($1+".$timezone."):($2<".$ext."?$2:1/0) title \"".addcslashes($dat['desc'],"\"")." < ".$ext."\" axes x1y".$cnt." w filledcurves below y".$cnt."=".$ext." fs transparent solid .1";
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
		if($setcolors) $plot[]="\"-\" using ($1+".$timezone."):($2) with lines".($val['color']?" linecolor rgb \"".$val['color']."\"":"")." title \"".addcslashes($dat['desc'],"\"")."\" axes x1y".$cnt;
		else $plot[]="\"-\" using ($1+".$timezone."):($2) with lines title \"".addcslashes($dat['desc'],"\"")."\" axes x1y".$cnt;

		$tostdin.=$dat['csv'];
		$tostdin.="e\n";
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
			$tostdin.=$dat['csva'];
			$tostdin.="e\n";
			$plot[]="\"-\" using ($1+".$timezone."):($2) title \"alarm ".addcslashes($dat['desc'],"\"")."\" pt 7 axes x1y".$cnt;
		    }
		}
		$cnt++;
	    }
	}

	echo "plot ".implode(",\\\n",$plot)."\n";
	echo $tostdin;
	if($havemulti) echo "unset multiplot\n";
	
//	file_put_contents("/tmp/out.gp",ob_get_clean());
//	exit();

	$gp=proc_open("/usr/bin/gnuplot",array(0=>array('pipe','r'),1=>array('pipe','w'),2=>array('pipe','w')),$pipes);
	if(!is_resource($gp)) {
	    ob_get_clean();
	    over();
	}
	
	fwrite($pipes[0],ob_get_clean());
	//fwrite($pipes[0],$tostdin);
	fclose($pipes[0]);
	
	$res=stream_get_contents($pipes[1]);
	fclose($pipes[1]);
	$rerr=stream_get_contents($pipes[2]);
	fclose($pipes[2]);
	
	proc_close($gp);
	if(!@imagecreatefromstring($res)) return file_get_contents("images/nodata.png");
	return $res;
}

if($_SERVER['REQUEST_METHOD']=="POST") over();

if($ARGC!=7) over();

$mids=array();
$newest=0;
foreach(explode("-",$ARGV[0]) as $val) {
    if(!preg_match('/^(\d+)_(\d+)$/',$val,$mch)) over();
    $mids[]=array($mch[1],$mch[2]);
    $qe=$SQL->query("select * from varmeascache where vmc_mid=".$mch[1]." && vmc_varid=".$mch[2]);
    $fe=$qe->obj();
    if($fe && $fe->vmc_lastrawtime>$newest) $newest=$fe->vmc_lastrawtime;
}
if(!$newest) $newest=time();

//$current=time();
$current=$newest;
$uphour=floor($current/3600);
$uphour=($uphour+1)*3600;
switch($ARGV[1]) {
case "1M":
    $to=$uphour;
    $from=$to-(86400*31);
    break;
case "1W":
    $to=$uphour;
    $from=$to-(86400*14);
    break;
case "7D":
    $to=$uphour;
    $from=$to-(86400*7);
    break;
case "1D":
    $to=$uphour;
    $from=$to-86400;
    break;
default:
    over();
}

$usescales=($ARGV[2]=='1');
$showextremes=($ARGV[3]=='1');
$showalarms=($ARGV[4]=='1');
$setcolors=($ARGV[5]=='1');
$derivate=($ARGV[6]=='1');

$args=array($mids,$ARGV[1],$usescales,$showextremes,$showalarms,$setcolors,$derivate);

$SQL->query("delete from plotcache where TIMESTAMPDIFF(minute,pc_date,now())>15");

$hash=md5(serialize($args));
$qe=$SQL->query("select *,TIMESTAMPDIFF(minute,pc_date,now()) as diff from plotcache where pc_hash=\"".$SQL->escape($hash)."\" && pc_args=\"".$SQL->escape(serialize($args))."\" limit 1");
$fe=$qe->obj();
if(!$fe) { // generate
    $res=plotgraph($mids,$from,$to,$usescales,$showextremes,$showalarms,$setcolors,$derivate);
    if($res===false) over();
    $SQL->query("insert into plotcache set
	pc_hash=\"".$SQL->escape($hash)."\",
	pc_date=now(),
	pc_args=\"".$SQL->escape(serialize($args))."\",
	pc_data=\"".$SQL->escape($res)."\"");
} else {
    if($fe->diff>15) { // too old
	$res=plotgraph($mids,$from,$to,$usescales,$showextremes,$showalarms,$setcolors,$derivate);
	if($res===false) over();
	$SQL->query("update plotcache set
		pc_date=now(),
		pc_data=\"".$SQL->escape($res)."\"
	    where
		pc_hash=\"".$SQL->escape($hash)."\" &&
		pc_args=\"".$SQL->escape(serialize($args))."\"");
    } else $res=$fe->pc_data;
}

header("Content-type: image/png");
header("Content-length: ".strlen($res));
echo $res;
