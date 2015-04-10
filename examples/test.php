<?php
// test.php
// BLP 2015-04-10 -- used to test dbSqlite, dbPod with pdo_sqlite and pdo_pgsql
// and dbPostgreSql.

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

$engine = $siteinfo['dbinfo']['engine'];

switch($engine) {
  case 'sqlite3':
    $disclamer =<<<EOF
<p>Sqlite3 does not implement a method to get the number of rows returned by SELECT.</p>
EOF;
    break;
  case 'pdo_sqlite':
    $disclamer =<<<EOF
<p>The pdo_sqlite uses PHP POD to access sqlite databases and suffers from the same lack
of a number of SELECT rows as the sqlite3 engine.</p>
EOF;
    break;
  case 'pdo_pgsql':
    $disclamer =<<<EOF
<p>The pdo_pgsql uses PHP POD to access PostgreSql databases. It lacks a good way to get the
last insert ID. The workaround is to do a 'select lastval()' but even that is not a good
solution.</p>
EOF;
    break;
  case 'pgsql':
    $disclamer =<<<EOF
<p>The pgsql uses the PHP PostgreSql library. It lacks a good way to get the
last insert ID. The workaround is to do a 'select lastval()' but even that is not a good
solution.</p>
EOF;
    break;
}

list($top, $footer) = $S->getPageTopBottom();
$date = date("Y-M-D H:m:s");
$n = $S->query("insert into members (fname, lname) values('$date', 'TESTTWO')");

$lastid = $S->getLastInsertId(); // may not work for postgresql
if(!$lastid) {
  echo "using lastval()<br>";
  $S->query("select lastval()"); //"select max(rowid) from members");
  list($lastid) = $S->fetchrow('num');
}

$fname .= "$date->$lastid";
$nn = $S->query("update members set fname='$fname' where rowid=$lastid");

// Do some database operations.
// NOTE: sqlite does not return the number of rows as it just doesn't know.

if($engine == 'mysql' || $engine == 'mysqli') {
  $nnn = $S->query("select concat(fname,' ',lname) from {$siteinfo['memberTable']}");
} else {
  $nnn = $S->query("select fname||' '||lname from {$siteinfo['memberTable']}");
}

$names = '';

while(list($name) = $S->fetchrow('num')) {
  $names .= "$name<br>";
}

echo <<<EOF
$top
<h1>Test For Different Engines</h1>
<h2>Using $engine</h2>
$disclamer
<p>Last Insert Id: $lastid</p>
<ul>
<li> Insert affected rows: $n</li>
<li> Update affected rows: $nn</li>
<li> Select num_rows: $nnn</li>
</ul>
<p>$names</p>
<hr>
$footer
EOF;
