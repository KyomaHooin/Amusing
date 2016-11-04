<?php

$db_config=array(
"_DOMAIN"=>"yourwebsite.com",
"_ROOTPATH"=>"/muzeum/",
"_DEFAULTPAGE"=>"main",
"_SENDMAIL"=>true,
"_PERPAGE"=>50,
"_LOCKFILE"=>"/tmp/phpdbproj.lock",
"_LOCKDIR"=>"/var/www/muzeum/lock/",
"_CSVDIR"=>"/var/www/muzeum/csv/",
"_PLOTDIR"=>"/var/www/muzeum/plot/",
"_PLOTW"=>1920,
"_PLOTH"=>1080,
"_ALARMMAIL"=>"webmaster@yourwebsite.com",
"_MAXDATAAGE"=>30,
"_BINSPERPAGE"=>20
);

$qe=$SQL->query("select * from setup");
while($fe=$qe->obj()) $db_config[$fe->set_variable]=$fe->set_value;
extract($db_config);

//$_ROOTPATH="/muzeum/";
