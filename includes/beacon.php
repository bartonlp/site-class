<?php
// Beacon from tracker.js

define("BEACON_VERSION", "4.0.0beacon"); // BLP 2023-01-30 - Add check for $_site.

// BLP 2023-01-30 - If you want the version defined ONLY and no other information.
// If we have a valid $_site or the $__VERSION_ONLY, then just return the version info.

if($_site || $__VERSION_ONLY === true) {
  return BEACON_VERSION;
}

// The normal beacon starts here.
// NOTE: we can get $_site from the mysitemap.json at bartonlp.com/otherpages because all of the
// inportant information is passed to this program via 'php://input'

// If $_site is null try the autoload.php which should be off this directory

if($_SERVER['HTML_HOST'] == 'bartonphillips.org') {
  $_site = require_once("autoload.php"); // We are at ~/bartonphillips.org/site-class/includes.
  error_log("*** beacon.php HP-Envy Server, use autoload.php");  
  $_site->trackerLocationJs =  'https://bartonphillips.org/site-class/includes/tracker.js';
  $_site->trackerLocation = 'https://bartonphillips.org/site-class/includes/tracker.php';
  $_site->beaconLocation = 'https://bartonphillips.org/site-class/includes/beacon.php';
} else {
  $_site = require_once(getenv("SITELOADNAME"));
}

$_site->noTrack = true;
$_site->noGeo = true;

$S = new Database($_site);
//error_log("*** beacon.php \$S=" . var_export($S, true));
          

//$DEBUG1 = true; // COUNTED real+1 bots-1
//$DEBUG2 = true; // After update tracker table
//$DEBUG3 = true; // visablechange
//$DEBUG_IPS = true; // show ip mismatches.
//$DEBUG_ISABOT = true;

// The input comes via php as json data not $_GET or $_POST

$data = file_get_contents('php://input');

$data = json_decode($data, true);

$id = $data['id'];
$type = $data['type'];
$site = $data['site'];
$ip = $data['ip'];
$visits = $data['visits'];
$thepage = $data['thepage'];
$state = $data['state'];

//error_log("*** beacon: site=$site, ip=$ip, page=$thepage, state=$state, type=$type");

// Here the isMeFalse is passed as a true bool.
$S->isMeFalse = $data['isMeFalse'];

$msg = strtoupper($type);

// If we do not have an $id or $visits that is an error.

if(!$id || $visits === null) {
  if(!$msg) $msg = "NO_TYPE_NO_VISITS";
  error_log("beacon:  NO ID, $ip, $site, $msg -- visits=$visits, \$S->ip=$S->ip, \$S->self=$S->self, \$S->agent=$S->agent, time=" . (new DateTime)->format('H:i:s:v'));
  
  $S->sql("insert into $S->masterdb.badplayer (ip, site, page, botAs, type, count, errno, errmsg, agent, created, lasttime) " .
            "values('$S->ip', '$site', '$S->self', 'counted', '{$msg}_BEACON_GOAWAY', 1, '-104', 'NO ID Go away', '$S->agent', now(), now()) ".
            "on duplicate key update count=count+1, lasttime=now()");

  echo "<h1>GO AWAY</h1><p>" . BEACON_VERSION . "</p>";
  exit();
}

// Now get botAs and isJavaScrip etc. from the tracker table.

if($S->sql("select botAs, isJavaScript, hex(isJavaScript), difftime, finger, agent from $S->masterdb.tracker where id=$id")) {
  [$botAs, $java, $js, $difftime, $finger, $agent] = $S->fetchrow('num');
} else {
  error_log("beacon: NO record for $id, line=" . __LINE__);
}

// BLP 2023-08-08 - tracker.php does not do any 'exit' tracking any more!

switch($type) {
  case "pagehide":
    $beacon = BEACON_PAGEHIDE;
    break;
  case "unload":
    $beacon = BEACON_UNLOAD;
    break;
  case "beforeunload":
    $beacon = BEACON_BEFOREUNLOAD;
    break;
  case "visibilitychange":
    $beacon = BEACON_VISIBILITYCHANGE;
    break;
  default:
    error_log("beacon: $id, $ip, $site, $thepage, SWITCH_ERROR_{$type}, botAs=$botAs, java=$js, visits=$visits -- \$S->ip=$S->ip, \$S->agent=$S->agent");
    exit();
}

$java |= $beacon;
$js2 = strtoupper(dechex($java));

if($DEBUG_IPS && ($ip != $S->ip)) {
  error_log("beacon:  $id, $ip, $site, $thepage, IP_MISMATCH_{$msg}, \$ip != \$S->ip -- \$S->ip=$S->ip, botAs=$botAs, jsin=$js, jsout=$js2, visits=$visits");
}

// Is this a bot?

if($agent && $S->isBot($agent)) {
  if($DEBUG_ISABOT) error_log("beacon:  $id, $ip, $site, $thepage, ISABOT_{$msg}1, state=$state, botAs=$botAs, visits=$visits, jsin=$js, jsout=$js2, time=" . (new DateTime)->format('H:i:s:v'));
  exit(); // If this is a bot don't bother
}

if(!str_contains($botAs, BOTAS_COUNTED)) {
  // Does not contain BOTAS_COUNTED
  
  if(!empty($botAs)) {
    // This must have robot, sitemap, or zero
    
    if($DEBUG_ISABOT) error_log("beacon:  $id, $ip, $site, $thepage, ISABOT_{$msg}2, state=$state, botAs=$botAs, visits=$visits, jsin=$js, jsout=$js2, difftime=$difftime, time=" . (new DateTime)->format('H:i:s:v'));
    exit();
  }
}

if(!$S->isMyIp($ip) && !str_contains($botAs, BOTAS_COUNTED)) { // it is not ME and it has not been counted yet.
  $S->sql("select `real`, bots, visits from $S->masterdb.daycounts where date=current_date() and site='$site'");
  [$dayreal, $daybots, $dayvisits] = $S->fetchrow('num');
  $dayreal++;
  $dayvisits += $visits;

  $S->sql("update $S->masterdb.daycounts set `real`=$dayreal, visits=$dayvisits where date=current_date() and site='$site'");

  if($DEBUG1) error_log("beacon:  $id, $ip, $site, $thepage, COUNTED_{$msg}1, real+1, botAs=$botAs, state=$state, jsin=$js, jsout=$js2, real=$dayreal, bots=$daybots, visits: $visits, time=" . (new DateTime)->format('H:i:s:v'));
}

if(empty($botAs)) {
  $botAs = BOTAS_COUNTED;
} elseif(!str_contains($botAs, BOTAS_COUNTED)) {
  $botAs .= "," . BOTAS_COUNTED;
}

// Now update tracker. $botAs should have BOTS_COUNTED!

$S->sql("update $S->masterdb.tracker set botAs='$botAs', endtime=now(), difftime=timestampdiff(second, starttime, now()), ".
          "isJavaScript='$java', lasttime=now() where id=$id");

if(!$S->isMyIp($ip) && $DEBUG2)
  error_log("beacon:  $id, $ip, $site, $thepage, {$msg}2, state=$state, botAs=$botAs, visits=$visits, jsin=$js, jsout=$js2, difftime=$difftime, time=" . (new DateTime)->format('H:i:s:v'));

if(!$S->isMyIp($ip) && $DEBUG3 && $type == 'visibilitychange')
  error_log("beacon:  $id, $ip, $site, $thepage, {$msg}3, state=$state, botAs=$botAs, visits=$visits, jsin=$js, jsout=$js2, difftime=$difftime, time=" . (new DateTime)->format('H:i:s:v'));
