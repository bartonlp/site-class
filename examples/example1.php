<?php
$_site = require_once(getenv("SITELOADNAME"));
vardump("site", $_site);
$S = new $_site->className($_site);
//$b->nofooter = true;
//$b->noLastmod = true;
//$b->noCounter = true;
//$b->noCopyright = true;
//$b->copyright = "This is the copyright";
//$b->aboutwebsite = "<h2><a target='_blank' href='phpinfo.php'>Something Special</a></h2>";
//$b->noAddress = true;
//$b->address = "Here at Home";
//$b->noEmailAddress = true;
//$b->emailAddress = "Something@xx.com";
//$h->author = "Sam the Man";
$h->title = "Example1";
$h->banner = "<h1>Example1</h1>";
$h->css =<<<EOF
h1 { text-align: center; }
EOF;

[$top, $footer] = $S->getPageTopBottom($h, $b);

echo <<<EOF
$top
<a href="phpinfo.php">PHPINFO</a>
$footer
EOF;

