<?php
// example1.php

require_once("../../../autoload.php");

$siteinfo = array(
  'siteDomain' => "localhost",
  'siteName' => "Example1",
  'copyright' => "2016 Barton L. Phillips",
);

$S = new SiteClass($siteinfo);

list($top, $footer) = $S->getPageTopBottom();
echo <<<EOF
$top
<h1>Example 1</h1>
<p>Hello World</p>
<hr>
$footer
EOF;
