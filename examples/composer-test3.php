<?php
// composer-test3.php

require_once("../../../autoload.php");

$siteinfo = array(
  'siteDomain' => "localhost",
  'siteName' => "Vbox Localhost",
  'copyright' => "2015 Barton L. Phillips",
  'memberTable' => 'members',
  'count' => false,
);

$dbinfo = array(
  'database' => 'test.sdb',
  'engine' => 'sqlite3'
);

Error::setNoEmailErrs(true);
Error::setDevelopment(true);

$siteinfo['databaseClass'] = new Database($dbinfo);

// The rest is like the above example. 

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
<h1>Test 3</h1>
<p>$names</p>
<hr>
$footer
EOF;
