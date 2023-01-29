<?php
// example using dbTables

$_site = require_once(getenv("SITELOADNAME"));

$S = new $_site->className($_site);
$T = new dbTables($S);

// Pass some info to getPageTopBottom method
$h->title = "Example"; // Goes in the <title></title>
$h->banner = "<h1>Example</h1>"; // becomes the <header> section
// Add some local css to but a border and padding on the table 
$h->css = <<<EOF
main table * {
  padding: .5em;
  border: 1px solid black;
}
EOF;

[$top, $footer] = $S->getPageTopBottom($h);

// create a table from the memberTable
$sql = "select id, site, page, ip, lasttime from $S->masterdb.tracker where site='Examples' limit 5";
$tbl = $T->maketable($sql)[0];

echo <<<EOF
$top
<main>
<h3>Create a table from the tracker database table</h3>
<p>$sql</p>
<p>The tracker table follows:</p>
$tbl
</main>
<hr>
$footer
EOF;
