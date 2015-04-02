<?php
// Example .sitemap.php
// This file should be in the top directory of a project. For example say I have a website at
// DocumentRoot/myproject/ (maybe /var/www/myproject)
// The .sitemap.php should then reside at /var/www/myproject/.sitemap.php

// DOC_ROOT is set in the siteautoload.class.php.
// The normal structure is:
// DOC_ROOT
// |       \ includes (has SiteClass, UpdateSite etc.)
// |                 \database-engines (has all the database engines and error and exception code)
//  \SITE_ROOT (defined in siteautoload.class.php. The site code lives here like index.php etc.)
//            \includes (the sites subclass lives here along with any special site classes)

// These defines position the site. Top is your DOCUMENT_ROOT. If you are using Apache this would
// be specified in your /etc/apache2/apache2.conf or /etc/apache2/sites-enabled/xxx.conf file as:
// DocumentRoot /var/www
//
// INCLUDES should normally be off the DOCUMENT_ROOT
// DATABASE_ENGINES is where the database classes live and it should normally be off INCLUDES
// SITE_INCLUDES is where site specific include files live like the 'headFile', 'bannerFile' or the
// 'footerFile' as well as custom site specific classes.
// SITE_ROOT is determined by the location of this .sitemap.php file which in the example would be
// at /var/www/myproject so SITE_ROOT would be /var/www/myproject.
// Say you have three virtual hosts 1) Site1, 2) Site2, 3) Site3. You would probably have three
// config files in /etc/apache2/sites-enabled one for each site. Say they have three DOCUMENT_ROOTs
// /var/www/Site1, /var/www/Site2, /var/www/Site3.
// Say Site3 has two projects 1) mysite which is directly under /var/www/Site3/index.php,
// 2) anothersite which is a subdomain of Site3(http://www.Site3/anothersite/index.php.
// If your database info or siteinfo are not the same for both projects then you would have one
// .sitemap.php file at /var/www/Site3/.sitemap.php (for the main site) and one at
// /var/www/Site3/anothersite/.sitemap.php for the http://www.Site3/anothersite.

define('TOP', '/home/barton/www');
define('INCLUDES', TOP."/includes");
define('DATABASE_ENGINES', INCLUDES."/database-engines");
define('SITE_INCLUDES', SITE_ROOT."/includes"); // SITE_ROOT is defined in siteautoload.php!

// The following defines three email addresses are used in SiteClass.class.php to send error info

define('EMAILADDRESS', "bartonphillips@gmail.com");
define('EMAILRETURN', "bartonphillips@gmail.com");
define('EMAILFROM', "webmaster@localhost");

// Other defines can go here. These may be used an file like 'headFile', 'bannerFile' etc. or
// elsewhere in your code.

define('LOGFILE', SITE_ROOT."/database.log");


// Database connection information. This specifies the database location, user, password, name and
// engine type.
// 'engine' is the type of database engine to use. Options are 'mysql' (depreciated), 'mysqli',
// 'sqlite', or 'POD'.
// Others may be added later
// Change these to match your database information the following is just an example to be changed.

$dbinfo = array('host' => 'localhost',
                'user' => 'barton',
                'password' => '7098653',
                'database' => 'barton',
                'engine' => 'mysqli'
               );

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
                  //'className' => "",
                  //'memberTable' => "users", // Don't set the memberTable!!! 6/9/2013
                  //'headFile' => SITE_INCLUDES."/head.i.php",
                  //'bannerFile' => SITE_INCLUDES."/banner.i.php",
                  //footerFile => SITE_INCLUDES."/footer.i.php"
                  'dbinfo' => $dbinfo,
                  'count' => true,
                  'countMe' => true, // Count BLP
                  'myUri' => "bartonphillips.dyndns.org"
                 );

