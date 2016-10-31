<?php
// example5.php

$_site = require_once(getenv("SITELOAD")."/siteload.php");

ErrorClass::setNoEmailErrs(true);
ErrorClass::setDevelopment(true);

$S = new $_site->className($_site);
$T = new dbTables($S);

// Pass some info to getPageTopBottom method
$h->title = "Example 5"; // Goes in the <title></title>
$h->banner = "<h1>Example 5</h1>"; // becomes the <header> section
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

$sql = "select * from $S->memberTable";

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
