# SiteClass Verion 3.4.1, Database Version 2.0.0, dbMysqli Version 2.0.0

**SiteClass** version 3.4.1 is a PHP mini framework for simple, small websites. It can be esaly combined with other frameworks or templeting engines if needed. 
For small websites I feel that frameworks like Laravel or Meteor etc. are just too much.

Updated BLP 2022-08-14 - Change had coded references to bartonphillips.net to $h, $b, $this (from mysitemap.json)  
Also moved tracker.php, tracker.js and beacon.php to the includes directory. They are now symlinked to https://bartonphillips.net.  
Updated BLP 2022-07-31 - Moved functins from SiteClass to Database. Added versions to all classes  
Updated BLP 2022-04-30 - Work on daycounts, tracker and checkIfBot. I may remove some error_log() messages later.  
Updated BLP 2022-04-24 - added defines.php with the tracker, bots and beacon constants.

This project has several parts that can function standalone or combined.

* Database.class.php (version 2.0.0): provides a wrapper for several different database engines.
* dbTables.class.php (version 1.0.0): uses the functionality of Database.class.php to make creating tables easy.
* ErrorClass.class.php (version 2.0.0): Error and Exception classes
* SqlException.class.php (version 2.0.0): Sql exception class.
* SiteClass.class.php (version 3.4.0): tools for making creating a site a little easier. The class provides methods to help with headers, banners, footers and more.
* defines.php : constants for tracker(), tracker.php, beacon.php, robots.php, sitemap.php and checktracker2.php. This has all of the constants 
like TRACKER_BOT, BOTS_ROBOTS etc.

The following database engines are provided:

dbMysqli.class.php : (rigorously tested) This is the latest PHP version of the MySql database engine.
Update BLP 2022-07-31 - dbSqlite.class.php : sqlite3 No longer supported

## SiteClass Documentation 

[SiteClass Documentation](https://bartonlp.github.io/site-class)

## Contact Me

Barton Phillips : [bartonphillips@gmail.com](mailto://bartonphillips@gmail.com)  
[My Website](http://www.bartonphillips.com)  
Copyright &copy; 2022 Barton Phillips
