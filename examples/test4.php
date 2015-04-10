<?php
// test4.php

// Check if we are in development mode. If there is an 'includes' directory just above
// 'examples' then development.
  
if(file_exists("../includes")) {
  require_once("../includes/siteautoload.class.php");
} else {
  require_once("../vendor/bartonlp/site-class/includes/siteautoload.class.php");
}
// the siteautoload.class.php first looks for the .sitemap.php file and then sets up class autoloader.
// Now the class autoloader finds the classes that are required. The .sitemap.php has all the 
// information needed to instanntiate the Database class. The $siteinfo array is available for the
// SiteClass etc.

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
