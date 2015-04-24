<?php
// composer-test4.php

require_once("../../../autoload.php");
require_once(".sitemap.php");

Error::setNoEmailErrs(true);
Error::setDevelopment(true);

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
<h1>Test 4</h1>
<p>$names</p>
<hr>
$footer
EOF;
