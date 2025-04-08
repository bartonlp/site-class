<?php
// The .htaccess file has: ReWriteRule ^robots.txt$ robots.php [L,NC]
// This file reads the rotbots.txt file and outputs it and then gets the user agent string and
// saves it in the bots table.
// NOTE: this file can only be run using mysqli or PDO with the mysql engine!

define("ROBOT_VERSION", '2.1.0'); // New logic for bots using bitmap site.

$_site = require_once(getenv("SITELOADNAME"));
$_site->noTrack = true;
$_site->noGeo = true;
$S = new Database($_site);

$rob = BOTS_ROBOTS;

if(!file_exists($S->path . "/robots.txt")) {
  echo "<h1>404 - FILE NOT FOUND</h1>";
  exit();
}

$robots = file_get_contents("./robots.txt");
header("Content-Type: text/plain");
echo $robots . "\n# From robots.php\n";

if($S->isMe()) return;

$agent = $S->agent;
$ip = $S->ip;
$page = basename($S->self);

// BLP 2021-12-26 -- robots is 1 if we do an insert or robots=robots|2 

$siteBit = BOTS_SITEBITMAP[$S->siteDomain] ?? 0; // BLP 2025-04-03 - all of the siteNames are domain names.
  
$S->sql("insert into $S->masterdb.bots3 (ip, agent, page, count, robots, site, created) ".
        "values('$ip', '$agent', '$page', 1, $rob, $siteBit, now()) ".
        "on duplicate key update robots=robots | $rob");

// Insert or update logagent

$S->sql("insert into $S->masterdb.logagent (site, ip, agent, count, created, lasttime) values('$S->siteName', '$ip', '$agent', 1, now(), now()) ".
        "on duplicate key update count=count+1, lasttime=now()");

// Add to tracker
// BLP 2025-03-23 - add TRACKER_ROBOTS
// BLP 2025-03-24 - I should look to see if this $agent looks like a bot with $S->isBot($agent).
// This would set up $botAs.

$S->sql("insert into $S->masterdb.tracker(site, ip, page, agent, botAs, isjavascript, starttime) ".
        "values('$S->siteName', '$ip', 'robots.php', '$agent', 'robots', ".
        TRACKER_ROBOTS . ", now()) ".
        "on duplicate key update count=count+1, botAs=botAs+',robot', botAsBits=botAsBits|". BOTS_ROBOTS . ", ".
        "isjavascript=isjavascript |" . TRACKER_ROBOTS); // BLP 2025-03-29 - 

error_log("robots.php end: ip=$ip, site=$siteBit, robots=$rob");