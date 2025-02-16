<?php
// Beacon from tracker.js

/* BLP 2024-12-31 - New errno values as a string.
   -200: No type
*/

define("BEACON_VERSION", "4.0.7beacon-pdo"); // BLP 2025-02-16 - $DEBUG2&3 true. Changed error_log messages and improved messages.

// BLP 2023-01-30 - If you want the version defined ONLY and no other information.
// If we have a valid $_site or the $__VERSION_ONLY, then just return the version info.

if($__VERSION_ONLY === true) {
  return BEACON_VERSION;
}

// The normal beacon starts here.

$_site = require_once(getenv("SITELOADNAME"));

$_site->noTrack = true;
$_site->noGeo = true;

$S = new Database($_site);
          
//$DEBUG1 = true; // COUNTED real+1 bots-1
//$DEBUG2 = true; // visibilitychange
$DEBUG3 = true; // all types
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

// Here the isMeFalse is passed as a true bool.
$S->isMeFalse = $data['isMeFalse'];

$msg = strtoupper($type);

// If we do not have an $id or $visits that is an error.

if(!is_numeric($id) || $visits === null) {
  if(!$msg) $msg = "NO_TYPE&NO_VISITS";

  $errno = "-200: No type";

  error_log("beacon: NO ID, $ip, $site, $msg -- visits=$visits, \$S->ip=$S->ip, \$S->self=$S->self, \$S->agent=$S->agent, time=" . (new DateTime)->format('H:i:s:v'));
  
  $S->sql("insert into $S->masterdb.badplayer (ip, site, page, botAs, type, count, errno, errmsg, agent, created, lasttime) " .
            "values('$S->ip', '$site', '$S->self', 'counted', '{$msg}_BEACON_GOAWAY', 1, '$errno', 'NO ID Go away', '$S->agent', now(), now()) ".
            "on duplicate key update count=count+1, lasttime=now()");

  error_log("beacon GO_AWAY: id=NO_ID, ip=$ip, site=$site, page=$thepage, type=$type, state=$state, line=" . __LINE__);
  
  echo "<h1>GO AWAY</h1><p>" . BEACON_VERSION . "</p>";
  exit();
}

// Now get botAs and isJavaScrip etc. from the tracker table.
// BLP 2024-12-17 - remove $java and hex(isJavaScript).

if($S->sql("select botAs, isJavaScript, difftime, finger, agent from $S->masterdb.tracker where id=$id")) {
  [$botAs, $js, $difftime, $finger, $agent] = $S->fetchrow('num');
  $difftime /= 1000; // seconds
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
    error_log("beacon: $id, $ip, $site, $thepage, SWITCH_ERROR_{$type}, botAs=$botAs, java=$js, visits=$visits -- \$S->ip=$S->ip, line=". __LINE__);
    exit();
}

$jsin = $js;
$java = strtoupper(dechex($js));
$js |= $beacon;
$js2 = strtoupper(dechex($js));

if($DEBUG_IPS && ($ip != $S->ip)) {
  error_log("beacon: $id, $ip, $site, $thepage, IP_MISMATCH_{$msg}, \$ip != \$S->ip -- \$S->ip=$S->ip, botAs=$botAs, jsin=$java, jsout=$js2, visits=$visits, line=". __LINE__);
}

// Is this a bot?

if($agent && $S->isBot($agent)) {
  if($DEBUG_ISABOT) error_log("beacon ISABOT_{$msg}1: $id, $ip, $site, $thepage, state=$state, botAs=$botAs, visits=$visits, jsin=$java, jsout=$js2, line=" . __LINE__);
  //exit(); // If this is a bot don't bother
}

if(!str_contains($botAs, BOTAS_COUNTED)) {
  // Does not contain BOTAS_COUNTED
  
  if(!empty($botAs)) {
    // This must have robot, sitemap, or zero
    
    if($DEBUG_ISABOT) error_log("beacon ISABOT_{$msg}2: id=$id, ip=$ip, site=$site, page=$thepage, state=$state, botAs=$botAs, visits=$visits, jsin=$java, jsout=$js2, difftime=$difftime, line=" . __LINE__);
    exit();
  }
}

if(empty($botAs)) {
  $botAs = BOTAS_COUNTED;
} elseif(!str_contains($botAs, BOTAS_COUNTED)) {
  $botAs .= "," . BOTAS_COUNTED;
}

// Now update tracker. $botAs should have BOTS_COUNTED!

$S->sql("update $S->masterdb.tracker set botAs='$botAs', endtime=now(), difftime=timestampdiff(second, starttime, now()), ".
          "isJavaScript='$js', lasttime=now() where id=$id");

//error_log("beacon TEST: id=$id, ip=$ip, site=$site, page=$thepage, jsin=" . dechex($jsin));

if(!$S->isMyIp($ip) && $DEBUG2 && $type == 'visibilitychange') {
  if(($jsin & BEACON_VISIBILITYCHANGE) === 0) 
    error_log("beacon {$msg}2: id=$id, ip=$ip, site=$site, page=$thepage, state=$state, botAs=$botAs, visits=$visits, jsin=$java, jsout=$js2, difftime=$difftime, line=" . __LINE__);
}

if(!$S->isMyIp($ip) && $DEBUG3) {
  if(($jsin & (BEACON_PAGEHIDE | BEACON_UNLOAD | BEACON_BEFOREUNLOAD | BEACON_VISIBILITYCHANGE)) === 0 && $type != "visibilitychange") {
    error_log("beacon {$msg}3: id=$id, ip=$ip, site=$site, page=$thepage, state=$state, botAs=$botAs, visits=$visits, jsin=$java, jsout=$js2, difftime=$difftime, line=" . __LINE__);
  }
}
