<?php
/**
 * This is a small piece of dbPdo for doWebServer.php.
 * This is part of the SiteClass framwork.
 * The webServer.php is placed in the DigitalOcean server in /var/www/bartonlp.com/otherpages.
 * It is symlinked from the SiteClass framwork to /var/www/bartonlp.com/otherpages.
 * NOTE, these may be changed to /var/www/bartonlp.com/otherpages.
 * The actual symlink is /var/www/bartonlp/otherpages/doWebServer.php -> ../../vendor/bartonlp/site-class/src/doWebServer.php.
 */
$_site = require_once getenv("SITELOADNAME");
$db = new dbPdo($_site);
$db->doWebServer();
exit;

