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

echo "<div id=\"divfooter\">";
//echo "<p id=\"thankfooter\">";
//echo "Projekt „Jednotný modulární systém dálkového on-line sledování environmentálních charakteristik depozitářů a expozic“ (č. DF-12P01OVV27) je řešen z prostředků účelové podpory poskytnuté z Programu aplikovaného výzkumu a vývoje národní kulturní identity (NAKI) Ministerstva kultury České republiky. Řešitelé touto cestou děkují Ministerstvu kultury ČR za možnost projekt uskutečnit.";
//echo "</p>";
echo "<p id=\"imgfooter\">";
echo "<a href=\"http://www.nm.cz\" target=\"_blank\"><img src=\"".root()."images/LogoNM_ANGL_cmyk_01.png\" /></a>";
echo "<a href=\"http://www.mkcr.cz\" target=\"_blank\"><img src=\"".root()."images/Ministry_of_Culture.jpg\" /></a>";
echo "<a href=\"http://www.itam.cas.cz\" target=\"_blank\"><img src=\"".root()."images/image005.png\" /></a>";
echo "</p>";
echo "</div>";

echo "</body></html>";
