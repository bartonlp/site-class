<?php  
// example2.php

require_once("../../../autoload.php");

$_site = array(
  'siteDomain' => "localhost",
  'siteName' => "Example2",
  'copyright' => "2016 Barton L. Phillips",
  'memberTable' => "members",
  'noTrack' => true, // do tracking logic in SiteClass
  'dbinfo' => array(
    'database' => 'test.sdb',
    'engine' => 'sqlite3'
  ),
  'count' => false
);

ErrorClass::setNoEmailErrs(true);
ErrorClass::setDevelopment(true);

$_site = arraytoobjectdeep($_site);

$S = new SiteClass($_site);

list($top, $footer) = $S->getPageTopBottom();

// Do some database operations
$S->query("select fname, lname from $S->memberTable");

$names = '';

while(list($fname, $lname) = $S->fetchrow('num')) {
  $names .= "$fname $lname<br>";
}

echo <<<EOF
$top
<h1>Example 2</h1>
<p>$names</p>
<hr>
$footer
EOF;
