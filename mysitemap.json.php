#! /usr/bin/env php
<?php
/*
  This file can used to create the 'mysitemap.json' file.
  Run this as a CLI by typing:
  php ./mysitemap.php > mysitemap.json
*/

// Example mysitemap.json

// Email info

define('EMAILADDRESS', "me@example.com");
define('EMAILRETURN', "me@example.com");
define('EMAILFROM', "webmaster@example.com");

// Database connection information. This specifies the database location, user, password, name and
// engine type.
// 'engine' is the type of database engine to use. Options are 'mysqli', or 'sqlite'.
// Change these to match your database information the following is just an example to be changed.

$dbinfo = array('host' => 'localhost',
                'user' => 'your_user_name',
                'password' => 'password',
                'database' => 'your_database_name',
                'engine' => 'mysqli', // mysqli or sqlite
               );

// SiteClass information
// 'siteDomain' should be your primary domain name like 'example.com' without subdomains like www.
// 'siteName' is how you will call your host. Should not have spaces.
// 'copyright' obvious
// 'className' the class name you instantiate in your php files. For example if you use SiteClass
//   directly use 'className'=>'SiteClass' if you extend SiteClass via 'MyClass' then
//   'className'=>'MyClass'.
// 'memberTable' if you have a table that has member or user information. 
// 'headFile' SiteClass can provide a generic <head> section but you can provide your own
//   customized <head> section in a file.
// 'bannerFile' as above.
// 'footerFile' as above.
// 'dbinfo' if you have a database above ($dbinfo). See info on the Database class and
//   'databaseClass' passed to the SiteClass constructor.
// 'count' should hits be counted. If yes then your database must have a 'counter' table as
//   described in SiteClass.class.php. 'count' is false by default.
// 'countMe' should the webmaster or someone special be counted or not. 'countMe' default false.
// 'myUrl' the webmaster or someone special's domain.
//   You can extend the $_site array to include things you may want in an extended child class of
//   SiteClass.
//
// A $_site array may be passed to SiteClass like this for example:
//  $S = new SiteClass($_site);
// You can either extend the array here or before you pass it to the SiteClass class or SiteClass
// child.

$_site = array('siteDomain' => "localhost",
               'siteName' => 'TheNameOfYourSite', // no spaces
               //'mainTitle' => "Test Site",
               //'emailDomain' => null,
               //'path' => "/var/www/html",
               'className' => "SiteClass",
               //'copyright' => "2016 Barton L. Phillips",
               //'author' => "Barton L. Phillips, mailto:bartonphillips@gmail.com",
               //'memberTable' => null,
               //'masterdb' => 'your_master_database', // This is where tables used by multiple sites live
               //'headFile' => "includes/head.i.php",
               //'bannerFile' => "includes/banner.i.php",
               //'footerFile' => "includes/footer.i.php"
               'dbinfo' => $dbinfo,
               'count' => true, // NOTE this defaults to false
               'countMe' => true, // Count BLP. Also defaults to false
               //'daycountwhat' => 'all', // what should we daycount? Can be a filename, all, or an array of filenames.
               //'analysis' => true, // update the barton.analysis table
               //'trackerImg1' => "/images/blp-image.png", // script
               //'trackerImg2' => "/images/146624.png", // normal
               'myUri' => "bartonphillips.dyndns.org",
               'EMAILADDRESS' => EMAILADDRESS,
               'EMAILRETURN' => EMAILRETURN,
               'EMAILFROM' => EMAILFROM,
              );

$json = json_encode($_site, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

echo "$json\n";
