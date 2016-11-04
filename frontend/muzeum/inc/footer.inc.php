<?php

if(count($_JQUERY)) {
    echo "<script type=\"text/javascript\">
// <![CDATA[
    $(function() {
    ".implode("",$_JQUERY)."
    });
// ]]>
</script>";
}

echo "<div>";
echo "<p id=\"imgfooter\">";
echo "<a href=\"http://www.nm.cz\" target=\"_blank\"><img src=\"".root()."images/LogoNM_ANGL_cmyk_01.png\" /></a>";
echo "<a href=\"http://www.mkcr.cz\" target=\"_blank\"><img src=\"".root()."images/Ministry_of_Culture.jpg\" /></a>";
echo "<a href=\"http://www.itam.cas.cz\" target=\"_blank\"><img src=\"".root()."images/image005.png\" /></a>";
echo "</p>";
echo "</div>";
echo "</body></html>";
