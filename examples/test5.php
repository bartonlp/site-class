<?php
// test5.php

require_once('../vendor/bartonlp/site-class/includes/siteautoload.class.php'); // path to siteautoload.class.php

Error::setNoEmailErrs(true);
Error::setDevelopment(true);

$S = new SiteClass($siteinfo);
$T = new dbTables($S);

// Pass some info to getPageTopBottom method
$h->title = "Table Test 5"; // Goes in the <title></title>
$h->banner = "<h1>Test 5</h1>"; // becomes the <header> section
// Add some local css to but a border and padding on the table 
$h->css = <<<EOF
  <style>
main table * {
  padding: .5em;
  border: 1px solid black;
}
  </style>
EOF;

list($top, $footer) = $S->getPageTopBottom($h);

// create a table from the memberTable
$sql = "select * from {$siteinfo['memberTable']}";
list($tbl) = $T->maketable($sql);
echo <<<EOF
$top
<main>
<h3>Create a table from the members database table</h3>
<p>The members table follows:</p>
$tbl
</main>
<hr>
$footer
EOF;
