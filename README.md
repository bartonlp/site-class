# SiteClass Verion 3.2.4

**SiteClass** version 3.2.4 is a PHP mini framework for simple, small websites. It can be esaly combined with other frameworks or templeting engines if needed. 
For small websites I feel that frameworks like Laravel or Meteor etc. are just too much.

Updated BLP 2022-04-30 - Work on daycounts, tracker and checkIfBot. I may remove some error_log() messages later.
Updated BLP 2022-04-24 - added defines.php with the tracker, bots and beacon constants.

This project has several parts that can function standalone or combined.

* Database.class.php : provides a wrapper for several different database engines.
* dbTables.class.php : uses the functionality of Database.class.php to make creating tables easy.
* ErrorClass.class.php : Error and Exception classes
* SiteClass.class.php : tools for making creating a site a little easier. The class provides methods to help with headers, banners, footers and more.
* defines.php : constants for tracker(), tracker.php, beacon.php, robots.php, sitemap.php and checktracker2.php. This has all of the constants 
like TRACKER_BOT, BOTS_ROBOTS, BOTS2_CRON_ZERO etc.

The following database engines are provided:

1. dbMysqli.class.php : (rigorously tested) This is the latest PHP version of the MySql database engine.
2. dbSqlite.class.php : sqlite3 (used for the examples)

There are a couple of additional databases but they have not been rigouously tested.

## SiteClass Documentation 

[SiteClass Documentation](https://bartonlp.github.io/site-class)

## Contact Me

Barton Phillips : [bartonphillips@gmail.com](mailto://bartonphillips@gmail.com)  
[My Website](http://www.bartonphillips.com)  
Copyright &copy; 2022 Barton Phillips
