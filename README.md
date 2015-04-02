# SiteClass
SiteClass PHP class mini framework for simple, small websites.

This project has several parts that can function standalone or combined.
* siteautoload.class.php : Autoload classes and reads a .sitemap.php file to initialize the system. 
* Database.class.php : provides a wrapper for several different database engines.
<ul>
1. Mysql  (depreciated)
2. Mysqli (most rigerously tested)
3. sqlite 
4. POD    (least tested)
</ul>
* dbTables.class.php : uses the functionality of Database.class.php to make creating tables easy.
* Error and Exception classes
* SiteClass.class.php : tools for making creating a site a little easier. The class provides methods to help with headers, banners, footers and more.

## Examples
Take a look at the .sitemap.php file. It has a fair amount of documentation.

There are a number of ways to use the framework:

First you can just use the SiteClass all by itself.

``` php
<?php
require-once('/var/www/includes/SiteClass.class.php'); // path to your SiteClass.class.php file.
require-once('/var/www/includes/database-engines/Database.class.php'); // path to Database.class.php

$siteinfo = array(
  'siteDomain' => "localhost",
  'siteName' => "Vbox Localhost",
  'copyright' => "2015 Barton L. Phillips",
);

$S = new SiteClass($siteinfo);

list($top, $footer) = $S->getPageTopBottom();
echo <<<EOF
$top
<h1>Banner</h1>
<p>Stuff</p>
$footer
EOF;
```

That is the simplelest usage. You get a generic <head> a blank <header> and a generic footer. 
No database or other stuff.

You can extend this by adding a database either by instantiating the Database class directly or 
indirectly. 

``` php
<?php
require-once('/var/www/includes/SiteClass.class.php'); // path to your SiteClass.class.php file.
require-once('/var/www/includes/database-engines/Database.class.php'); // path to Database.class.php

$siteinfo = array(
  'siteDomain' => "localhost",
  'siteName' => "Vbox Localhost",
  'copyright' => "2015 Barton L. Phillips",
  'memberTable' => 'mymembers',
  'dbinfo' => array(
    'host' => 'localhost',
    'user' => 'barton',
    'password' => '7098653',
    'database' => 'barton',
    'engine' => 'mysqli'
  ),
);

$S = new SiteClass($siteinfo);

list($top, $footer) = $S->getPageTopBottom();
// Do some database operations
$S->query("select concat(fname, ' ', lname) from {$siteinfo['memberTable']}";
$names = '';
while(list($name) = $S->fetchrow('num')) {
  $names .= "$name<br>";
}

echo <<<EOF
$top
<h1>Banner</h1>
<p>$nammes</p>
$footer
EOF;
```

The above example uses the 'query' and 'fetchrow' methods to do some database operations.
The database could also be instantiated explicitly as follows:

``` php
<?php
require-once('/var/www/includes/SiteClass.class.php'); // path to your SiteClass.class.php file.
require-once('/var/www/includes/database-engines/Database.class.php'); // path to Database.class.php

$siteinfo = array(
  'siteDomain' => "localhost",
  'siteName' => "Vbox Localhost",
  'copyright' => "2015 Barton L. Phillips",
  'memberTable' => 'mymembers',
);

$dbinfo = array(
  'host' => 'localhost',
  'user' => 'barton',
  'password' => '7098653',
  'database' => 'barton',
  'engine' => 'mysqli'
);

$siteifno['databaseClass'] = new Database($dbinfo);

// The rest is like the above example. 
```

You can also use the siteautoload.class.php and .sitemap.php to further automate working with the 
framework.

``` php
<?php
require_once('/var/www/includes/siteautoload.class.php'); // path to siteautoload.class.php
// the siteautoload.class.php first looks for the .sitemap.php file and then sets up class autoloader.
// Now the class autoloader finds the classes that are required. The .sitemap.php has all the 
// information needed to instanntiate the Database class. The $siteinfo array is available for the
// SiteClass etc.

$S = new SiteClass($siteinfo);

list($top, $footer) = $S->getPageTopBottom();
// Do some database operations
$S->query("select concat(fname, ' ', lname) from {$siteinfo['memberTable']}";
$names = '';
while(list($name) = $S->fetchrow('num')) {
  $names .= "$name<br>";
}

echo <<<EOF
$top
<h1>Banner</h1>
<p>$nammes</p>
$footer
EOF;
```

In addition to the SiteClass and Database classes there are several others:
* Error
* SqlException
* dbMysql
* dbMysqli
* dbSqlite
* dbPod
* dbTables
* and helper functions.

The dbTables class uses the Database class to make creating tables simple. For example:

``` php
<?php
require_once('/var/www/includes/siteautoload.class.php'); // path to siteautoload.class.php

$S = new SiteClass($siteinfo);
$T = new dbTables($S);

// Pass some info to getPageTopBottom method
$h->title = "Table Test"; // Goes in the <title></title>
$h->banner = "<h1>Create a table from the members database table</h1>; // becomes the <header> section
// Add some local css to but a border and padding on the table 
$h->css = <<<EOF
  <style>
main table * {
  padding: .5em;
  border: 1px solid black;
}
  </style>
EOF;

list($top, $footer) = $S->getPageTopBottom($h);

// create a table from the memberTable
$sql = "select * from {$siteinfo['memberTable']}";
list($tbl) = $T->maketable($sql);
echo <<<EOF
$top
<main>
<p>The members table follows:</p>
$tbl
</main>
$footer
EOF;
```

The maketable method takes several optional option to help setup the table. Using the options you can
give your table an id or class or set any other attributes.

# Class Methods

While there are a number of methods for each of the major classes there are really only a small 
handfull you will use on a regular bases. The one must used have some documentation with them.

## SiteClass methods:

* constructor
* public function setSiteCookie($cookie, $value, $expire, $path="/")
* public function setIdCookie($id, $cookie=null)
* public function checkId($mid=null, $cookie=null) // if a memberTable
* public function getId() // if a memberTable
* public function setId($id) // if a memberTable
* public function getIp()
* public function getEmail() // if a memberTable
* public function setEmail($email) // if a memberTable
* public function getWhosBeenHereToday() // if a memberTable
* public function getPageTopBottom($h, $b=null)  
This is the most used method. It takes one or two arguments which can be string|array|object.  
$h can have 'title', 'desc', 'banner' and a couple of other less used options.  
$b is for the footer or bottom. I usually just pass a &lt;hr&gt; but you can also pass a 'msg', 'msg1',
'msg2' etc. I usually put things into the 'footerFile' but on ocasions a page needs something extra.  
This method ends up calling getPageHead, getBanner(), getFooter().
* public function getPageTop($header, $banner=null, $bodytag=null)
* public function getDoctype()
* public function getPageHead(/*$title, $desc=null, $extra=null, $doctype, $lang*/)
* public function getBanner($mainTitle, $nonav=false, $bodytag=null)
* public function getFooter(/* mixed */)
* public function __toString()
* A number of 'protected' methods that can be used in a child class.

## Database methods:

* constructor
* public function getDb()
* public function query($query)  
This is the workhourse of the database. It is used for 'select', 'update', 'insert' and basically
anything you need to do like 'drop', 'alter' etc. $query is just sql.
* public function fetchrow($result=null, $type="both")  
Probably the second most used method. If it follows the 'query' the $result is not needed. The only
time $result is needed is is there are other queries in a while loop. In that case you need to get
the result of the query by calling the getResult() method before running the while loop.  
The $type can be 'assoc', 'num' or default 'both'. 'assoc' returns only an associative array, while 'num'
return a numberic array. I usually use a numeric array with
``` php
while(list($name, $email) = $S->fetchrow('num')) { ... }
```
* public function queryfetch($query, $retarray=false)
* public function getLastInsertId()  
After an 'insert' this method returns the new row primary key id.
* public function getResult()  
Returns the result object from the last 'query'. Usually not needed.
* public function escape($string)
* public function escapeDeep($value)
* public function getNumRows($result=null)
* public function prepare($query)  
Hardly ever use prepare(), bindParam(), bindResults() or execute() so they are not as well tested as 
the other methods.
* public function bindParam($format)
* public function bindResults($format)
* public function execute()
* public function getErrorInfo()

The database methods are implemented for all supported engines.

## dbTables methods:

* public function makeresultrows($query, $rowdesc, array $extra=array())
* public function maketable($query, array $extra=null)  
$extra is an optional assoc array: $extra['callback'], $extra['callback2'], $extra['footer'] 
and $extra['attr'].  
$extra['attr'] is an assoc array that can have attributes for the <table> tag, like 'id',
 'title', 'class', 'style' etc.  
$extra['callback'] function that can modify the header after it is filled in.  
$extra['footer'] a footer string   
@return array [{string table}, {result}, {num}, {hdr}, table=>{string}, result=>{result},
 num=>{num rows}, header=>{hdr}]  
or === false

## Contact Me

Barton Phillips : bartonphillips@gmail.com
Copyright &copy; 2015 Barton Phillips
