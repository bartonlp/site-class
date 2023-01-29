<?php
/*
  This file will run all the database functions. It will not see you.
*/
// This gets the siteload.php from the includes directory.

$_site = require_once(getenv("SITELOADNAME"));

$S = new $_site->className($_site);

// The $h object has information that is passed to the getPageTopBottom() function.  
$h->title = "Example4"; // The <title>
$h->banner = "<h1>Example4</h1>"; // This is the banner.

// Lets do some database stuff
/* Here is the tarcker table's schema
CREATE TABLE `tracker` (
  `id` int NOT NULL AUTO_INCREMENT,
  `botAs` varchar(30) DEFAULT NULL,
  `site` varchar(25) DEFAULT NULL,
  `page` varchar(255) NOT NULL DEFAULT '',
  `finger` varchar(50) DEFAULT NULL,
  `nogeo` tinyint(1) DEFAULT NULL,
  `ip` varchar(40) DEFAULT NULL,
  `agent` text,
  `referer` varchar(255) DEFAULT '',
  `starttime` datetime DEFAULT NULL,
  `endtime` datetime DEFAULT NULL,
  `difftime` varchar(20) DEFAULT NULL,
  `isJavaScript` int DEFAULT '0',
  `lasttime` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `site` (`site`),
  KEY `ip` (`ip`),
  KEY `lasttime` (`lasttime`),
  KEY `starttime` (`starttime`)
) ENGINE=MyISAM AUTO_INCREMENT=6315673 DEFAULT CHARSET=utf8mb3;
*/
// There is more information about the mysql functions at https://bartonlp.github.io/site-class/ or
// in the docs directory.

$sql = "select id, site, page, ip, lasttime from $S->masterdb.tracker where lasttime>=now() - interval 5 minute and site='Examples' order by lasttime";
$S->query($sql);
while($row = $S->fetchrow('num')) {
  $rows .= "<tr>";
  foreach($row as $v) {
    $rows .= "<td>$v</td>";
  }
  $rows .= "</tr>";
}

// Now here is an easier way using dbTables.
// For more information on dbTables you can look at the source or the documentation in the docs
// directory on on line at https://bartonlp.github.io/site-class/

$T = new dbTables($S);
$tbl = $T->maketable($sql, ['attr'=>['id'=>'table1', 'border'=>'1']])[0];

[$top, $footer] = $S->getPageTopBottom($h, $b);

echo <<<EOF
$top
<p>Here are some entries from the 'tracker' table for the last 5 minutes for the 'Examples' site.</p>
<table border='1'>
<thead>
<tr><th>id</th><th>site</th><th>page</th><th>ip</th><th>lasttime</th></tr>
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
