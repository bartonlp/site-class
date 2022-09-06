<?php
/*
  This file will run all the database functions. It will not see you.
*/
// This gets the siteload.php from the includes directory.

$_site = require_once(getenv("SITELOADNAME"));

$S = new $_site->className($_site);

// The $h object has information that is passed to the getPageTopBottom() function.  
$h->title = "Example3"; // The <title>
$h->banner = "<h1>Example3</h1>"; // This is the banner.

// Lets do some database stuff
/* Here is the schema for the counter table.
CREATE TABLE `counter` (
  `filename` varchar(255) NOT NULL,
  `site` varchar(50) NOT NULL DEFAULT '',
  `count` int DEFAULT NULL,
  `realcnt` int DEFAULT '0',
  `lasttime` datetime DEFAULT NULL,
  PRIMARY KEY (`filename`,`site`),
  KEY `site` (`site`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;
*/

// There is more information about the mysql functions at https://bartonlp.github.io/site-class/ or
// in the docs directory.

$sql = "select filename, site, count, realcnt, lasttime from $S->masterdb.counter where lasttime>=current_date() and site='Examples'";
$S->query($sql);
while([$file, $site, $count, $real, $lasttime] = $S->fetchrow('num')) {
  $rows .= "<tr><td>$file</td><td>$site</td><td>$count</td><td>$real</td><td>$lasttime</td></tr>";
}

// Now here is an easier way using dbTables.
// For more information on dbTables you can look at the source or the documentation in the docs
// directory on on line at https://bartonlp.github.io/site-class/

$T = new dbTables($S);
$tbl = $T->maketable($sql, ['attr'=>['id'=>'table1', 'border'=>'1']])[0];

[$top, $footer] = $S->getPageTopBottom($h, $b);

echo <<<EOF
$top
<p>Here are the entries from the 'counter' table for today.</p>
<table border='1'>
<thead>
<tr><th>filename</th><th>site</th><th>count</th><th>real</th><th>lasttime</th></tr>
</thead>
<tbody>
$rows
</tbody>
</table>

<p>Same table but with dbTables</p>
$tbl
<hr>
<a href="example1.php">Example1</a><br>
<a href="example2.php">Example2</a><br>
<a href="example3.php">Example3</a><br>
<a href="example4.php">Example4</a><br>
<a href="hi.php">Hi</a><br>
<a href="phpinfo.php">PHPINFO</a>
$footer
EOF;
