<?php
//require-once('/var/www/includes/SiteClass.class.php'); // path to your SiteClass.class.php file.
//require_once("vendor/bartonlp/SiteClass/siteautoload.class.php"); // Composer install
require_once("../includes/SiteClass.class.php");

$siteinfo = array(
  'siteDomain' => "localhost",
  'siteName' => "Vbox Localhost",
  'copyright' => "2015 Barton L. Phillips",
);

$S = new SiteClass($siteinfo);

list($top, $footer) = $S->getPageTopBottom();
echo <<<EOF
$top
<h1>Test 1</h1>
<p>Stuff</p>
<hr>
$footer
EOF;
