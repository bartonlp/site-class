<?php
// composer-test1.php

require_once("../../../autoload.php");

$siteinfo = array(
  'siteDomain' => "localhost",
  'siteName' => "Vbox Localhost",
  'copyright' => "2015 Barton L. Phillips",
);

$S = new SiteClass($siteinfo);

list($top, $footer) = $S->getPageTopBottom();
echo <<<EOF
$top
<h1>Test 1</h1>
<p>Stuff</p>
<hr>
$footer
EOF;
