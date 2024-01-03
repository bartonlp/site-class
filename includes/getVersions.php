<?php
$_site = require_once(getenv("SITELOADNAME"));
//$_site = require_once("/var/www/tysonweb/docs/site-class/includes/autoload.php");
$_site->noTrack = $_site->noGeo = true;

$tbl = (require(SITECLASS_DIR . "/whatisloaded.php"))[0];

$S = new SiteClass($_site);
$S->title = "Get Versions";
$S->banner = "<h1>Get Versions</h1>";
$S->css = "td { padding: 0 10px; }";

$class = $S->__toString();

[$top, $footer] = $S->getPageTopBottom();

foreach($ret as $k=>$v) {
  $msg .= "<tr><td>$k</td><td>$v</td></tr>";
}

echo <<<EOF
$top
<hr>
$class
$tbl
<hr>
$footer
EOF;
