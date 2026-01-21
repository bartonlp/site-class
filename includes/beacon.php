<?php
// Beacon from tracker.js
/*
CREATE TABLE `badplayer` (
  `primeid` int NOT NULL AUTO_INCREMENT,
  `id` int DEFAULT NULL,
  `ip` varchar(20) NOT NULL,
  `site` varchar(50) DEFAULT NULL,
  `page` varchar(255) DEFAULT NULL,
  `botAs` varchar(50) DEFAULT NULL,
  `botAsBits` int DEFAULT '0' COMMENT 'bitmap',
  `type` varchar(50) NOT NULL,
  `errno` varchar(100) DEFAULT NULL,
  `errmsg` varchar(255) NOT NULL,
  `agent` varchar(255) DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  `lasttime` datetime DEFAULT NULL,
  PRIMARY KEY (`primeid`)
) ENGINE=InnoDB AUTO_INCREMENT=2141 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
*/

define("BEACON_VERSION", "4.0.18beacon-pdo"); // BLP 2025-04-24 - removed $bExit and error from the tracker select.

// The normal beacon starts here.

$_site = require_once getenv("SITELOADNAME");
// If you want the version defined ONLY and no other information.
// If we have $__VERSION_ONLY, then just return the version info.

if($__VERSION_ONLY === true) {
  return BEACON_VERSION;
}

// Don't track or do geo!

$_site->noTrack = true;
$_site->noGeo = true;

$S = new Database($_site);
          
//$DEBUG = true; // if not me check visability
//$DEBUG_ISABOT = true;
//$DEBUG_ISABOT = true;

// The input comes via php as json data not $_GET or $_POST

$data = file_get_contents('php://input');

$data = json_decode($data, true);

$id = $data['id'];
$type = $data['type'];
$site = $data['site'];
$ip = $data['ip'];
$agent = $data['agent'];
$thepage = $data['thepage'];
$state = $data['state'];
$prevState = $data['prevState'];

// Here the isMeFalse is passed as a true bool.
$S->isMeFalse = $data['isMeFalse'];

$msg = strtoupper($type);

// If we do not have an $id thiss an error.

if(!is_numeric($id)) {
  if(!$msg) {
    $msg = "ID_NOT_NUMERIC";
    logInfo("beacon ID_NOT_NUMERIC: id=$id, line=". __LINE__);
  }
  
  $errno = "-200: No id";

  $S->sql("
insert into $S->masterdb.badplayer (ip, site, page, type, errno, errmsg, agent, created, lasttime)
values('$S->ip', '$site', '$S->self', '{$msg}_BEACON_GOAWAY', '$errno', '$msg', '$S->agent', now(), now())");

  $xip = $_SERVER['REMOTE_ADDR'];
  logInfo("beacon GO_AWAY NO_ID: id=$id,  xip=$xip, ip=$ip, site=$site, page=$thepage, ".
          "type=$type, state=$state, prevstate=$prevState, agent=$agent, line=" . __LINE__);
  
  echo "<h1>GO AWAY</h1><p>" . BEACON_VERSION . "</p>";
  exit();
}

// Now get botAsBits and isJavaScrip etc. from the tracker table.

if($S->sql("select botAsBits, isJavaScript, difftime, finger from $S->masterdb.tracker where id=$id")) {
  [$botAsBits, $js, $difftime, $finger] = $S->fetchrow('num'); 
} else {
  logInfo("beacon: NO record for $id, line=" . __LINE__);
}

// BLP 2023-08-08 - tracker.php does not do any 'exit' tracking any more!

switch($type) {
  case "pagehide":
    $beacon =  BEACON_PAGEHIDE;
    $botAsBits |= BOTS_PAGEHIDE;
    break;
  case "unload":
    $beacon =  BEACON_UNLOAD;
    $botAsBits |= BOTS_UNLOAD;
    break;
  case "beforeunload":
    $beacon =  BEACON_BEFOREUNLOAD;
    $botAsBits |= BOTS_BEFOREUNLOAD;
    break;
  case "visibilitychange":
    $beacon =  BEACON_VISIBILITYCHANGE;
    $botAsBits |= BOTS_VISIBILITYCHANGE;
    break;
  default:
    logInfo("beacon Error: id=$id, ip=$ip, state=$site, page=$thepage, SWITCH_ERROR_{$type}, botAsBits=$botAsBits, java=$js, ".
              "-- \$S->ip=$S->ip, line=". __LINE__);
    exit();
}

$jsin = $js;
$java = strtoupper(dechex($js));
$js |= $beacon;
$js2 = strtoupper(dechex($js));

if($DEBUG_IPS && ($ip != $S->ip)) {
  logInfo("beacon Warning: id=$id, ip=$ip, site=$site, page=$thepage, IP_MISMATCH_{$msg}, ".
          "\$ip != \$S->ip -- \$S->ip=$S->ip, botAsBits=$botAsBits, jsin=$java, jsout=$js2, line=". __LINE__);
}

$botAsBits |= BOTS_COUNTED;

// BLP 2025-02-26 - moved $S->sql below error messages.

$masked = dechex($js & (BEACON_PAGEHIDE | BEACON_UNLOAD | BEACON_BEFOREUNLOAD | BEACON_VISIBILITYCHANGE));

if(!$S->isMyIp($ip) && $DEBUG) {
  if($type == 'visibilitychange') {
    logInfo("beacon {$msg}2: id=$id, ip=$ip, site=$site, page=$thepage, state=$state, prevState=$prevState, ".
            "botAsBits=$botAsBits, jsin=$java, jsout=$js2, difftime=$difftime, line=" . __LINE__);
  } else {
    if(($js & (BEACON_PAGEHIDE | BEACON_UNLOAD | BEACON_BEFOREUNLOAD)) !== 0) {
      logInfo("beacon {$msg}3: id=$id, ip=$ip, site=$site, page=$thepage, state=$state, prevState=$prevState, ".
              "botAsBits=$botAsBits, jsin=$java, jsout=$js2, difftime=$difftime, line=" . __LINE__);
    }
  }
}

$botAsBits |= ($difftime ? BOTS_HAS_DIFFTIME : 0);

// Is this a bot without a difftime?

if($agent && $S->isBot($agent) && ($botAsBits & BOTS_HAS_DIFFTIME) === 0) {
  if($DEBUG_ISABOT) logInfo("beacon ISABOT_{$msg}1: id=$id, ip=$ip, state=$site, page=$thepage, state=$state, ".
                            "botAsBits=$botAsBits, jsin=$java, jsout=$js2, line=" . __LINE__);

  $botAsBits |= BOTS_BOT;
}

$S->sql("
update $S->masterdb.tracker set botAsBits=botAsBits|$botAsBits, endtime=now(), count=count+1,
difftime=timestampdiff(second, starttime, now()),
isJavaScript='$js' where id=$id");

// BLP 2025-04-11 - update bots3 table.

$S->updateBots3($ip, $agent, $thepage, $site, $botAsBits);
