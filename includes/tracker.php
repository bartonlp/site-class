<?php
// Track the various thing that happen. Some of this is done via JavaScript while others are by the
// header images and the csstest that is in the .htaccess file as a RewirteRule.
// NOE: the $_site info is from a mysitemap.json that is where the tracker.php
// is located (or a directory above it) not necessarily from the mysitemap.json that lives with the
// target program.
// BLP 2022-08-15 - I have moved tracker.php, tracker.js and beacon.php into the SiteClass
// directory. I use symlinks to let them continue to live in my CookieLess server.

// BLP 2022-08-15 - Check for the examples directory. If we find it then get siteload.php form the
// location where tracker.php is. siteload.php will look for a mysitemap.json file up from where it
// lives. NOTE the mysitemap.json for the examples lives at 'site-class' because examples and
// includes are at the same level and a mysitemap.json in the examples directory would never be
// found!
/*
CREATE TABLE `badplayer` (
  `ip` varchar(20) NOT NULL,
  `botAs` varchar(50) DEFAULT NULL,
  `type` varchar(50) NOT NULL,
  `count` int DEFAULT NULL,
  `errno` int DEFAULT NULL,
  `errmsg` varchar(255) DEFAULT NULL,
  `agent` varchar(255) DEFAULT NULL,
  `lasttime` datetime DEFAULT NULL,
  PRIMARY KEY (`ip`,`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
*/

$_site = require_once(getenv("SITELOADNAME"));

$_site->count = false; // Don't count this.

$S = new Database($_site);

require_once(SITECLASS_DIR . "/defines.php"); // constants for TRACKER, BOTS, BEACON.

//$DEBUG_START = true; // start
//$DEBUG_LOAD = true; // load
$DEBUG2 = true; // AJAX: pagehide, beforeunload, unload
$DEBUG3 = true; // AJAX: 'not done' pagehide, beforeunload, unload
//$DEBUG4 = true; // GET: script, normal, noscript
//$DEBUG5 = true; // Timer
//$DEBUG6 = true; // RewriteRule: csstest
//$DEBUG7 = true; // pagehide, beforeunload, unload real+1
//$DEBUG10 = true; // ref info
//$DEBUG11 = true; // Timer real+1
//$DEBUG_MSG = true; // AjaxMsg
//$DEBUG_GET1 = true;
//$DEBUG_ISABOT = true;

// ****************************************************************
// All of the following are the result of a javascript interactionl
// ****************************************************************

if($_POST) {
  // Here isMeFalse is a string '1'.
  if($_POST['isMeFalse']) $S->isMeFalse = true;
}

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

// start is an ajax call from tracker.js

if($_POST['page'] == 'start') {
  $id = $_POST['id'];
  $site = $_POST['site'];
  $ip = $_POST['ip']; // This is the real ip of the program. $S->ip will be the ip of ME.
  $visits = $_POST['visits']; // Visits may be 1 or zero. tracker.js sets the mytime cookie.
  $thepage = $_POST['thepage'];
  $ref = $_POST['referer'];

  if(!$id) {
    error_log("tracker $site, $ip: START NO ID");
    exit();
  }

  if($S->isMyIp($ip)) {
    $visits = 0;
  }

  if($S->query("select botAs, isJavaScript, hex(isJavaScript) from $S->masterdb.tracker where id=$id")) {
    [$botAs, $java, $js] = $S->fetchrow('num');
  }

  $java |= TRACKER_START; 
  $js2 = strtoupper(dechex($java));

  if(!$S->isMyIp($ip) && $DEBUG_START) error_log("tracker: $id, $ip, $site, $thepage, START1, botAs=$botAs, jsin=$js, jsout=$js2, time=" . (new DateTime)->format('H:i:s:v'));
  
  $S->query("update $S->masterdb.tracker set isJavaScript=$java, lasttime=now() where id='$id'");
  echo "Start OK, visits: $visits, java=$js";
  exit();
}

// load is an ajax call from tracker.js

if($_POST['page'] == 'load') {
  $id = $_POST['id'];
  $site = $_POST['site'];
  $ip = $_POST['ip'];
  $visits = $_POST['visits'];
  $thepage = $_POST['thepage'];
  
  if(!$id) {
    error_log("tracker $site, $ip: LOAD NO ID");
    exit();
  }

  if($S->query("select botAs, isJavaScript, hex(isJavaScript) from $S->masterdb.tracker where id=$id")) {
    [$botAs, $java, $js] = $S->fetchrow('num');
  }

  $java |= TRACKER_LOAD;
  $js2 = strtoupper(dechex($java));

  if(!$S->isMyIp($ip) && $DEBUG_LOAD && strpos($botAs, BOTAS_COUNTED) === false) error_log("tracker: $id, $ip, $site, $thepage, LOAD2, botAs=$botAs, visits=$visits, jsin=$js, jsout=$js2, time=" . (new DateTime)->format('H:i:s:v'));

  $S->query("update $S->masterdb.tracker set isJavaScript=$java, lasttime=now() where id='$id'");
  echo "Load OK, visits: $visits, java=$js";
  exit();
}

// ON EXIT FUNCTION
// NOTE: There will be very few clients that do not support beacon. Only very old versions of
// browsers and of course MS-Ie. Therefore these should not happen often.

if($_POST['page'] == 'onexit') {
  $id = $_POST['id'];
  $site = $_POST['site'];
  $ip = $_POST['ip'];
  $visits = $_POST['visits'];
  $thepage = $_POST['thepage'];
  $type = $_POST['type'];
  $state = $_POST['state'];
  
  if(!$id) {
    error_log("tracker $site, $ip: $type NO ID");
    exit();
  }

  $S->query("select botAs, isJavaScript, hex(isJavaScript), difftime, referer, finger from $S->masterdb.tracker where id=$id");
  [$botAs, $java, $js, $difftime, $referer, $finger] = $S->fetchrow('num');

  $msg = strtoupper($type);
  
  // NOTE: this check is really not necessary because if the client's browser supports beacon it is
  // unlikey (really imposible) that the browser would change its mind.
  
  if(($java & BEACON_MASK) == 0) { // Not handled by BEACON
    switch($type) {
      case "pagehide":
        $tracker = TRACKER_PAGEHIDE;
        break;
      case "unload":
        $tracker = TRACKER_UNLOAD;
        break;
      case "beforeunload":
        $tracker = TRACKER_BEFOREUNLOAD;
        break;
      case "visibilitychange":
        $tracker = TRACKER_VISIBILITYCHANGE;
        break;
      default:
        error_log("tracker: $id, $ip, $site, $thepage, SWITCH_ERROR_{$type}, java=$js, visits=$visits -- \$S->ip=$S->ip, \$S->agent=$S->agent");
        exit();
    }

    $java |= $tracker;
    $js2 = strtoupper(dechex($java));

    $msg = strtoupper($type);
    
    // Is this a bot?

    if($agent && $S->isBot($agent)) {
      if($DEBUG_ISABOT) error_log("tracker: $id, $ip, $site, $thepage, ISABOT_{$msg}, state=$state, botAs=$botAs, visits=$visits, jsin=$js, jsout=$js2, time=" . (new DateTime)->format('H:i:s:v'));
      exit(); // If this is a bot don't bother
    }

    if((str_contains($botAs, BOTAS_COUNTED) === false) && $botAs) { // Has not been counted yet but $botAs has a value.
      // I think this is a bot.
      echo "tracker:  $id, $ip, $site, $thepage, $msg IS A BOT=$botAs,  js=$js";
      if($DEBUG_ISABOT) error_log("tracker: $id, $ip, $site, $thepage, ISABOT_{$msg }, state=$state, botAs=$botAs, visits=$visits, jsin=$js, jsout=$js2, difftime=$difftime, time=" . (new DateTime)->format('H:i:s:v'));
      exit();
    }

    $botAs = BOTAS_COUNTED; // $botAs is either empty or has BOTAS_COUNTED. Force it to counted in case it is empty.

    if(!$S->isMyIp($ip)) {
      $S->query("select `real`, bots, visits from $S->masterdb.daycounts where date=current_date() and site='$site'");
      [$dayreal, $daybots, $dayvisits] = $S->fetchrow('num');
      $dayreal++;
      $dayvisits += $visits;

      if($DEBUG7) error_log("tracker: $id, $ip, $site, $thepage, COUNTED_{$msg}, state=$state, jsin=$js, jsout=$js2, real=$dayreal, bots=$daybots, real+1, visits: $visits, time=" . (new DateTime)->format('H:i:s:v'));

      $sql = "insert into $S->masterdb.dayrecords (fid, ip, site, page, finger, jsin, jsout, dayreal, daybots, dayvisits, visits, lasttime) ".
             "values($id, '$ip', '$site', '$thepage', '$finger', '$js', '$js2', $dayreal, $daybots, $dayvisits, $visits, now()) ".
             "on duplicate key update page='$thepage', finger='$finger', dayreal=$dayreal, daybots=$daybots, dayvisits=$dayvisits, visits=$visits, lasttime=now()";

      $S->query($sql);
    }

    $S->query("update $S->masterdb.tracker set page='$thepage', botAs='$botAs', endtime=now(), difftime=timestampdiff(second, starttime, now()), ".
              "isJavaScript=$java, lasttime=now() where id=$id");

    echo "tracker $type OK, dayreal=$dayreal, daybots: $daybots, dayvisits: $dayvisits java=$js2";
  } else {
    // This will only happen if the client somehow stops supporting beacon (and I can't imagin how
    // that could happen.
    
    if($DEBUG3) error_log("tracker DEBUG3 $type Not Done $id, $site, $ip -- thepage=$thepage, visits: $visits, java=$js, difftime=$difftime, time=" . (new DateTime)->format('H:i:s:v'));
    echo "tracker, pagehide Not Done, java=$js";
  }
  exit();
}
// END OF EXIT FUNCTIONS

// timer is an ajax call from tracker.js
// TIMER. This runs while the page is up.

if($_POST['page'] == 'timer') {
  $id = $_POST['id'];
  $site = $_POST['site'];
  $ip = $_POST['ip'];
  $visits = $_POST['visits'];
  $thepage = $_POST['thepage'];

  if(!$id) {
    error_log("tracker $site, $ip: TIMER NO ID");
    exit();
  }

  // If we have a TIMER then this is probably NOT a bot. So remove BOT if it's there.

  $S->query("select botAs, isJavaScript, hex(isJavaScript), finger, agent from $S->masterdb.tracker where id=$id");
  [$botAs, $java, $js, $finger, $agent] = $S->fetchrow('num');

  if($agent && $S->isBot($agent)) {
    if($DEBUG_ISABOT) error_log("tracker: $id, $ip, $site, $thepage, ISABOT_TIMER1, botAs=$botAs, visits: $visits, jsin=$js, jsout=$js2, time=" . (new DateTime)->format('H:i:s:v'));
    echo "Timer1 This is a BOT, $id, $ip, $site, $thepage";
    exit(); // If this is a bot don't bother
  }

  $java |= TRACKER_TIMER; // Or in TIMER
  $js2 = strtoupper(dechex($java));

  if(!str_contains($botAs, BOTAS_COUNTED) && !empty($botAs)) { // Has not been counted yet but $botAs has a value.
    // If $botAs has a value other than BOTAS_COUNTED then it must be robot, sitemap, zero or table.
    // I think this is a bot.
    if($DEBUG_ISABOT) error_log("tracker: $id, $ip, $site, $thepage, ISABOT_TIMER2, state=$state, botAs=$botAs, visits=$visits, jsin=$js, jsout=$js2, difftime=$difftime, time=" . (new DateTime)->format('H:i:s:v'));
    echo "Timer2 This is a BOT, $id, $ip, $site, $thepage";
    exit();
  }

  if(!$S->isMyIp($ip) && empty($botAs)) {
    $botAs = BOTAS_COUNTED;
    $S->query("select `real`, bots, visits from $S->masterdb.daycounts where date=current_date() and site='$site'");
    [$dayreal, $daybots, $dayvisits] = $S->fetchrow('num');
    $dayreal++;
    $dayvisits += $visits;

    $S->query("update $S->masterdb.daycounts set `real`=$dayreal, bots=$daybots, visits=$dayvisits where date=current_date() and site='$site'");

    if($DEBUG11) error_log("tracker: $id, $ip, $site, $thepage, COUNTED_TIMER, real+1, visits=$visits, jsin=$js, jsout=$js2, real=$dayreal, bots=$daybots, time=" . (new DateTime)->format('H:i:s:v'));

    $sql = "insert into $S->masterdb.dayrecords (fid, ip, site, page, finger, jsin, jsout, dayreal, daybots, dayvisits, visits, lasttime) ".
           "values($id, '$ip', '$site', '$thepage', '$finger', '$js', '$js2', $dayreal, $daybots, $dayvisits, $visits, now()) ".
           "on duplicate key update finger='$finger', dayreal=$dayreal, daybots=$daybots, dayvisits=$dayvisits, visits=$visits, lasttime=now()";

    $S->query($sql);
  }

  $sql = "update $S->masterdb.tracker set botAs='$botAs', isJavaScript=$java, endtime=now(), ".
         "difftime=timestampdiff(second, starttime, now()), lasttime=now() where id=$id";

  $S->query($sql);

  if(!$S->isMyIp($ip) && $DEBUG5) error_log("tracker: $id, $ip, $site, $thepage, TIMER2, botAs=$botAs, visits: $visits, jsin=$js, jsout=$js2, time=" . (new DateTime)->format('H:i:s:v'));

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
//   <a href="https://www.bartonphillips.com">
//    <img id='logo' data-image="image" src="https://bartonphillips.net/images/blp-image.png"></a>
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
// tracker.js changes the <img id='logo' ... from the above to 'src' attribute:
// src="https://bartonphillips.net/tracker.php?page=script&id="+lastId+"&image="+image);
// When tracker.php is called to get the image 'page' has the values script, normal or noscript.
//
// csstest happens via .htaccess REWRITERULE. See .htaccess for more details.

//error_log("tracker: site=$S->siteName, \$_GET: " . print_r($_GET, true));

if($type = $_GET['page']) {
  switch($type) {
    case "normal":
      $or = TRACKER_NORMAL;
      break;
    case "script":
      $or = TRACKER_SCRIPT;
      break;
    case "noscript":
      $or = TRACKER_NOSCRIPT;
      break;
    case "csstest":
      $or = TRACKER_CSS;
      $image = $image ?? "NONE";
      break;
    default:
      error_log("tracker Switch Error: $type, time=" . (new DateTime)->format('H:i:s:v'));
      goto GOAWAY;
  }

  $msg = strtoupper($type);
  
  $id = $_GET['id'];
  $image = $_GET['image'];
  
  if(!$id) {
    error_log("tracker $type: NO ID, $S->ip, $S->agent");
    exit();
  }

  if(!is_numeric($id)) {
    $errno = -99;
    $errmsg = "ID is not numeric";
    $botAs = BOTAS_COUNTED;
    
    $sql = "insert into $S->masterdb.badplayer (ip, botAs, type, count, errno, errmsg, agent, created, lasttime) ".
           "values('$S->ip', $botAs, '$msg', 1, '$errno', '$errmsg', '$S->agent', now(), now()) ".
           "on duplicate key update botAs='ID_IS_A_STRING', count=count+1, lasttime=now()";

    error_log("tracker ID_IS_A_STRING: ip=$S->ip, id(value)=$id, agent=$S->agent");

    $S->query($sql);
    goto GOAWAYNOW;
  }
  
  // I get people that call tracker 'exit' functions directly with nufarious stuff.
  
  $botAs = '';
  
  try {
    $sql = "select site, ip, page, hex(isJavaScript), agent, botAs from $S->masterdb.tracker where id=$id";
    
    if($S->query($sql)) {
      [$site, $ip, $thepage, $js, $agent, $botAs] = $S->fetchrow('num');
    } else {
      throw new Exception("NO RECORD for id=$id", -100); // This will be caught below.
    }
  } catch(Exception $e) { // catches the throw above.
    $errno = $e->getCode();
    $errmsg = $e->getMessage();

    /* for debug error_log("tracker: TEST1, $errno, $errmsg"); */
    
    $tmpBotAs = $botAs;
    $botAs = BOTAS_COUNTED;

    // try to insert into the id that was passed in.
    try {
      $ip = "NONE";

      $sql = "insert into $S->masterdb.tracker (id, ip, botAs, starttime, lasttime) values($id, '$ip', '$botAs', now(), now()) ".
             "on duplicate key update botAs='$botAs', lasttime=now()";
      
      $S->query($sql);

      error_log("tracker: $id, $ip, $site, $thepage, SELECT_FAILED_INSERT_OK_{$msg}, err=$errno, errmsg=$errmsg, time=" . (new DateTime)->format('H:i:s:v'));
    } catch(Exception $e) {
      $errno = $e->getCode();
      $errmsg .= "::" . $e->getMessage();

      /* for debug error_log("tracker: TEST2, $errno, $errmsg"); */
      
      // ADD an entry to badplayer and mark it with BOTAS_COUNTED.
      // The primary key is ip and type

      try {
        $sql = "insert into $S->masterdb.badplayer (ip, botAs, type, count, errno, errmsg, agent, created, lasttime) ".
               "values('$S->ip', '$botAs', '$msg', 1, '$errno', '$errmsg', '$S->agent', now(), now()) ".
               "on duplicate key update botAs='$botAs', count=count+1, lasttime=now()";
        
        if(!$S->query($sql)) {

          error_log("tracker: $S->ip, badplayer - could not do insert/update");
        } else {
          if(!str_contains($tmpBotAs, BOTAS_COUNTED)) error_log("tracker: $id, $S->ip, insert into badplayer OK: $botAs, $msg, errno=$errno, errmsg=$errmsg, sql=$sql");
        }
      } catch(Exception $e) {
        $errno = $e->getCode();
        $errmsg .= "::" . $e->getMessage();
        error_log("tracker: ip=$S->ip, insert/update badplayer failed: errno=$errno, errmsg=$errmsg, sql=$sql");
      }

GOAWAYNOW: // label for goto
      
      echo <<<EOF
<!doctype html>
<html>
<head>
<title>Go Away</title>
<style>
.blink {
 animation: blink 1s linear infinite;
}
@-webkit-keyframes blink {
 0% { opacity: 1; }
 50% { opacity: 1; }
 50.01% { opacity: 0; }
 100% { opacity: 0; }
}
@-moz-keyframes blink {
 0% { opacity: 1; }
 50% { opacity: 1; }
 50.01% { opacity: 0; }
 100% { opacity: 0; }
}
@-ms-keyframes blink {
 0% { opacity: 1; }
 50% { opacity: 1; }
 50.01% { opacity: 0; }
 100% { opacity: 0; }
}
@-o-keyframes blink {
 0% { opacity: 1; }
 50% { opacity: 1; }
 50.01% { opacity: 0; }
 100% { opacity: 0; }
}
@keyframes blink {
 0% { opacity: 1; }
 50% { opacity: 1; }
 50.01% { opacity: 0; }
 100% { opacity: 0; }
}
.goaway {
font-size: 100px; color: red;
}
</style>
</head>
<body>
<h1>NOT AUTHORIZED</h1>
<h2 class='goaway blink'>Please, please just Go Away!<h2>
</body>
</html>
EOF;
      exit();
    }
  }
    
  if($DEBUG_GET1) error_log("tracker: $id, $ip, $site, $thepage, $msg, java=$js, time=" . (new DateTime)->format('H:i:s:v'));

  $java = hexdec($js);

  if($agent && $S->isBot($agent)) {
    if(!str_contains($botAs, BOTAS_COUNTED)) {
      if($botAs) {
        $botAs = BOTAS_COUNTED . ",$botAs";
      } else {
        $botAs = BOTAS_COUNTED;
      }
      // This can't be me.
      
      if($DEBUG_ISABOT) error_log("tracker: $id, $ip, $site, $thepage, ISABOT_{$msg}, image=$image, time=" . (new DateTime)->format('H:i:s:v'));

      // We know that there is an ID but is the a record with that ID?
      
      if(!$S->query("update $S->masterdb.tracker set botAs='$botAs', lasttime=now() where id=$id")) {
        // We did not find a record. Create a record

        error_log("tracker: $id, $S->ip, $S->siteName, $S->self, ISABOT_NO_UPDATE_{$msg}, id not valid no update posible, time" . (new DateTime)->format('H:i:s:v'));

        try {
          $S->query("insert into $S->masterdb.tracker (id, ip, site, page, agent, botAs, isJavaScript, finger, starttime, lasttime) ".
                    "values($id, '$S->ip', '$S->siteName', '$S->self', '$S->agent', '$botAs', $java, 'ISABOT_NO_UPDATE_{$msg}', now(), now())");

          error_log("tracker: $id, $S->ip, '$S->siteName', '$S->self', ISABOT_INSERT_AFTER_NO_UPDATE_{$msg}, time" . (new DateTime)->format('H:i:s:v'));
        } catch (Exception $e) {
          $errno = $e->getCode();
          $errmsg = $e->getMessage();

          /* for debug */error_log("tracker: TEST3, $errno, $errmsg");
          
          error_log("tracker: $id, $S->ip $S->siteName, $S->self, ISABOT_INSERT_FAILED_{$msg}, unable to do insert, try badplayer, errno=$errno, errmsg=$errmsg");

          $S->query("insert into $S->masterdb.badplayer (ip, botAs, type, count, errno, errmsg, agent, created, lasttime) " .
                    "values('$S->ip', '$botAs', 'ISABOT_INSERT_FAILED_{$msg}', 1, '$errno', '$errmsg', '$S->agent', now(), now()) ".
                    "on duplicate key update botAs='$botAs', count=count+1, lasttime=now()");
        }
      }
    }
    exit(); // If this is a bot don't bother
  }
  
  if($DEBUG_GET2) error_log("tracker: $id, $ip, $site, $thepage, $msg -- referer=$ref");
  
  if(!$S->query("update $S->masterdb.tracker set isJavaScript=isJavaScript|$or, lasttime=now() where id=$id")) {
    $S->query("insert into $S->masterdb.tracker (id, ip, site, page, agent, botAs, isJavaScript, finger, starttime, lasttime) ".
              "values($id, '$S->ip', '$S->siteName', '$S->self', '$S->agent', '$botAs', $java, 'NO_UPDATE_{$msg}', now(), now()) ".
              "on duplicate key update lasttime=now()");

    error_log("tracker: $id, $S->ip, $S->siteName, $S->self, NO_UPDATE_INSERT_{$msg}, id not valid did insert instead of update, time=" . (new DateTime)->format('H:i:s:v'));

    $errno = -101;
    $errmsg = "No Updata but insert OK";
    
    $S->query("insert into $S->masterdb.badplayer (ip, botAs, type, count, errno, errmsg, agent, created, lasttime) " .
              "values('$S->ip', '$botAs', 'NO_UPDATE_INSERT_{$msg}', 1, '$errno', '$errmsg', '$S->agent', now(), now()) ".
              "on duplicate key update botAs='$botAs', count=count+1, lasttime=now()");
    
    exit();
  }

  // If this is csstest we are done.
  
  if($type == "csstest") {
    header("Content-Type: text/css");
    echo "/* csstest.css */";
    exit();
  }

  // BLP 2022-08-15 - Get the default image. Use the $S->defaultImage if not null
  // The defaultImage can be set in mysitemap.json or via $_site only.
  
  $img = $S->defaultImage ?? "https://bartonphillips.net/images/blank.png";

  // script and normal may have an image but
  // noscript has NO IMAGE
  
  if($image) {
    $pos = strpos($image, "http"); // does $image start with http?
    if($pos !== false && $pos == 0) { 
      $img = $image; // $image has the full url starting with http (could be https)
    } else {
      // BLP 2022-08-15 - Use $S->imageLocation if not null.
      // This can be set in mysitemap.json or via $_site only.
      $img = ($S->imagesLocation ?? "https://bartonphillips.net") . $image;
    }
  }

  $imageType = preg_replace("~.*\.(.*)$~", "$1", $img);
  
  $imgFinal = file_get_contents($img);
  header("Content-type: image/$imageType");
  echo $imgFinal;
  exit();
}
// END OF GET LOGIC

// Go Away logic

GOAWAY: // Label for goto.

$id = $_GET['id'] ?? $_POST['id'];

if(!$id) {
  error_log("tracker $S->siteName, $S->ip: GOAWAY NO ID");
} else {
  // If this ID is not in the table add it with TRACKER_GOAWAY.
  
  $S->query("insert into $S->masterdb.tracker (id, site, ip, agent, isJavaScript, starttime, lasttime) ".
            "values($id, '$S->siteName', '$S->ip', '$S->agent', " . TRACKER_GOAWAY . ", now(), now()) ".
            "on duplicate key update isJavaScript=isJavaScript|" . TRACKER_GOAWAY . ", lasttime=now()");
}

// otherwise just go away!

if($id) {
  $sql = "select finger from tracker where id=$id";
  $S->query($sql);
  $finger = $S->fetchrow('num')[0] ?? "NONE";
}
$request = $_REQUEST ? ", \$_REQUEST: " . print_r($_REQUEST, true) : '';
$id = $id ?? "NO ID  ";
error_log("tracker: $id, $S->ip, $S->siteName, $S->self, GOAWAY, $S->agent, finger=$finger{$request}");

echo <<<EOF
<!DOCTYPE html>
<html>
<head>
</head>
<body>
<h1>Go Away!</h1>
</body>
</html>
EOF;
