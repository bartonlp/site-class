<?php
$_site = require_once(getenv("SITELOADNAME"));
$S = new $_site->className($_site);

$h->banner = "<h1>I am HI</h1>";
[$top, $footer] = $S->getPageTopBottom($h);

echo <<<EOF
$top
<a href="example4.php">Exampe4</a><br>
$footer
EOF;

