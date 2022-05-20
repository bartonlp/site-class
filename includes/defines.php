<?php
// Defines for tables.
// BLP 2022-04-24 - New. Values for SiteClass, tracker and beacon.
/*
  From 'rotots.txt': Initial Insert=1, Update= OR 2.
  From 'SiteClass' scan: Initial Insert=4, Update= OR 8.
  From 'Sitemap.xml': Initial Insert=16(x10), Update= OR 32(x20).
  From CRON indicates a Zero (curl type) in the 'tracker' table: 258(x100)
*/

// Bots and bots2 Tables.
// These are all done via SiteClass trackbots() which does both the bots and bots2 tables.
define("BOTS_ROBOTS", 1); // the first time 
define("BOTS_SITEMAP", 2);
define("BOTS_SITECLASS", 4);
define("BOTS_CRON_ZERO", 0x100);

/*
  1 for 'robots.txt'
  2 for 'Sitemap.xml'
  4 for 'SiteClass'
  0x100 for tracker value 0 (curl type)
*/

/*
  // 0x20000 (tracker called without info -- GoAway)
  // 0x10000 (b-visibilitychanged)
  // 0x8000 (isMe)
  // 0x4000 (csstest)
  // 0x2000 (bot via SiteClass)
  // 0x1000 (timer)
  // 0x800 (t-visibilitychange)
  // 0x400 (t-pagehide)
  // 0x200 (t-unload)
  // 0x100 (t-beforeunload)
  // 0x80 (b-beforeunload)
  // 0x40 (b-unload)
  // 0x20 (b-pagehide)
  // 0x10 (noscript)
  // 0xf (start=1,load=2,script=4,normal=8)
*/

// Tracker defines. These happen in different places all via tracker.php exceipt the ones marked as
// SiteClass. The one marked 'via javascript' are instigated by the tracker.js program which does
// AJAX calls to tracker.php or beacon.php.

define("TRACKER_START", 1); // via javascript but not on an event it is just the first thing java does.
define("TRACKER_LOAD", 2); // via javascript
define("TRACKER_SCRIPT", 4); // header image1 logo
define("TRACKER_NORMAL", 8); // header image2 
define("TRACKER_NOSCRIPT", 0x10); // header image3
define("TRACKER_BEFOREUNLOAD", 0x100); // via javascript
define("TRACKER_UNLOAD", 0x200); // via javascript
define("TRACKER_PAGEHIDE", 0x400); // via javascript
define("TRACKER_VISIBILITYCHANGE", 0x800); // via javascript
define("TRACKER_TIMER", 0x1000); // via javascript. Recurring every interval via setTimer(). Increses by 10 seconds for 50 intervals, about 8 min at end.
define("TRACKER_BOT", 0x2000); // This happens in SiteClass if isBot is true
define("TRACKER_CSS", 0x4000); // This is triggered by .htaccess ReWriteRule.
define("TRACKER_ME", 0x8000); // This happens in SiteClass, isMe() is true.
define("TRACKER_ZERO", 0); // This happens in SiteClass, isMe() is false and isBot is false. 
define("TRACKER_GOTO", 0x10000); // see bartonphillips.com/goto.php, also webtats.php and webstats.js
define("TRACKER_GOAWAY", 0x20000); // tracker called with no info so GoAway

define("TRACKER_MASK", TRACKER_BEFOREUNLOAD | TRACKER_UNLOAD | TRACKER_PAGEHIDE | TRACKER_VISIBILITYCHANGE);

// Beacon is part of isJavaScript in the tracker table.
define("BEACON_VISIBILITYCHANGE", 0x40000); // via javascript. Just ran out of bits.
define("BEACON_PAGEHIDE", 0x20); // via javascript
define("BEACON_UNLOAD", 0x40); // via javascript
define("BEACON_BEFOREUNLOAD", 0x80); // via javascript

define("BEACON_MASK", BEACON_VISIBILITYCHANGE | BEACON_PAGEHIDE | BEACON_UNLOAD | BEACON_BEFOREUNLOAD);

// foundBotAs. This is the value in the tracker table as botAs.
define("BOTAS_MATCH", "match");
define("BOTAS_TABLE", "table");
define("BOTAS_NOT", null);
define("BOTAS_ROBOT", "robot");
define("BOTAS_SITEMAP", "sitemap");
define("BOTAS_ZERO", "zero");
define("BOTAS_COUNTED", "counted");
// Define the DigitalOcean Server
define("DO_SERVER", "157.245.129.4");
