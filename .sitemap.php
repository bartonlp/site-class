<?php
// DOC_ROOT is set in the siteautoload.class.php.
// The normal structure is:
// DOC_ROOT
// |       \ includes (has SiteClass, UpdateSite etc.)
// |                 \database-engines (has all the database engines and error and exception code)
//  \SITE_ROOT (defined in siteautoload.class.php. The site code lives here like index.php etc.)
//            \includes (the sites subclass lives here along with any special site classes)

define('TOP', '/home/barton/www');
define('INCLUDES', TOP."/includes");
define('DATABASE_ENGINES', INCLUDES."/database-engines");
define('SITE_INCLUDES', SITE_ROOT."/includes"); // SITE_ROOT is defined in siteautoload.php!

// Email info and logfile location

define('LOGFILE', SITE_ROOT."/database.log");

define('EMAILADDRESS', "bartonphillips@gmail.com");
define('EMAILRETURN', "bartonphillips@gmail.com");
define('EMAILFROM', "webmaster@localhost");

// Database connection information
// 'engine' is the type of database engine to use. Options are 'mysqli', 'sqlite'.
// Others may be added later

$dbinfo = array('host' => 'localhost',
                'user' => 'barton',
                'password' => '7098653',
                'database' => 'barton',
                'engine' => 'mysqli'
               );

// SiteClass information
// See the SiteClass constructor for other possible values like 'count', 'emailDomain' etc.

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

