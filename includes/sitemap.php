<?php
// This file is a substitute for Sitemap.xml. This file is RewriteRuled in
// .htaccess to read Sitemap.xml and output it. It also writes a record into the bots tables and
// logagent.
// NOTE: this can only be run with mysqli or PDO using engine mysql!

define("SITEMAP_VERSION", '2.1.0'); // BLP 2025-04-03 - new bots3 logic

$_site = require_once getenv("SITELOADNAME");
$_site->noTrack = true;
$_site->noGeo = true;
$S = new Database($_site);

if(!file_exists("./Sitemap.xml")) {
  echo "<h1>404 - FILE NOT FOUND</h1>";
  error_log("sitemap.php: Sitemap.xml not found, line=". __LINE__);
  exit();
}

$sitemap = file_get_contents("./Sitemap.xml");
header("Content-Type: application/xml");
echo $sitemap  . "<!-- From sitemap.php -->";

if($S->isMe()) return;

$ip = $S->ip;
$agent = $S->agent;
$page = basename($S->self);

$siteBit = BOTS_SITEBITMAP[$S->siteDomain] ?? 0; // BLP 2025-04-03 - all of the siteNames are domain names.
$siteMap = BOTS_SITEMAP;

$S->sql("insert into $S->masterdb.bots3 (ip, agent, page, count, robots, site, created) ".
        "values('$ip', '$agent', '$page', 1, $siteMap, $siteBit, now()) ".
        "on duplicate key update robots=robots|$siteMap, site=site|$siteBit");

// Insert or update logagent

$S->sql("insert into $S->masterdb.logagent (site, ip, agent, count, created, lasttime) values('$S->siteName', '$ip', '$agent', 1, now(), now()) ".
        "on duplicate key update count=count+1, lasttime=now()");

// Add to tracker
// BLP 2025-03-23 - add TRACKER_ROBOTS

$S->sql("insert into $S->masterdb.tracker(site, ip, page, agent, botAs, botAsBits, isjavascript, starttime) ".
        "values('$S->siteName', '$ip', 'robots.php', '$agent', 'robots', ". BOTS_SITEMAP .
        ", " . TRACKER_ROBOTS . ", now()) ".
        "on duplicate key update count=count+1, botAs=botAs+',robot', botAsBits=botAsBits|". BOTS_SITEMAP .
        ", isjavascript=isjavascript |" . TRACKER_ROBOTS . ", lasttime=now()");
