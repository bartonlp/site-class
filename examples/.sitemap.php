<?php
// .sitemap.php for test4.php and test5.php

//**** SECTION ONE ****

// Check if we are in development mode. If there is an 'includes' directory just above
// 'examples' then development.

if(file_exists("../includes")) {
  define('TOP', '../');
} else {
  define('TOP', '../vendor/bartonlp/site-class');
}
define('INCLUDES', TOP."/includes");
define('DATABASE_ENGINES', INCLUDES."/database-engines");
if(defined(SITE_ROOT)) {
  define('SITE_INCLUDES', SITE_ROOT."/includes"); // SITE_ROOT is defined in siteautoload.php!
} else {
  define('SITE_INCLUDES', __DIR__."/includes");
}

// **** SECTION TWO ****

// The following defines three email addresses are used in SiteClass.class.php to send error info

define('EMAILADDRESS', "bartonphillips@gmail.com");
define('EMAILRETURN', "bartonphillips@gmail.com");
define('EMAILFROM', "webmaster@localhost");

// Other defines can go here. These may be used an file like 'headFile', 'bannerFile' etc. or
// elsewhere in your code.

// **** SECTION THREE ****

// Database connection information. This specifies the database location, user, password, name and
// engine type.
// 'engine' is the type of database engine to use. Options are 'mysql' (depreciated), 'mysqli',
// 'sqlite', or 'POD'.
// Others may be added later
// Change these to match your database information the following is just an example to be changed.

// For testing only
// If you want to try the tests out on other databases just create a 'mysql' or 'PostgreSql'
// database and a table with 'rowid' as the auto incrementing primary key and two fields
// named 'fname', and 'lname' as strings (whatever the database calls a string).
// Change the 'user', 'password', and 'database' to whatever you want.

define('ENGINE_TYPE', 'sqlite3');

switch(ENGINE_TYPE) {
  case 'sqlite3':
    $dbinfo = array('database' => 'test.sdb', 'engine' => 'sqlite3');
    break;
  case 'pgsql':
    $dbinfo = array('host'=>'localhost', 'user'=>'barton', 'password'=>'7098653',
                    'database' => 'barton', 'engine' => 'pgsql');
    break;
  case 'pdo_pgsql':
    $dbinfo =  array('host'=>'localhost', 'user'=>'barton', 'password'=>'7098653',
                     'database' => 'barton', 'engine' => 'pdo_pgsql');
    break;
  case 'pdo_sqlite':
    $dbinfo =  array('database' => 'test.sdb', 'engine' => 'pdo_sqlite');
    break;
  case 'mysql':
    $dbinfo = array('host'=>'localhost', 'user'=>'barton', 'password'=>'7098653',
                    'database' => 'barton', 'engine' => 'mysql');
    break;
  case 'mysqli':
    $dbinfo = array('host'=>'localhost', 'user'=>'barton', 'password'=>'7098653',
                    'database' => 'barton', 'engine' => 'mysqli');
    break;
}

// SiteClass information
// 'siteDomain' should be your domain name like 'bartonphillips.dyndns.org' without subdomains like
// www.
// 'siteName' is how you will call your host.
// 'copyright' obvious
// 'className' the class name you instantiate in your php files. For example if you use SiteClass
// directly use 'className'=>'SiteClass' if you extend SiteClass via MyClass then
// 'className'=>'MyClass'.
// 'memberTable' if you have a table that has member or user information. If you have a
// 'memberTable' it must have several fields. You can add additional fields and if you do so you
// will probably need to extend SiteClass. See SiteClsss.class.php for a list of the required
// fields.
// 'headFile' SiteClass can provide a generic <head> section but you can provide your own
// customized <head> section in a file.
// 'bannerFile' as above.
// 'footerFile' as above.
// 'dbinfo' if you have a database above ($dbinfo). See info on the Database class and
// 'databaseClass' passed to the SiteClass constructor.
// 'count' should hits be counted. If yes then your database must have a 'counter' table as
// described in SiteInfo.class.php.
// 'countMe' should the webmaster or someone special be counted or not.
// 'myUrl' the webmaster or someone special's domain.
// You can extend the $siteinfo array to include things you may in an extended child class of
// SiteInfo.
// A $siteinfo array must be passed to SiteClass like this for example:
//  $S = new SiteClass($siteinfo);
// You can either extend the array here or before you pass it to the SiteClass class.

$siteinfo = array('siteDomain' => "localhost",
                  'siteName' => "Vbox Localhost",
                  'copyright' => "2015 Barton L. Phillips",
                  //'className' => "", // If you have a child class of SiteClass
                  'memberTable' => "members", 
                  'headFile' => SITE_INCLUDES."/head.i.php",
                  //'bannerFile' => SITE_INCLUDES."/banner.i.php",
                  //footerFile => SITE_INCLUDES."/footer.i.php"
                  'dbinfo' => $dbinfo,
                  'count' => false, // false is the default, NOTHING is counted.
                  //'countMe' => true, // Count myUrl. If false myUrl is not counted
                  'myUri' => "bartonphillips.dyndns.org"
                 );

