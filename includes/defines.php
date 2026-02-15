<?php
// Defines for tables.

define("DO_SERVER", "192.241.132.229"); // My server IP address through DigitalOcean
define("MY_IP", "195.252.232.86"); // My personal static IP address through MetroNet
define("SITECLASS_DEFAULT_NAME", "https://bartonphillips.net");
define("SITECLASS_OTHERPAGES", "https://bartonlp.com/otherpages");

define("DEFINES_VERSION", "1.4.0defines-pdo");

// Only if doSiteClass is true we do FULL database

// These are the values for the bots3 table and the tracker table field botAsBits.

define("BOTS_ROBOTS", 1); 
define("BOTS_SITEMAP", 2);
define("BOTS_SITECLASS", 4); // This means that SITECLASS (actually dbPdo isBot()) found that this is a BOT

define("BOTS_ZBOT", 8); // isBot() from the bots table
define("BOTS_GOODBOT", 0x10); // isBot() found +https?:// in user agent string
define("BOTS_COUNTED", 0x20); // Was counted in tracker.php or banner.php
define("BOTS_MATCH", 0x40); // isBot() found a match in the preg_match()
define("BOTS_NOAGENT", 0x80); // isBot() no agent
define("BOTS_VISIBILITYCHANGE", 0x100); // exit logic
define("BOTS_PAGEHIDE", 0x200); // exit logic
define("BOTS_BEFOREUNLOAD", 0x400); // exit logic
define("BOTS_UNLOAD", 0x800); // exit logic
define("BOTS_CRON_CHECKTRACKER", 0x1000); // checktracker.php
define("BOTS_CRON_CHECKVISIBILITY", 0x2000); // checkvisability.php
define("BOTS_HAS_DIFFTIME", 0x4000); // tracker table has non zero difftime
define("BOTS_HAS_FINGER", 0x8000); // has a finger print.
define("BOTS_NO_MYSITEMAP", 0x10000); // No mysitemap.json passed to tracker.php or beacon.php etc.
define("BOTS_HAS_INTERACTION", 0x20000); // has interaction from events like scroll, mousemove, click etc.
define("BOTS_ISMEFALSE", 0x40000); // used the $this-isMeFalse === true. BLP 2025-04-04 - 
define("BOTS_FORCE", 0x80000); // used the $this->forceBot === true. BLP 2025-04-04 - 
define("BOTS_BOT", 0x100000); // If a bot detected in files other than my class files
                              //(tracker.php, beacon.php, robots-sitemap.php etc)

// The values for site in bots3 table 'site' field.
// Used to encode and decode the 'site' field.

define("BOTS_SITEBITMAP", [
                          'bartonphillips.com' => 1,
                          'bartonphillips.net' => 2,
                          'bartonlp.com' => 4,
                          'bartonlp.org' => 8,
                          'bonnieburch.com' => 0x10,
                          'newbernzig.com' => 0x20,
                          'newbern-nc.info' => 0x40,
                          'jt-lawnservice.com' => 0x80,
                          'swam.us' => 0x100,
                          'NO_SITE' => 0x10000, // This is for entries that have NO_SITE info. 
                         ]);

// Tracker defines. 

define("TRACKER_ZERO", 0); // via SiteClass if isMe() is false and isBot is false. 

define("TRACKER_START", 1); // via javascript but not on an event it is just the first thing java does.
define("TRACKER_LOAD", 2); // via javascript
define("TRACKER_NORMAL", 4); // via a GET in header image2 
define("TRACKER_NOSCRIPT", 8); // via a GET in noscript image3
define("BEACON_VISIBILITYCHANGE", 0x10); // via javascript. Just ran out of bits.
define("BEACON_PAGEHIDE", 0x20); // via javascript
define("BEACON_UNLOAD", 0x40); // via javascript
define("BEACON_BEFOREUNLOAD", 0x80); // via javascript
define("TRACKER_TIMER", 0x100); // via javascript. Recurring every interval via setTimer().
                                // Increses by 10 seconds for 50 intervals, about 8 min at end.
define("TRACKER_BOT", 0x200); // via SiteClass if isBot is true
define("TRACKER_CSS", 0x400); // via .htaccess ReWriteRule. This is a GET.
define("TRACKER_ME", 0x800); // via SiteClass if isMe() is true.
define("TRACKER_GOTO", 0x1000); // via webstats.php, webstats.js and bartonlp.com/otherpages/goto.php
define("TRACKER_GOAWAY", 0x2000); // via tracker if called with no info so GoAway
define("TRACKER_ADDED", 0x4000); // BLP 2025-03-08 - via checkvischange.php via CRON once an hour
define("TRACKER_CHECKTRACKER", 0x8000); // BLP 2023-10-20 - via checktracker2.php via CRON once every 1/4 hour
define("TRACKER_ROBOTS", 0x10000); // via dbPod isBot() if the bots3 table show a robots.php call.
define("TRACKER_SITEMAP", 0x20000);// via dbPdo isBot() if the bots3 table show a sitemap.php call.

define("BEACON_MASK", BEACON_VISIBILITYCHANGE | BEACON_PAGEHIDE | BEACON_UNLOAD | BEACON_BEFOREUNLOAD);

// This is used in dbPdo in the isBot() function to take apart the BOTS_... and
// TRACKER_... values. If there is no TRACKER_... value that matches a BOTS_... value then null.

define("BOTS_ROBOTMAP", [
                         BOTS_ROBOTS => TRACKER_ROBOTS,
                         BOTS_SITEMAP => TRACKER_SITEMAP,
                         BOTS_SITECLASS => TRACKER_BOT,
                         BOTS_ZBOT => null,
                         BOTS_GOODBOT => null,
                         BOTS_NOAGENT => null,
                         BOTS_VISIBILITYCHANGE => BEACON_VISIBILITYCHANGE,
                         BOTS_PAGEHIDE => BEACON_PAGEHIDE,
                         BOTS_BEFOREUNLOAD => BEACON_BEFOREUNLOAD,
                         BOTS_UNLOAD => BEACON_UNLOAD,
                         BOTS_CRON_CHECKTRACKER => null,
                         BOTS_CRON_CHECKVISIBILITY => null,
                         BOTS_HAS_DIFFTIME => null,
                         BOTS_HAS_FINGER => null,
                         BOTS_ISMEFALSE => null,
                         BOTS_FORCE => null,
                        ]);

// Array of my servers.

define("MY_HOSTS", [
                    "bartonphillips.com",
                    "bartonphillips.net",
                    "bartonlp.com",
                    "bartonlp.org",
                    "bonnieburch.com",
                    "newbernzig.com",
                    "newbern-nc.info",
                    "jt-lawnservice.com",
                    "swam.us"
                   ]);
