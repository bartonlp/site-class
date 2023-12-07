<?php
// Track the various thing that happen. Some of this is done via JavaScript while others are by the
// header images and the csstest that is in the .htaccess file as a RewirteRule.
// NOTE: the $_site info is from a mysitemap.json that is where the tracker.php
// is located (or a directory above it) not necessarily from the mysitemap.json that lives with the
// target program.
// BLP 2023-08-11 - added logic below to allow me to keep the files tracker.php in
// https://bartonlp.com/otherpages/ with symlinks to the site-class library.
// SO we do not need a symlink in everypage directory!!!!
// BLP 2023-08-08 - Move the 'script' type to 'start'.
// BLP 2023-09-10 - add if($_POST) right after the require_once(). We need to get the host from the
// first $_site->dbinfo and replace the value form the file_get_contents().

/*
CREATE TABLE `tracker` (
  `id` int NOT NULL AUTO_INCREMENT,
  `botAs` varchar(30) DEFAULT NULL,
  `site` varchar(25) DEFAULT NULL,
  `page` varchar(255) NOT NULL DEFAULT '',
  `finger` varchar(50) DEFAULT NULL,
  `nogeo` tinyint(1) DEFAULT NULL,
  `ip` varchar(40) DEFAULT NULL,
  `agent` text,
  `referer` varchar(255) DEFAULT '',
  `starttime` datetime DEFAULT NULL,
  `endtime` datetime DEFAULT NULL,
  `difftime` varchar(20) DEFAULT NULL,
  `isJavaScript` int DEFAULT '0',
  `error` varchar(256) DEFAULT NULL,
  `lasttime` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `site` (`site`),
  KEY `ip` (`ip`),
  KEY `lasttime` (`lasttime`),
  KEY `starttime` (`starttime`)
) ENGINE=MyISAM AUTO_INCREMENT=6523425 DEFAULT CHARSET=utf8mb3;

CREATE TABLE `badplayer` (
  `ip` varchar(20) NOT NULL,
  `id` int DEFAULT NULL,
  `site` varchar(50) DEFAULT NULL,
  `page` varchar(255) DEFAULT NULL,
  `botAs` varchar(50) DEFAULT NULL,
  `type` varchar(50) NOT NULL,
  `count` int DEFAULT NULL,
  `errno` int DEFAULT NULL,
  `errmsg` varchar(255) DEFAULT NULL,
  `agent` varchar(255) DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  `lasttime` datetime DEFAULT NULL,
  PRIMARY KEY (`ip`,`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

// BLP 2022-12-06 - Added rcount and bcount

CREATE TABLE `dayrecords` (
  `fid` int DEFAULT NULL,
  `ip` varchar(20) DEFAULT NULL,
  `site` varchar(20) DEFAULT NULL,
  `page` varchar(255) DEFAULT NULL,
  `finger` varchar(20) DEFAULT NULL,
  `jsin` varchar(10) DEFAULT NULL,
  `jsout` varchar(20) DEFAULT NULL,
  `dayreal` int DEFAULT NULL,
  `rcount` int DEFAULT '0',
  `daybots` int DEFAULT NULL,
  `dayvisits` int DEFAULT NULL,
  `visits` smallint DEFAULT '0',
  `lasttime` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
*/

define("TRACKER_VERSION", "4.0.0tracker"); // BLP 2023-09-10 - 

// If you want the version defined ONLY and no other information.

if($_site || $__VERSION_ONLY === true) {
  return TRACKER_VERSION;
}

// If this is my home server try the autoload.php which should be off this directory

if($_SERVER['REMOTE_ADDR'] == '195.252.232.86') {
  error_log("*** tracker.php HP-Envy Server, use autoload.php");
  $_site = require_once("autoload.php"); // We are at ~/bartonphillips.org/site-class/includes.
  $_site->trackerLocationJs =  'https://bartonphillips.org/site-class/includes/tracker.js';
  $_site->trackerLocation = 'https://bartonphillips.org/site-class/includes/tracker.php';
  $_site->beaconLocation = 'https://bartonphillips.org/site-class/includes/beacon.php';
} else {
  $_site = require_once(getenv("SITELOADNAME"));
}

// BLP 2023-09-10 - If this is a POST from tracker.js via ajax get the $_site via a
// file_get_contents($_POST['mysitemap']) but use the host from $_site->dbinfo->host above. See
// SiteClass::getPageHead(), siteload.php. 

if($_POST) {
  // Here isMeFalse is a string '1'.
  if($_POST['isMeFalse']) $_site->isMeFalse = true;

  // BLP 2023-08-11 - This allow us to keep the tracker.php at bartonlp.com/otherpages with a
  // symlink to vendor/bartonlp/site-class/includes/tracker.php
  // There are two remote sites where I have to do a get_file_contentes(): HP-Envy and RPI.
  // Every thing on the server can use require.

  $mysite = $_POST['mysitemap'];

  $tmp = $_site->dbinfo->host; // BLP 2023-09-10 -

  unset($_site);

  if(str_contains($mysite, "bartonphillips.org")) {
    $port = null;
    if(str_contains($mysite, ":8000")) {
      $port = ":8000";
    }
    $mysite = preg_replace("~/var/www/(.*?)/~", "https://bartonphillips.org$port/", $mysite);
  }

  if($mysite == "mysitemap.json") $mysite = "https://bartonphillips.org/$mysite";
  
  $_site = json_decode(stripComments(file_get_contents($mysite)));

  $ip = $_SERVER['REMOTE_ADDR'];

  if($_site === null) {
    error_log("*** tracker.php: \$_site is NULL, ip=$ip");
    echo "ERROR \$_site is NULL";
    exit();
  }
  
  $_site->dbinfo->host = $tmp; // BLP 2023-09-10 -

  //if($ip != "195.252.232.86") error_log("*** tracker.php: mysite=$mysite, site=$_site->siteName, ip=$ip, host=$tmp");
}

$_site->noTrack = true; // Don't track or do geo!
$_site->noGeo = true;

$S = new Database($_site); // BLP 2023-10-02 - because we use Database noTrack is set and we do not do any tracking.
//error_log("*** tracker.php \$S=" . var_export($S, true));

//$DEBUG_START = true; // start
//$DEBUG_LOAD = true; // load
//$DEBUG_TIMER = true; // Timer
//$DEBUG_DAYCOUNT = true; // Timer real+1
//$DEBUG_MSG = true; // AjaxMsg
//$DEBUG_GET1 = true;
//$DEBUG_ISABOT = true; // This is in the 'timer' logic
//$DEBUG_ISABOT2 = true; // This is in the 'image' GET logic
$DEBUG_NOSCRIPT = true; // no script

// ****************************************************************
// All of the following are the result of a javascript interactionl
// ****************************************************************

// Post an ajax error message

if($_POST['page'] == 'ajaxmsg') {
  $msg = $_POST['msg'];
  $ip = $_POST['ip'];
  $site = $_POST['site'];
  $arg1 = $_POST['arg1'];
  $arg2 = $_POST['arg2'];

  if($arg1) {
    $args = ", $arg1";
  }
  if($arg2) {
    $args .= ", $arg2";
  }

  if(!$S->isMyIp($ip) && $DEBUG_MSG) error_log("tracker AJAXMSG $site, $ip: $msg{$args}");
  
  echo "AJAXMSG OK";
  exit();
}

// 'start' is an ajax call from tracker.js
// BLP 2023-08-08 - 'start' now implies the 'script' (TRACKER_SCRIPT goes away. see defines.php)

if($_POST['page'] == 'start') {
  $id = $_POST['id'];
  $site = $_POST['site'];
  $ip = $_POST['ip']; // This is the real ip of the program. $S->ip will be the ip of ME.
  $visits = $_POST['visits']; // Visits may be 1 or zero. tracker.js sets the mytime cookie.
  $thepage = $_POST['thepage'];
  $ref = $_POST['referer'];

  if(!$id) {
    error_log("tracker: $site, $ip: START NO ID");
    exit();
  }

  if($S->isMyIp($ip)) {
    $visits = 0;
  }

  if($S->sql("select botAs, isJavaScript, hex(isJavaScript) from $S->masterdb.tracker where id=$id")) {
    [$botAs, $java, $js] = $S->fetchrow('num');
  } else { // BLP 2023-02-10 - add for debug
    error_log("tracker: $id, $ip, $site, $thispage,  Select of id=$id failed, time=" . (new DateTime)->format('H:I:s:v'));
  }
  
  $java |= TRACKER_START; 
  $js2 = strtoupper(dechex($java));

  if(!$S->isMyIp($ip) && $DEBUG_START) {
    error_log("tracker: $id, $ip, $site, $thepage, START1, botAs=$botAs, referer=$ref, jsin=$js, jsout=$js2, time=" . (new DateTime)->format('H:i:s:v'));
  }

  if($ref) {
    $sql = "insert into $S->masterdb.tracker (id, botAs, site, page, ip, agent, referer, starttime, isJavaScript, lasttime) ".
           "values($id, '$botAs', '$site', '$thepage', '$ip', '$agent', '$ref', now(), '$java', now()) ".
           "on duplicate key update isJavaScript='$java', referer='$ref', lasttime=now()";
  } else {
    $sql = "insert into $S->masterdb.tracker (id, botAs, site, page, ip, agent, starttime, isJavaScript, lasttime) ".
           "values($id, '$botAs', '$site', '$thepage', '$ip', '$agent', now(), '$java', now()) ".
           "on duplicate key update isJavaScript='$java', lasttime=now()";
  }
  
  $S->sql($sql);
  echo "Start OK, java=$js";
  exit();
}

// load is an ajax call from tracker.js

if($_POST['page'] == 'load') {
  $id = $_POST['id'];
  $site = $_POST['site'];
  $ip = $_POST['ip'];
  $thepage = $_POST['thepage'];
  
  if(!$id) {
    error_log("tracker $site, $ip: LOAD NO ID");
    echo "Load Error: no id, exiting";
    exit();
  }

  if($S->sql("select botAs, isJavaScript, hex(isJavaScript) from $S->masterdb.tracker where id=$id")) {
    [$botAs, $java, $js] = $S->fetchrow('num');
  }

  $java |= TRACKER_LOAD;
  $js2 = strtoupper(dechex($java));

  if(!$S->isMyIp($ip) && $DEBUG_LOAD && strpos($botAs, BOTAS_COUNTED) === false)
    error_log("tracker: $id, $ip, $site, $thepage, LOAD2, botAs=$botAs, jsin=$js, jsout=$js2, time=" . (new DateTime)->format('H:i:s:v'));

  // BLP 2023-03-25 - This should maybe be insert/update?
  
  $S->sql("update $S->masterdb.tracker set isJavaScript='$java', lasttime=now() where id='$id'");
  echo "Load OK, java=$js";
  exit();
}

// ON EXIT FUNCTION
// NOTE: There will be very few clients that do not support beacon. Only very old versions of
// browsers and of course MS-Ie. Therefore these should not happen often.
// BLP 2022-10-27 - I have not seen one in several months so I am removing this logic and replacing
// it with an error message!
// BLP 2023-10-06 - Tor does not support 'beacon' so Tor comes here.

if($_POST['page'] == 'onexit') {
  $id = $_POST['id'];
  $site = $_POST['site'];
  $ip = $_POST['ip'];
  $thepage = $_POST['thepage'];
  $type = $_POST['type'];

  if($S->sql("select botAs, isJavaScript, difftime, agent from $S->masterdb.tracker where id=$id")) {
    [$botAs, $java, $difftime, $agent] = $S->fetchrow('num');
  } else {
    error_log("tracker onexit: NO record for $id, line=" . __LINE__);
  }

  $msg = strtoupper($type);

  // BLP 2023-10-06 - use BEACON values.
  
  switch($type) {
    case "pagehide":
      $onexit = BEACON_PAGEHIDE;
      break;
    case "unload":
      $onexit = BEACON_UNLOAD;
      break;
    case "beforeunload":
      $onexit = BEACON_BEFOREUNLOAD;
      break;
    case "visibilitychange":
      $onexit = BEACON_VISIBILITYCHANGE;
      break;
    default:
      error_log("tracker: $id, $ip, $site, $thepage, SWITCH_ERROR_{$type}, botAs=$botAs -- \$S->ip=$S->ip, \$S->agent=$S->agent");
      exit();
  }

  $botAs = empty($botAs) ? "Tor?" : "$botAs,Tor?";
  $java |= $onexit;
  
  $S->sql("update $S->masterdb.tracker set botAs='$botAs', endtime=now(), difftime=timestampdiff(second, starttime, now()), ".
            "isJavaScript='$java', lasttime=now() where id=$id");

  error_log("tracker onexit: $id, $site, $ip, $thepage, $msg, $botAs, Maybe Tor?");
  exit();
}
// END OF EXIT FUNCTIONS

// 'timer' is an ajax call from tracker.js
// TIMER. This runs while the page is up.

if($_POST['page'] == 'timer') {
  $id = $_POST['id'];
  $site = $_POST['site'];
  $ip = $_POST['ip'];
  $visits = $_POST['visits'];
  $thepage = $_POST['thepage'];

  if(!$id) {
    error_log("tracker: $site, $ip: TIMER NO ID");
    echo "Timer Error: no id, exiting";
    exit();
  }

  if(!$S->sql("select botAs, isJavaScript, hex(isJavaScript), finger, agent from $S->masterdb.tracker where id=$id")) {
    error_log("*** tracker.php: No record for id=$id, $site, $thispage");
  }

  [$botAs, $java, $js, $finger, $agent] = $S->fetchrow('num');

  $java |= TRACKER_TIMER; // Or in TIMER
  $js2 = strtoupper(dechex($java));

  if(!empty($agent)) {
    if($S->isBot($agent)) {
      if($DEBUG_ISABOT) error_log("tracker: $id, $ip, $site, $thepage, ISABOT_TIMER1, botAs=$botAs, visits: $visits, jsin=$js, jsout=$js2, time=" . (new DateTime)->format('H:i:s:v'));
      echo "Timer1: This is a BOT, $id, $ip, $site, $thepage";
      exit(); // If this is a bot don't bother
    }
  } else {
    error_log("tracker, timer: $id, $ip, $site, $thepage, java=$js2, 'EMPTY_AGENT', botAs=$botAs");
  }

  // $botAs could have any or all of these: counted as well as  match, robot, sitemap or zbot
  
  if(!str_contains($botAs, BOTAS_COUNTED)) {
    // Does not contain BOTAS_COUNTED
    
    if(!empty($botAs)) {
      // This must have match, no-agent, robot, sitemap, or zbot

      if($DEBUG_ISABOT) error_log("tracker: $id, $ip, $site, $thepage, ISABOT_TIMER2, state=$state, botAs=$botAs, visits=$visits, jsin=$js, jsout=$js2, difftime=$difftime, time=" . (new DateTime)->format('H:i:s:v'));

      echo "Timer2 This is a BOT, $id, $ip, $site, $thepage, $botAs";
      exit();
    }
    // $botAs must be blank so set it to 'counted'.
    
    $botAs = BOTAS_COUNTED;
  }

  // If we get here we know that $botAs could have counted from above, but it may still have the other values.
    
  if(!$S->isMyIp($ip) && !str_contains($botAs, BOTAS_COUNTED)) {
    // It is not me, and $botAs does not conatin counted. It may have any of the other values.
    
    try {
      $S->sql("select `real`, bots, visits from $S->masterdb.daycounts where date=current_date() and site='$site'");
      [$dayreal, $daybots, $dayvisits] = $S->fetchrow('num');
      $dayreal++;
      $dayvisits += $visits;
      $daybots = empty($daybots) ? 0 : $daybots; // BLP 2023-01-07 -
      
      $sql = "update $S->masterdb.daycounts set `real`='$dayreal', bots='$daybots', visits='$dayvisits' where date=current_date() and site='$site'";
      $S->sql($sql);
      if($DEBUG_DAYCOUNT) error_log("tracker: $id, $ip, $site, $thepage, COUNTED_TIMER, real+1, visits=$visits, jsin=$js, jsout=$js2, real=$dayreal, bots=$daybots, time=" . (new DateTime)->format('H:i:s:v'));

      // BLP 2022-12-06 - Added rcount and bcount
      
      $S->sql("insert into $S->masterdb.dayrecords (fid, ip, site, page, finger, jsin, jsout, dayreal, rcount, daybots, dayvisits, visits, lasttime) ".
                "values($id, '$ip', '$site', '$thepage', '$finger', '$js', '$js2', '$dayreal', 1, '$daybots', '$dayvisits', '$visits', now()) ".
                "on duplicate key update finger='$finger', dayreal='$dayreal', rcount=rcount+1, daybots='$daybots', ".
                "dayvisits='$dayvisits', visits='$visits', lasttime=now()");
    } catch(Exception $e) {
      error_log("tracker Exception ($e) update or insert daycounts: real=$dayreal, bots=$daybots, visits=$dayvisits, site=$site, sql=$sql");
    }      
  }

  // BLP 2022-12-06 - now $botAs may have counted as well as maybe all of the other values.
  // Look again to see if we have counted.

  if(!str_contains($botAs, BOTAS_COUNTED)) {
    // It does not have counted so add it to the start

    $botAs = BOTAS_COUNTED . "," . $botAs;
  }
  
  $sql = "update $S->masterdb.tracker set botAs='$botAs', isJavaScript='$java', endtime=now(), ".
         "difftime=timestampdiff(second, starttime, now()), lasttime=now() where id=$id";

  $S->sql($sql);

  if(!$S->isMyIp($ip) && $DEBUG_TIMER) error_log("tracker: $id, $ip, $site, $thepage, TIMER2, botAs=$botAs, visits: $visits, jsin=$js, jsout=$js2, time=" . (new DateTime)->format('H:i:s:v'));

  echo "Timer OK, visits: $visits, java=$js, finger=$finger";
  exit();
}

// *********************************************
// This is the END of the javascript AJAX calls.
// *********************************************

// START OF IMAGE and CSSTEST FUNCTIONS These are NOT javascript but rather use $_GET.
// NOTE: The image functions are GET calls from the original php file.
//       THESE ARE NOT DONE BY tracker.js!

// Here is an example of the banner.i.php:
// <header>
//   <!-- bartonphillips.com/includes/banner.i.php -->
//   <a href="$h->logoAnchor">
//    <!-- The logo line is changed by tracker.js -->
// $image2
// $mainTitle
// <noscript>
// <p style='color: red; background-color: #FFE4E1; padding: 10px'>
// $image3
// Your browser either does not support <b>JavaScripts</b> or you have JavaScripts disabled, in either case your browsing
// experience will be significantly impaired. If your browser supports JavaScripts but you have it disabled consider enabaling
// JavaScripts conditionally if your browser supports that. Sorry for the inconvienence.</p>
// </noscript>
// </header>
//
// $image2 and $image3 look like this:
// <img id={headerImg2 or noscript} alt={headerImg2, noscriptImg}
// src='{the url for tracker.php}?page={normal or noscript}&id={$lastId}&image={image url}'> 
//
// In the 'ready' function tracker.js creates:
// <picture id='logo'><source srcset={phoneImg} media='((hover: none) and
// (pointer: course))' alt='photoImage'><img src={desktopImg} alt='desktopImage'></picture>
//
// The {phoneImg} and {disktopImg} are the two javascript variables.
//
// When tracker.php is called to get the image, 'page' has the values 'normal', 'noscript' or
// 'csstest'.
// 'csstest' happens via .htaccess REWRITERULE. See .htaccess for more details.

if($type = $_GET['page']) {
  $msg = strtoupper($type);
  
  $id = $_GET['id'];
  $image = $_GET['image'];

  if(!$id) {
    error_log("tracker: type=$msg, NO ID, $S->siteName, $S->ip, $S->agent");
    exit();
  }

  if(!is_numeric($id)) {
    $errno = -99;
    $errmsg = "ID is not numeric: $id";

    // No id, and ip, site, thepage, and agent are not yet valid. Use $S->...
    
    $sql = "insert into $S->masterdb.badplayer (ip, site, page, type, count, errno, errmsg, agent, created, lasttime) ".
           "values('$S->ip', '$S->siteName', '$S->self', '$msg', 1, '$errno', '$errmsg', '$S->agent', now(), now()) ".
           "on duplicate key update errmsg='UPDATE_ID_NOT_NUMERIC::$errmsg', count=count+1, lasttime=now()";

    error_log("tracker ID_IS_NOT_NUMERIC: site=$S->siteName, ip=$S->ip, id(value)=$id, agent=$S->agent");

    $S->sql($sql);
    goto GOAWAYNOW;
  }
  
  switch($type) {
    case "normal":
      $or = TRACKER_NORMAL;
      break;
    case "noscript":
      $or = TRACKER_NOSCRIPT;
      break;
    case "csstest":
      $or = TRACKER_CSS;
      $image = $image ?? "NONE";
      break;
    default:
      error_log("tracker Switch Error: $S->ip, $S->siteName, type=$type, time=" . (new DateTime)->format('H:i:s:v'));
      goto GOAWAY;
  }

  try {
    $sql = "select site, ip, page, finger, hex(isJavaScript), agent from $S->masterdb.tracker where id=$id";
    
    if($S->sql($sql)) {
      [$site, $ip, $thepage, $finger, $js, $agent] = $S->fetchrow('num');
    } else {
      throw new Exception("tracker: NO RECORD for id=$id, type=$msg", -100); // This will be caught below.
    }
  } catch(Exception $e) { // catches the throw above.
    // At this point $site, $ip, $thepage and $agent are NOT VALID.
    
    $errno = $e->getCode();
    $errmsg = $e->getMessage();

    // try to insert into the id that was passed in.

    try {
      $ip = $ip ?? "NO_IP";
      
      $sql = "insert into $S->masterdb.tracker (id, ip, error, starttime, lasttime) ".
             "values($id, '$S->ip', 'Select failed insert on $ip $msg', now(), now()) ".
             "on duplicate key update error='Update, Select failed $ip $msg', lasttime=now()";
      
      $S->sql($sql);

      error_log("tracker: $id, \$S->ip=$S->ip, \$S->siteName=$S->siteName,  $S->self, SELECT_FAILED_INSERT_OK_{$ip}_{$msg}, ".
                "err=$errno, errmsg=$errmsg, time=" . (new DateTime)->format('H:i:s:v'));
    } catch(Exception $e) {
      $errno = $e->getCode();
      $errmsg .= "::" . $e->getMessage();

      // ADD an entry to badplayer and mark it with BOTAS_COUNTED.
      // The primary key is ip and type

      $sql = "insert into $S->masterdb.badplayer (ip, id, site, page, type, count, errno, errmsg, agent, created, lasttime) ".
             "values('$S->ip', $id, '$S->self', '$msg', 1, '$errno', '$errmsg', '$S->agent', now(), now()) ".
             "on duplicate key update count=count+1, errmsg=errmsg . '::$errmsg', lasttime=now()";
        
      if(!$S->sql($sql)) {
        error_log("tracker: \$S->ip=$S->ip, \$S->siteName=$S->siteName, \$ip=$ip badplayer - could not do insert/update");
      }       
      goto GOAWAYNOW;
    }
  }

  // If we get here the $ip, $site, $thepage and $agent are all valid.

  if($DEBUG_GET1)
     error_log("tracker: $id, $ip, $site, $thepage, $msg, java=$js, time=" . (new DateTime)->format('H:i:s:v'));
  
  
  $java = hexdec($js);
  
  if(empty($agent) || $S->isBot($agent)) {
    if($DEBUG_ISABOT2) error_log("tracker: $id, $ip, $site, $thepage, ISABOT_{$msg}, agent=$agent, image=$image, time=" . (new DateTime)->format('H:i:s:v'));

    // We know that there is an ID but is there a record with that ID?

    $S->sql("insert into $S->masterdb.tracker (id, ip, site, page, agent, isJavaScript, error, starttime, lasttime) ".
                "values($id, '$ip', '$site', '$thepage', '$agent', '$java', 'ISABOT_$msg', now(), now()) ".
                "on duplicate key update error='ISABOT_UPDATE_$msg', lasttime=now()");

    // BLP 2023-01-18 - If this is a bot change the image.
    //$image = "/images/bot.jpg"; // Image of a bad bot!
  }
  
  if($DEBUG_GET2) error_log("tracker: $id, $ip, $site, $thepage, $msg -- referer=$ref");

  if($ref) {
    $sql = "insert into $S->masterdb.tracker (id, ip, site, page, agent, referer, isJavaScript, error, starttime, lasttime) ".
           "values($id, '$ip', '$site', '$thepage', '$agent', {$referer1}'$java', 'NO_UPDATE_$msg', now(), now()) ".
           "on duplicate key update isJavaScript=isJavaScript|$or, {$referer2}lasttime=now()";
  } else {
    $sql = "insert into $S->masterdb.tracker (id, ip, site, page, agent, isJavaScript, error, starttime, lasttime) ".
           "values($id, '$ip', '$site', '$thepage', '$agent', '$java', 'NO_UPDATE_$msg', now(), now()) ".
           "on duplicate key update isJavaScript=isJavaScript|$or, lasttime=now()";
  }

  $S->sql($sql);
  
  // If this is csstest we are done.
  
  if($type == "csstest") {
    header("Content-Type: text/css");
    echo "/* csstest.css */";
    exit();
  }

  $img = $S->defaultImage ?? "https://bartonphillips.net/images/blank.png";

  // script and normal may have an image but
  // noscript has NO IMAGE

  if($image) {
    $pos = strpos($image, "http"); // does $image start with http?
    if($pos !== false && $pos == 0) { 
      $img = $image; // $image has the full url starting with http (could be https)
    } else {
      // BLP 2023-08-09 - If we don't have an full url then force this to be from
      // bartonphillips.net. We can't use $S->imageLocation.
      
      $img = "https://bartonphillips.net$image";
    }
  }
    
  $imageType = pathinfo($img, PATHINFO_EXTENSION); //preg_replace("~.*\.(.*)$~", "$1", $img);

  $imgFinal = file_get_contents($img);

  if($imageType == 'svg') $imageType = "image/svg+xml";

  header("Content-Type: $imageType");

  $java |= $or;
  if($msg == "NOSCRIPT" && $DEBUG_NOSCRIPT) error_log("tracker: $id, $ip, $site, $thepage, $msg, java=$java, time=" . (new DateTime)->format('H:i:s:v'));

  echo $imgFinal;
  exit();
}
// END OF GET LOGIC

// Go Away logic

GOAWAY: // Label for goto.

$id = $_GET['id'] ?? $_POST['id'];

if(!$id) {
  $errno = -102;
  $errmsg = "No tracker logic triggered";
  
  // No id
  
  $S->sql("insert into $S->masterdb.badplayer (ip, site, page, type, count, errno, errmsg, agent, created, lasttime) " .
            "values('$S->ip', '$S->siteName', '$S->self', 'GOAWAY NO ID', 1, '$errno', '$errmsg', '$S->agent', now(), now()) ".
            "on duplicate key update count=count+1, lasttime=now()");
} else {
  // If this ID is not in the table add it with TRACKER_GOAWAY.
  
  $S->sql("insert into $S->masterdb.tracker (id, site, ip, agent, isJavaScript, starttime, lasttime) ".
            "values($id, '$S->ip', '$S->siteName', '$S->agent', " . TRACKER_GOAWAY . ", now(), now()) ".
            "on duplicate key update isJavaScript=isJavaScript|" . TRACKER_GOAWAY . ", lasttime=now()");

  $errno = -103;
  $errmsg = "No tracker logic triggered";
  $botAs = BOTAS_COUNTED;
  
  $S->sql("insert into $S->masterdb.badplayer (ip, id, site, page, type, count, errno, errmsg, agent, created, lasttime) " .
            "values('$S->ip', $id ,'$S->siteName', '$S->self', 'GOAWAY', 1, '$errno', '$errmsg', '$S->agent', now(), now()) ".
            "on duplicate key update count=count+1, lasttime=now()");
}

// otherwise just go away!

if($id) {
  $sql = "select finger from tracker where id=$id";
  $S->sql($sql);
  $finger = $S->fetchrow('num')[0] ?? "NONE";
}
$request = $_REQUEST ? ", \$_REQUEST: " . print_r($_REQUEST, true) : '';
$id = $id ?? "NO_ID";

GOAWAYNOW:

error_log("tracker: $id, $S->ip, $S->siteName, $S->self, GOAWAY, \$S->agent=$S->agent, agent=$agent finger=$finger{$request}");

$version = TRACKER_VERSION;

echo <<<EOF
<!DOCTYPE html>
<html>
<head>
<title>Go Away</title>
<meta name='robots' content='noindex'>
</head>
<body>
<h1>NOT AUTHORIZED</h1>
<h2>Please Do Not Index</h2>
<p>Please look at the <i>robots.txt</i> or the <i>Sitemap.xml</i> files.</p>
<p>$version</p>
</body>
</html>
EOF;
