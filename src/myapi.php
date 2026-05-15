<?php
/**
 * This is a small piece of dbPdo for myapi.php.
 * This is part of the SiteClass framwork.
 * The myapi.php is placed in the DigitalOcean server in /var/www/bartonlp.com/otherpages.
 * It is symlinked from the SiteClass framwork (currently on HP-envy /home/barton/site-class/src)
 * to /var/www/bartonlp.com/otherpages.
 * NOTE, these may be changed to /var/www/bartonlp.com/otherpages and HP-envy /home/barton/site-class/src.
 * The actual symlink is /var/www/bartonlp/otherpages/myapi.php -> ../../vendor/bartonlp/site-class/src/myapi.php.
 */
$_site = require_once getenv("SITELOADNAME");
$db = new dbPdo($_site);
$db->doWebServer();
exit;

