<?php
// Defines for tables.
// BLP 2024-04-12 - add and update defines for myip and digitalocean

define("DO_SERVER", "192.241.132.229"); // My server IP address through DigitalOcean
define("MY_IP", "195.252.232.86"); // My personal static IP address through MetroNet

define("DEFINES_VERSION", "1.1.2defines-pdo"); // BLP 2025-03-03 - Added BOTAS_SITECLASS. Reorginized and put BEACON_* in the right place.

// Bots and bots2 Tables.
// These are all done via SiteClass trackbots() which does both the bots and bots2 tables.
define("BOTS_ROBOTS", 1); 
define("BOTS_SITEMAP", 2);
define("BOTS_SITECLASS", 4);
define("BOTS_CRON_ZERO", 0x100);

// Tracker defines. These happen in different places all via tracker.php exceipt the ones marked as
// SiteClass. The one marked 'via javascript' are instigated by the tracker.js program which does
// AJAX calls to tracker.php or beacon.php.

define("TRACKER_START", 1); // via javascript but not on an event it is just the first thing java does.
define("TRACKER_LOAD", 2); // via javascript
define("TRACKER_NORMAL", 4); // header image2 
define("TRACKER_NOSCRIPT", 8); // header image3
define("BEACON_VISIBILITYCHANGE", 0x10); // via javascript. Just ran out of bits.
define("BEACON_PAGEHIDE", 0x20); // via javascript
define("BEACON_UNLOAD", 0x40); // via javascript
define("BEACON_BEFOREUNLOAD", 0x80); // via javascript
define("TRACKER_TIMER", 0x100); // via javascript. Recurring every interval via setTimer(). Increses by 10 seconds for 50 intervals, about 8 min at end.
define("TRACKER_BOT", 0x200); // This happens in SiteClass if isBot is true
define("TRACKER_CSS", 0x400); // This is triggered by .htaccess ReWriteRule.
define("TRACKER_ME", 0x800); // This happens in SiteClass, isMe() is true.
define("TRACKER_ZERO", 0); // This happens in SiteClass, isMe() is false and isBot is false. 
define("TRACKER_GOTO", 0x1000); // see bartonphillips.com/goto.php, also webtats.php and webstats.js
define("TRACKER_GOAWAY", 0x2000); // tracker called with no info so GoAway
define("CHECKTRACKER", 0x8000); // BLP 2023-10-20 - checktracker2.php ors in this value with TRACKER_BOT if the
define("TRACKER_ROBOTS", 0x10000); // robots.php
define("TRACKER_SITEMAP", 0x20000);// sitemap.php

define("BEACON_MASK", BEACON_VISIBILITYCHANGE | BEACON_PAGEHIDE | BEACON_UNLOAD | BEACON_BEFOREUNLOAD);

// botAs values.

define("BOTAS_MATCH", "match");
define("BOTAS_NOT", null);
define("BOTAS_ROBOT", "robot");
define("BOTAS_SITEMAP", "sitemap");
define("BOTAS_SITECLASS", "BOT"); // BLP 2025-03-03 - 
define("BOTAS_ZERO", "zero");
define("BOTAS_ZBOT", "zbot"); // BLP 2023-11-04 - 
define("BOTAS_COUNTED", "counted");
define("BOTAS_NOAGENT", "no-agent"); // BLP 2023-10-27 - added
define("BOTAS_GOODBOT", "good-bot"); // BLP 2025-01-14 - added
