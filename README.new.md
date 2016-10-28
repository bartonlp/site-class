# SiteClass Verion 2.0

**SiteClass** is a PHP mini framework for simple, small websites. It can be esaly combined with other frameworks or templeting engines if needed. For small websites I feel that frameworks like Laravel or Meteor etc. are just too much.

This project has several parts that can function standalone or combined.

* Database.class.php : provides a wrapper for several different database engines.
* dbTables.class.php : uses the functionality of Database.class.php to make creating tables easy.
* ErrorClass.class.php : Error and Exception classes
* SiteClass.class.php : tools for making creating a site a little easier. The class provides methods to help with headers, banners, footers and more.

The following database engines are provided as the following classes:

1. dbMysqli.class.php : (rigorously tested) This is the latest PHP version of the MySql database engine.
2. dbSqlite.class.php : sqlite3 (used for the examples)

There are a couple of additional databases but they have not be rigouously tested.

## Disclamer

To start, this framework is meant for Linux not Windows. I don't use Windows, like it or have it, 
so nothing has been tried on Windows. 

I use Linux Mint which is an Ubuntu derivative which is a Debian derivative. 
I have not tried this package on any distributions that do not evolve from Debian.

## Install

There are several ways to install this project. 

### Download The ZIP File

Download the ZIP file from GitHub. Expand it and move the 'includes' directory somewhere. On a system with Apache2,
I usually put the 'includes' directory in the /var/www directory that Apache creates. 
Apache also usually creates /var/www/html and makes this the default DocumentRoot. 
I put the 'includes' directory just outside of the DocumentRoot. 
In my servers I have /var/www and then have my virtual hosts off that directory. 
That way the 'includes' directory is easily available to all of my virtual hosts.

If you are testing with the PHP server I put a 'www' directory off my $HOME and put the 'includes' directory there. 
I then make my test DocumentRoot off '&#126;/www' like '&#126;/www/test'. I `cd` to the test directory and 
do `php -S localhost:8080`. I can then use my browser and goto `localhost:8080` and see my 'index.php' file.

### Use Composer

If you have Apache or Nginx installed then you should made your project root somewhere within your 
DocumentRoot ('/var/www/html' for Apache2 on Ubuntu). Or if you want to make a seperate Apache virtual host with a 
registered domain name you can make your new project in '/var/www'.

Create a directory `mkdir myproject; cd myproject`, this is your project root directory. 
Add the following to 'composer.json', just cut and past:

```json
{
  "require": {
      "bartonlp/site-class": "dev-master"
  }
}
```

Then run 

```bash
composer install
```

**OR** you can just run 

```bash
composer require bartonlp/site-class:dev-master
``` 

which will create the 'composer.json' for you and load the package like 'composer install' above.

In your PHP file add `require_once($PATH_TO_VENDOR . '/vendor/autoload.php');` 
where '$PATH' is the path to the 'vendor' directory like './' or '../' etc.

There are some example files in the 'examples' directory at '$PATH_TO_VENDOR/vendor/bartonlp/site-class/examples'.

## Examples

The code shown below can be found in the 'examples' directory at http://github.com/bartonlp/site-class or 
from your project root at 'vendor/bartonlp/site-class/examples'. There is an 'EXAMPLES.md' and 'EXAMPLES.html' 
in the 'examples' directory.

<p style="color: green">The code in the 'examples' directory has actually been tested and runs. 
The code in this README was originally copied in from the examples code but may have changed for some reason. 
Therefore you should use the examples code rather than doing a copy and past from this README.</p>

If you have Apache or Nginx installed then you should have made your project root somewhere within your DocumentRoot, 
for example '/var/www/html/myproject'.

If you don't have Apache or Nginx installed on your computer you can use the PHP server. 
Do the following from your project root:

```bash
php -S localhost:8080
```

Then use your browser by entering `http://localhost:8080/vendor/bartonlp/site-class/README.html` 
in the browsers location bar.

The code in the 'examples' directory uses the **sqlite3** database engine. 
There should be a 'test.sdb' database file in the 'examples' directory already.

I have included a 'sqlite.sql' file that can be run from the command line if you want to recreate the 'members' table.

You will need to get sqlite3 and get the PHP sqlite packages along with mysql etc. 
From the command line in the directory where the SiteClass was downloaded:

```bash
$ cd examples
$ sqlite3 test.sdb
sqlite> drop table members;
sqlite> .read sqlite.sql
sqlite> .table
members
sqlite> select rowid,* from members;
1|Big|Joe
2|Little|Joe
3|Barton|Phillips
4|Someone|Else
sqlite> .quit
$
```

This should create a new 'members' table in the 'test.sdb' database.

<hr>

There are a number of ways to use the framework:

**First** you can just use the SiteClass all by itself.

```php
<?php
// test1.php

require-once($PATH_TO_VENDOR . '/vendor/autoload.php');
$S = new SiteClass();

list($top, $footer) = $S->getPageTopBottom();
echo <<<EOF
$top
<h1>Test 1</h1>
<p>Hello World</p>
$footer
EOF;
```

That is the simplest usage. You get a generic head and a genericfooter. No database or other stuff.

<hr>

You can extend this by adding a database either by instantiating the 'Database' class directly or indirectly. 

```php
<?php
// test2.php

require-once($PATH_TO_VENDOR . '/vendor/autoload.php');
$_site = array(
  'copyright' => "2016 Barton L. Phillips",
  'memberTable' => 'members',
  // Add dbinfo to the $_site and SiteClass will instantiate the Database for you
  'dbinfo' => array(
    'database' => 'test.sdb',
    'engine' => 'sqlite3',
  ),
);

$S = new SiteClass($_site);

list($top, $footer) = $S->getPageTopBottom();

// Do some database operations

$S->query("select fname||' '||lname) from {$_site['memberTable']}";
$names = '';

while(list($name) = $S->fetchrow('num')) { // fetch a row with numeric indices.
  $names .= "$name<br>";
}

echo <<<EOF
$top
<h1>Test 2</h1>
<p>$names</p>
$footer
EOF;
```

The above example uses the 'query' and 'fetchrow' methods to do some database operations.

<hr>

The database could also be instantiated explicitly as follows:

```php
<?php
// test3.php

require-once($PATH_TO_VENDOR . '/vendor/autoload.php');
$_site = array(
  'copyright' => "2015 Barton L. Phillips",
  'memberTable' => 'members',
);

$dbinfo = array(
  'database' => 'test.sdb',
  'engine' => 'mysqli'
);

$_site['databaseClass'] = new Database($dbinfo);

// by adding to the $siteinfo arrays 'databaseClass' element we let SiteClass
// know that the database is active.

$S = new SiteClass($_site);

list($top, $footer) = $S->getPageTopBottom();

// Do some database operations

$S->query("select fname||' '||lname from {$siteinfo['memberTable']}");

$names = '';

while(list($name) = $S->fetchrow('num')) {
  $names .= "$name<br>";
}

echo <<<EOF
$top
<h1>Test 3</h1>
<p>$names</p>
<hr>
$footer
EOF;
```

<hr>

You can also use the 'siteload.php' file to load the json file 'mysitemap.json' to further automate working 
with the framework. This file is in the 'includes' directory. There is a 'mysitemap.json.php' file that is well commented. 
You can uncomment sections of this file or add items as needed.

You can run this file as a CLI program and it will output to 'stdout'. Create your 'mysitemap.json' file as follows:

```bash
./mysitemap.json.php >mysitemap.json
```

Copy the created file to your project directory. 
There is already a 'mysitemap.json' file in the 'examples' directory.

I set the Apache2 environment variable 'SITELOAD' to point to my 'siteload.php' file in 
'vendor/bartonlp/site-class/includes'. You can add it to your '/etc/apache2/apache2.conf', to an apache2 virtual host
or to your '.htaccess' file. I will assume you have done this in the following examples.

```bash
SetEnv SITELOAD /var/www/vendor/bartonlp/site-class/includes/siteload.php
```

This example uses 'SITELOAD', 'query' and 'fetchrow':

```php
<?php
// test4.php

$_site = require_once(getenv("SITELOAD") . '/siteload.php');
$S = new $_site->className($_site);

list($top, $footer) = $S->getPageTopBottom();

// Do some database operations

$S->query("select concat(fname, ' ', lname) from {$siteinfo['memberTable']}";

$names = '';

while(list($name) = $S->fetchrow('num')) {
  $names .= "$name<br>";
}

echo <<<EOF
$top
<h1>Test 4</h1>
<p>$names</p>
$footer
EOF;
```

<hr>

In addition to the SiteClass and Database classes there are several others classes in the 'database-engins' directory:

* ErrorClass
* SqlException
* dbTables
* and a file with helper functions ('helper-functions.php').

<hr>

The dbTables class uses the Database class to make creating tables simple. For example:

```php
<?php
$_site = require_once(getenv("SITELOAD") .'/siteload.php');

$S = new $_site->className($_site);
$T = new dbTables($S);

// Pass some info to getPageTopBottom method

$h->title = "Table Test 5"; // Goes in the <title></title>
$h->banner = "<h1>Test 5</h1>"; // becomes the <header> section

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

// create a table from the database table 'memberTable'

$sql = "select * from $S->memberTable";
list($tbl) = $T->maketable($sql);
echo <<<EOF
$top
<main>
<h3>Create a table from the members database table</h3>
<p>The members table follows:</p>
$tbl
</main>
<hr>
$footer
EOF;
```

The 'maketable' method takes several optional arguments to help setup the table. 
Using the options you can give your table an id or class or set any other attributes. 
You can also pass a 'callback' function which can modify the rows as they are selected 
(see the 'insert-update.php' file in the 'examples' directory for more information).

<hr>

## The 'mysitemap.json' File

The 'mysitemap.json' file is the site configuration file. 'siteload.php' loads the 'mysitemap.json' file 
that is in the current directory. If a 'mysitemap.json' file is not found an exception is thrown.

Once a 'mysitemap.json' file is found the information in it is read in via 'require_once'. 
The information from the 'mysitemap.json' file is returned as a PHP object.

You can generate a 'mysitemap.json' file by running 'mysitemap.json.php' and redirecting the output to 'mysitemap.json'.

My usual directory structure starts under a 'www' subdirectory. On an Apache2 host the structure looks like this:

```plain
/var/www/vendor          // this is the 'composer' directory where the 'bartonlp/site-class' resides
/var/www/html            // this is where your php files and js, css etc. 
                         // directories live
/var/www/html/includes   // this is where 'headFile', 'bannerFile', 
                         // 'footerFile' and child classes live
```

If I have multiple virtual hosts they are all off the '/var/www' directory instead of a single 'html' directory.

### How the xxxFile files look

In the 'mysitemap.json' file there can be three elements that describe the location of special files. 
These files are 1) 'headFile', 2) 'bannerFile' and 3) 'footerFile'.

I put the three special file in my '/var/www/html/includes' directory (where 'html' may be one of your virtual hosts 
and not named 'html'). 

Here is an example of my 'headFile':

```php
<?php
// head.i.php for bartonphillips.com

return <<<EOF
<head>
  <title>{$arg['title']}</title>
  <!-- METAs -->
  <meta name=viewport content="width=device-width, initial-scale=1">
  <meta charset='utf-8'>
  <meta name="copyright" content="$this->copyright">
  <meta name="Author" content="Barton L. Phillips, mailto:bartonphillips@gmail.com"/>
  <meta name="description" content="{$arg['desc']}">
  <link rel="stylesheet" href="css/your_css_file.css">
{$arg['link']}
  <!-- jQuery -->
  <script src="http://ajax.googleapis.com/ajax/libs/jquery/1/jquery.min.js"></script>
{$arg['extra']}
{$arg['script']}
{$arg['css']}
</head>
EOF;
```

These 'xxxFile' files return their contents.
The $arg array is created form the argument passed to the 'getPageTopBottom' method. 
The 'getPageTopBottom' method also has access to the SiteClass '$this' property.

You will see if you delve into the SiteClass code that many things can be passed to the getPageTopBottom method, 
and the various sub-methods, but the standard things are:

* title
* desc
* link
* extra
* script
* css

As you saw in example 5 above (test5.php in the 'examples' directory) I passed a '$h' object to 'SiteClass'. 
For example it might look like this:

```php
$h->title = 'my title';
$h->desc = 'This is the description';
$h->link = '<link rel="stylesheet" href="test.css">';
$h->extra = '<!-- this can be anything from a meta, link, script etc -->';
$h->script = '<script> var a="test"; </script>';
$h->css = '<style> /* some css */ #test { width: 10px; } </style>';
$S = new SiteClass($h);
```

As you can see in the 'headFile' example the '$this' can also be used as in '$this->copyright'. 
Any of the public, protected or private '$this' properties can be used in any of the special files as they 
are all included within 'SiteClass.class.php'.

As these special files are PHP files you can do anything else that you need to, 
including database queries. Just remember that you need to use '$this'. 
For example, to do a query do `$this->query($sql);` not `$S->query($sql);`. 
You can't use the variable from your project file that you created via the `$S = new SiteClass($h);` 
because it is NOT within scope.

I usually call these files 'head.i.php', 'banner.i.php' and 'footer.i.php' but you can name them anything you like. 
In the 'mysitemap.json' just add the full path to the file. For example:

```json
{
    "siteDomain": "example.com",
    "siteName": "Example",
    "mainTitle": "Test Site",
    "className": "SiteClass",
    "copyright": "2016 Barton L. Phillips",
    "author": "Barton L. Phillips, mailto:bartonphillips@gmail.com",
    "masterdb": "your_master_database",
    "dbinfo": {
        "host": "localhost",
        "user": "username",
        "password": "password",
        "database": "yourPrimaryDatabase",
        "engine": "mysqli"
    },
    "headFile": "/var/www/html/includes/head.i.php",
    "bannerFile": "/var/www/html/includes/banner.i.php",
    "footerFile": "/var/www/html/includes/footer.i.php",
    "count": true,
    "countMe": true,
    "myUri": "yourUri",
    "EMAILADDRESS": "email@example.com",
    "EMAILRETURN": "email@example.com",
    "EMAILFROM": "webmaster@example.com"
}
```

There is a default for the head, banner and footer section if you do not have special files. 
The DOCTYPE is by default <!DOCTYPE html> but that can be altered via an argument to the 'getPageTopBottom' 
method (`$h->doctype='xxx';`).

Creating the special files make the tedious boiler plate simple and yet configureable via the $arg array.

<hr>

# Doing Page Counting and Analysis

If you want to do page counting and analysis there are several MySql tables that you can use. The MySql schema for these 
tables is in the *mysql.schema* file in the repository.

The tables are:

* bots : the SiteClass has logic to try to determin which user agents might be robots. 
* bots2 : similar to bots but has a 'site' and 'date' field.
* logagent : logs the IpAddress, and User Agent.
* logagent2 : a short term version of lagagent.
* daycounts : counts the number of hits per day
* counter : counts the number of hits per site per file.
* counter2 : counts the number of hits per site per file per day.
* tracker : trackes accesses by site, page etc.

Here are the schemas of the tables:

```sql
CREATE TABLE `bots` (
  `ip` varchar(40) NOT NULL DEFAULT '',
  `agent` varchar(255) NOT NULL DEFAULT '',
  `count` int(11) DEFAULT NULL,
  `robots` int(5) DEFAULT '0',
  `who` varchar(255) DEFAULT NULL,
  `creation_time` datetime DEFAULT NULL,
  `lasttime` datetime DEFAULT NULL,
  PRIMARY KEY (`ip`,`agent`),
  KEY `ip` (`ip`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE `bots2` (
  `ip` varchar(40) NOT NULL DEFAULT '',
  `agent` varchar(255) NOT NULL DEFAULT '',
  `date` date NOT NULL DEFAULT '0000-00-00',
  `site` varchar(50) NOT NULL DEFAULT '',
  `which` int(5) NOT NULL DEFAULT '0',
  `count` int(11) DEFAULT NULL,
  `lasttime` datetime DEFAULT NULL,
  PRIMARY KEY (`ip`,`agent`,`date`,`site`,`which`),
  KEY `ip` (`ip`),
  KEY `agent` (`agent`),
  KEY `site` (`site`),
  KEY `ip_2` (`ip`),
  KEY `date` (`date`),
  KEY `site_2` (`site`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `logagent` (
  `site` varchar(25) NOT NULL DEFAULT '',
  `ip` varchar(40) NOT NULL DEFAULT '',
  `agent` varchar(255) NOT NULL,
  `count` int(11) DEFAULT NULL,
  `id` int(11) DEFAULT NULL,
  `created` datetime DEFAULT '0000-00-00 00:00:00',
  `lasttime` datetime DEFAULT NULL,
  PRIMARY KEY (`site`,`ip`,`agent`),
  KEY `ip` (`ip`),
  KEY `site` (`site`),
  KEY `agent` (`agent`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE `logagent2` (
  `site` varchar(25) NOT NULL DEFAULT '',
  `ip` varchar(40) NOT NULL DEFAULT '',
  `agent` varchar(255) NOT NULL,
  `count` int(11) DEFAULT NULL,
  `id` int(11) DEFAULT NULL,
  `created` datetime DEFAULT '0000-00-00 00:00:00',
  `lasttime` datetime DEFAULT NULL,
  PRIMARY KEY (`site`,`ip`,`agent`),
  KEY `agent` (`agent`),
  KEY `site` (`site`),
  KEY `ip` (`ip`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE `daycounts` (
  `site` varchar(50) NOT NULL DEFAULT '',
  `date` date NOT NULL,
  `real` int(11) DEFAULT '0',
  `bots` int(11) DEFAULT '0',
  `members` int(11) DEFAULT '0',
  `visits` int(11) DEFAULT NULL,
  `lasttime` datetime DEFAULT NULL,
  PRIMARY KEY (`site`,`date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE `counter` (
  `filename` varchar(255) NOT NULL,
  `site` varchar(50) NOT NULL DEFAULT '',
  `ip` varchar(20) DEFAULT NULL,
  `agent` varchar(255) DEFAULT NULL,
  `count` int(11) DEFAULT NULL,
  `realcnt` int(11) DEFAULT '0',
  `lasttime` datetime DEFAULT NULL,
  PRIMARY KEY (`filename`,`site`),
  KEY `site` (`site`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE `counter2` (
  `site` varchar(50) NOT NULL DEFAULT '',
  `date` date NOT NULL DEFAULT '0000-00-00',
  `filename` varchar(255) NOT NULL DEFAULT '',
  `count` int(11) DEFAULT '0',
  `members` int(11) DEFAULT '0',
  `bots` int(11) DEFAULT '0',
  `lasttime` datetime DEFAULT NULL,
  PRIMARY KEY (`site`,`date`,`filename`),
  KEY `site` (`site`),
  KEY `date` (`date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE `tracker` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `site` varchar(25) DEFAULT NULL,
  `page` varchar(255) NOT NULL DEFAULT '',
  `ip` varchar(40) DEFAULT NULL,
  `agent` varchar(255) DEFAULT NULL,
  `starttime` datetime DEFAULT NULL,
  `endtime` datetime DEFAULT NULL,
  `difftime` time DEFAULT NULL,
  `refid` int(11) DEFAULT '0',
  `isJavaScript` int(5) DEFAULT '0',
  `lasttime` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `site` (`site`),
  KEY `ip` (`ip`),
  KEY `agent` (`agent`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
```

If you look at *SiteClass* you will see several methods in the constructor:

*$this->trackbots();
*$this->tracker();
*$this->doanalysis();
*$this->logagent();
*$this->counter();

If you look at these methods you will see that they are protected by a check of the database to see if the tables
exists in the database. If the table does not exist an 'error_log' message is output.
You can prevent the error message by setting "noErrorLog": "true" in the 'mysitemap.json' file.

<hr>

# Tests

In the 'tests' directory there are a series of mostly database engine tests. If you want to test MySql 
you will need to first install the packages (both OS and PHP5) and then configure each with a user and password etc. 
Setting up the databases is beyond the scope of this README. 
The tests are set up for an account with user 'siteclass' and password 'siteclass' with database 'siteclass' 
(no single quotes of course).

<hr>

# Class Methods

While there are a number of methods for each of the major classes there are really only a small handful you will use on a regular bases. The ones most used have some documentation with them.

## SiteClass methods:

* constructor
* public function setSiteCookie($cookie, $value, $expire, $path="/")
* public function getId() // if a memberTable
* public function setId($id) // if a memberTable
* public function getIp()
* public function getPageTopBottom($h, $b=null)  
This is the most used method. It takes one or two arguments which can be string|array|object.  
$h can have 'title', 'desc', 'banner' and a couple of other less used options.  
$b is for the footer or bottom. I sometimes pass a &lt;hr&gt; but you can also pass a 'msg', 'msg1', 'msg2' (see the code). I usually put things into the 'footerFile' but on occasions a page needs something extra.  
This method calls getPageHead(), getBanner(), getFooter().
* public function getPageTop($header, $banner=null, $bodytag=null)
* public function getDoctype()
* public function getPageHead(/*$title, $desc=null, $extra=null, $doctype, $lang*/)
* public function getPageBanner($mainTitle, $nonav=false, $bodytag=null)
* public function getPageFooter(/* mixed */)
* public function \__toString()
* A number of 'protected' methods and properties that can be used in a child class.

## Database methods:

* constructor
* public function getDb()
* public function query($query)  
This is the workhourse of the database. It is used for 'select', 'update', 'insert' and basically anything you need to do like 'drop', 'alter' etc. $query is the sql statement.
* public function fetchrow($result=null, $type="both")  
Probably the second most used method. If it follows the 'query' the $result is not needed. The only time $result is needed is if there are other queries in a while loop. In that case you need to get the result of the query by calling the getResult() method before running the while loop.  
The $type can be 'assoc', 'num' or default 'both'. 'assoc' returns only an associative array, while 'num' return a numeric array. I usually use a numeric array with

```php
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
I hardly ever use prepare(), bindParam(), bindResults() or execute() so they are not as well tested as the other methods.
* public function bindParam($format)
* public function bindResults($format)
* public function execute()
* public function getErrorInfo()

The database methods are implemented for all supported engines. There are some minor   behavioral differences, for example in the syntax the engine queries uses or the return values. For example sqlite3 does not support a number of rows returned functionality and there are also several (many) syntactial differenced between sqlite and mysql when it comes to supported functions etc. (caviat emptor).

## dbTables methods:

* constructor
* public function makeresultrows($query, $rowdesc, array $extra=array())
* public function maketable($query, array $extra=null)  
$extra is an optional assoc array: $extra['callback'], $extra['callback2'], $extra['footer'] and $extra['attr'].  
$extra['attr'] is an assoc array that can have attributes for the <table> tag, like 'id', 'title', 'class', 'style' etc.  
$extra['callback'] function that can modify the header after it is filled in.  
$extra['footer'] a footer string   
@return array [{string table}, {result}, {num}, {hdr}, table=>{string}, result=>{result},
 num=>{num rows}, header=>{hdr}]  
 or === false

## Contact Me

Barton Phillips : <a href="mailto://bartonphillips@gmail.com">bartonphillips@gmail.com</a>
Copyright &copy; 2015 Barton Phillips
