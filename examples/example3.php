<?php
// example3.php

require_once("../../../autoload.php");

$_site = array(
  'siteDomain' => "localhost",
  'siteName' => "Example3",
  'copyright' => "2016 Barton L. Phillips",
  'memberTable' => "members",
  'noTrack' => true, // do tracking logic in SiteClass
  'count' => false
);

$dbinfo = array(
  'database' => 'test.sdb',
  'engine' => 'sqlite3'
);

ErrorClass::setNoEmailErrs(true);
ErrorClass::setDevelopment(true);

$S = new SiteClass($_site);
$db = new Database((object)$dbinfo);
$S->setDb($db);

// The rest is like the above example. 

list($top, $footer) = $S->getPageTopBottom();

// Do some database operations
$S->query("select fname, lname from $S->memberTable");

$names = '';

while(list($fname, $lname) = $S->fetchrow('num')) {
  $names .= "$fname $lname<br>";
}

echo <<<EOF
$top
<h1>Example 3</h1>
<p>$names</p>
<hr>
$footer
EOF;
