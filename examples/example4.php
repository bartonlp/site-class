<?php
// Example4.php

require_once("../../../autoload.php");
$_site = json_decode(file_get_contents("mysitemap.json"));

ErrorClass::setNoEmailErrs(true);
ErrorClass::setDevelopment(true);

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
<h1>Example 4</h1>
<p>$names</p>
<hr>
$footer
EOF;
