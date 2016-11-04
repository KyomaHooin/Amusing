<?php

function mainexc($e) {
    echo "Uncaught exception: ".$e->getMessage();
    exit();
}

set_exception_handler("mainexc");

require_once "core/init.inc.php";

ob_start();
require_once "pages/".$PAGE.".inc.php";
$html=ob_get_contents();
ob_end_clean();

if(!$_NOHEAD) {
    require_once "inc/header.inc.php";
}
echo $html;
if(!$_NOHEAD) require_once "inc/footer.inc.php";
