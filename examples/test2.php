<?php  
// test2.php

require_once("../includes/SiteClass.class.php");

$siteinfo = array(
  'siteDomain' => "localhost",
  'siteName' => "Vbox Localhost",
  'copyright' => "2015 Barton L. Phillips",
  'memberTable' => 'members',
  'dbinfo' => array(
    'database' => 'test.sdb',
    'engine' => 'sqlite3'
  ),
  'count' => false
);

ErrorClass::setNoEmailErrs(true);
ErrorClass::setDevelopment(true);

$S = new SiteClass($siteinfo);

list($top, $footer) = $S->getPageTopBottom();

// Do some database operations
$S->query("select fname||' '||lname from {$siteinfo['memberTable']}");

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
