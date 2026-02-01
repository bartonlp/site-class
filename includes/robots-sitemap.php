<?php
// robots-sitemap.php (conbined file).
// The .htaccess file has: ReWriteRule ^robots\.txt$ robots.php [L,NC] and
// ReWriteRule ^sitemap\.xml$ sitemap.php [L,NC].
// This file reads the corresponding txt file and outputs it and then gets the user agent string and
// saves it in the bots3 table.
// NOTE: this file can only be run using PDO with the mysql engine!

define("ROBOT_SITEMAP_VERSION", '4.0.0');

$_site = require_once(getenv("SITELOADNAME"));
// Do not do Google's Geo mapping products (Maps, Earth, Street View)
$_site->noGeo = true;
// Set Development true.
ErrorClass::setDevelopment(true);
// Start the Database as it does not SiteClass.
$S = new Database($_site);

$self = basename($S->self);

switch($self) {
  case 'robots.php':
    $file = "robots.txt";
    break;
  case 'sitemap.php':
    $file = "Sitemap.xml";
    break;
  default:
    throw new Exception("robots-setemap.php: Invalid value, line=". __LINE__);
    break;
}

if(!file_exists($S->path . "/$file")) {
  echo "<h1>404 - FILE NOT FOUND</h1>";
  exit();
}

$info = file_get_contents("./$file");
header("Content-Type: text/plain");
echo $info . "\n# From $file\n";

// If I have me return.
if($S->isMe()) {
  return;
}

$agent = $S->agent;
$ip = $S->ip;

// Now ONLY use if doSiteClass true.

if($S->doSiteClass === true) {
  // Insert or update logagent

  $S->sql("
insert into $S->masterdb.logagent (site, ip, agent, count, created, lasttime)
values('$S->siteName', '$ip', '$agent', 1, now(), now())
on duplicate key update count=count+1, lasttime=now()");

  switch(basename($S->self)) {
    case 'robots.php':
      $botBits = BOTS_ROBOTS;
      $java = TRACKER_ROBOTS;
      break;
    case 'sitemap.php':
      $botBits = BOTS_SITEMAP;
      $java = TRACKER_SITEMAP;
      break;
    default:
      throw new Exception("robots-setemap.php: Invalid valid, line=". __LINE__);
  }

  // Is this a bot? We know that the client looked at the robots.txt but this might not really be a
  // bot.

  if($S->isBot($agent)) {
    $java |= $S->trackerBotInfo;
    $botBits |= $S->botAsBits;
  }

  // Add to tracker

  $S->sql("
  insert into $S->masterdb.tracker(site, ip, page, agent, botAsBits, isjavascript, starttime)
  values('$S->siteName', '$ip', '$file', '$agent', $botBits, $java, now())
  on duplicate key update count=count+1, botAsBits=botAsBits|$botBits,
  isjavascript=isjavascript|$java"); 

  $S->updateBots3($ip, $agent, $file, $S->siteName, $botBits);

  $hexBotBits = dechex($botBits);
  logInfo("$file: ip=$ip, page=$file, site=$S->siteName, robots=$hexBotBits");
}
