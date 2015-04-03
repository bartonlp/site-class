<?php  
// test2.php

//require_once('/var/www/includes/SiteClass.class.php'); // path to your SiteClass.class.php file.
//require_once("vendor/bartonlp/SiteClass/siteautoload.class.php"); // Composer install
require_once("../includes/SiteClass.class.php");

$siteinfo = array(
  'siteDomain' => "localhost",
  'siteName' => "Vbox Localhost",
  'copyright' => "2015 Barton L. Phillips",
  'memberTable' => 'pokermembers',
  'dbinfo' => array(
    'host' => 'localhost',
    'user' => 'barton',
    'password' => '7098653',
    'database' => 'barton',
    'engine' => 'mysqli'
  ),
);

Error::setNoEmailErrs(true);
Error::setDevelopment(true);

$S = new SiteClass($siteinfo);

list($top, $footer) = $S->getPageTopBottom();

// Do some database operations
$S->query("select concat(fname, ' ', lname) from {$siteinfo['memberTable']}");

$names = '';

while(list($name) = $S->fetchrow('num')) {
  $names .= "$name<br>";
}

echo <<<EOF
$top
<h1>Test 2</h1>
<p>$names</p>
<hr>
$footer
EOF;
