<?php

echo "varmeascache<br />";

dblock();
//$SQL->query("delete from varmeascache");
$qe=$SQL->query("show tables");
while($fe=$qe->row()) {
    if(preg_match('/^values_\d+$/',$fe[0])) {
	$qe2=$SQL->query("select * from ".$fe[0]." group by v_mid,v_varid");
//	while($fe2=$qe2->obj()) $SQL->query("insert into varmeascache set vmc_mintime=2556140399,vmc_mid=".$fe2->v_mid.",vmc_varid=".$fe2->v_varid);
    } else if(preg_match('/^valuesblob_\d+$/',$fe[0])) {
	$qe2=$SQL->query("select * from ".$fe[0]." group by vb_mid,vb_varid");
//	while($fe2=$qe2->obj()) $SQL->query("insert into varmeascache set vmc_mintime=2556140399,vmc_mid=".$fe2->vb_mid.",vmc_varid=".$fe2->vb_varid);
    } else if(preg_match('/^rawvalues_\d+$/',$fe[0])) {
	$qe2=$SQL->query("select *,max(rv_date) as max from ".$fe[0]." group by rv_mid,rv_varid");
	while($fe2=$qe2->obj()) {
	    $qe3=$SQL->query("select * from ".$fe[0]." where rv_mid=".$fe2->rv_mid." && rv_varid=".$fe2->rv_varid." && rv_date=".$fe2->max);
	    $fe3=$qe3->obj();

    $SQL->query("insert into varmeascache set 
	    vmc_mid=".$fe2->rv_mid.",
	    vmc_varid=".$fe2->rv_varid.",
	    vmc_mintime=2556140399,
	    vmc_maxtime=0,
	    vmc_lastrawtime=".$fe2->max.",
	    vmc_lastrawvalue=\"".$SQL->escape($fe3->rv_value)."\"
	on duplicate key update 
	    vmc_lastrawtime=greatest(".$fe2->max.",vmc_lastrawtime),
	    vmc_lastrawvalue=if(vmc_lastrawtime>".$fe2->max.",vmc_lastrawvalue,\"".$SQL->escape($fe3->rv_value)."\")");

//	    $SQL->query("insert into varmeascache set vmc_mintime=2556140399,vmc_mid=".$fe2->rv_mid.",vmc_varid=".$fe2->rv_varid.",vmc_lastval=".$fe2->rv_value."
//		on duplicate key update vmc_lastval=if(vmc_maxtime>".$fe2->max.",vmc_lastval,".$fe2->rv_value.")");
//	    echo("insert into varmeascache set vmc_mintime=2556140399,vmc_mid=".$fe2->rv_mid.",vmc_varid=".$fe2->rv_varid.",vmc_lastval=".$fe2->rv_value."
//		on duplicate key update vmc_lastval=if(vmc_maxtime>".$fe2->max.",vmc_lastval,".$fe2->rv_value.")");
//	    echo "<br />";
	}
    }
}
dbunlock();

echo "done<br />";

if($_SERVER['REQUEST_METHOD']=="POST") {
    redir();
}
