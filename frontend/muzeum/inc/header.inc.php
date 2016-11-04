<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<?php
    if(isset($_STYLE)) {
	if(is_array($_STYLE)) {
	    foreach($_STYLE as $val) {
		echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"".$_ROOTPATH."css/".$val."\" />\n";
	    }
	} else echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"".$_ROOTPATH."css/".$_STYLE."\" />\n";
    }
?>
<title>Automatic Museum Monitoring</title>
</head>
<body>
<?php echo "<script src=\"".root()."js/jquery-1.11.2.min.js\"></script>";
echo "<script src=\"".root()."js/jquery-ui.min.js\"></script>"; ?>

<?php

echo "<div id=\"divheader\" class=\"header\">";

if($_SESSION->user) echo "<div class=\"user\"><span class=\"man\"><a href=\"".root()."user\">".htmlspecialchars($_SESSION->user->u_uname)."</a> / <a href=\"".root()."logout\">odhlásit</a></span></div>";

echo "</div>";
