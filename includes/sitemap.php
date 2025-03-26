<?php
// This file is a substitute for Sitemap.xml. This file is RewriteRuled in
// .htaccess to read Sitemap.xml and output it. It also writes a record into the bots tables and
// logagent.
// NOTE: this can only be run with mysqli or PDO using engine mysql!

define("SITEMAP_VERSION", '2.0.1'); // BLP 2025-03-23 - add TRACKER_ROBOTS to tracker table.

$_site = require_once(getenv("SITELOADNAME"));
$_site->noTrack = true;
$_site->noGeo = true;
$S = new Database($_site);

$map = BOTS_SITEMAP;

if(!file_exists("./Sitemap.xml")) {
  echo "<h1>404 - FILE NOT FOUND</h1>";
  exit();
}

$sitemap = file_get_contents("./Sitemap.xml");
header("Content-Type: application/xml");
echo $sitemap  . "<!-- From sitemap.php -->";

if($S->isMe()) return;

$ip = $S->ip;
$agent = $S->agent;

try {
  // BLP 2021-12-26 -- robots is 4 for insert and robots=robots|8 for update.

  $S->sql("insert into $S->masterdb.bots (ip, agent, count, robots, site, creation_time, lasttime) ".
          "values('$ip', '$agent', 1, $map, '$S->siteName', now(), now())");
} catch(Exception $e) {
  if($e->getCode() == 1062 || $e->getCode() == 23000) { // duplicate key
    $S->sql("select site from $S->masterdb.bots where ip='$ip'");

    $who = $S->fetchrow('num')[0];

    if(!$who) {
      $who = $S->siteName;
    }
    if(strpos($who, $S->siteName) === false) {
      $who .= ", $S->siteName";
    }
    $S->sql("update $S->masterdb.bots set robots=robots|$map, count=count+1, site='$who', lasttime=now() ".
              "where ip='$ip' and agent='$agent'");
  } else {
    error_log("robots: ".print_r($e, true));
  }
}

// BLP 2021-11-12 -- 4 for sitemap
// BLP 2021-12-26 -- bots2 primary key is 'ip, agent, date, site, which'.

$S->sql("insert into $S->masterdb.bots2 (ip, agent, page, date, site, which, count, lasttime) ".
        "values('$ip', '$agent', 'sitemap', now(), '$S->siteName', $map, 1, now()) ".
        "on duplicate key update count=count+1, lasttime=now()");

// Insert or update logagent

$S->sql("insert into $S->masterdb.logagent (site, ip, agent, count, created, lasttime) values('$S->siteName', '$ip', '$agent', 1, now(), now()) ".
        "on duplicate key update count=count+1, lasttime=now()");

// Add to tracker
// BLP 2025-03-23 - add TRACKER_ROBOTS

$S->sql("insert into $S->masterdb.tracker(site, ip, page, agent, botAs, isjavascript, starttime, lasttime) ".
        "values('$S->siteName', '$ip', 'robots.php', '$agent', 'robots',  ".
        TRACKER_ROBOTS . ", now(), now()) ".
        "on duplicate key update botAs=botAs+',robot', isjavascript=isjavascript |" . TRACKER_ROBOTS . ", lasttime=now()");
