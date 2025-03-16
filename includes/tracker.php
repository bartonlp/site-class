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
Modified tracker to make id a bigint.

CREATE TABLE `tracker` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `botAs` varchar(100) DEFAULT NULL,
  `site` varchar(25) DEFAULT NULL,
  `page` varchar(255) NOT NULL DEFAULT '',
  `finger` varchar(50) DEFAULT NULL,
  `nogeo` tinyint(1) DEFAULT NULL,
  `browser` varchar(50) DEFAULT NULL,
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
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;

CREATE TABLE `bots` (
  `ip` varchar(40) NOT NULL DEFAULT '',
  `agent` text NOT NULL,
  `count` int DEFAULT NULL,
  `robots` int DEFAULT '0',
  `site` varchar(255) DEFAULT NULL,
  `creation_time` datetime DEFAULT NULL,
  `lasttime` datetime DEFAULT NULL,
  PRIMARY KEY (`ip`,`agent`(254))
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

CREATE TABLE `bots2` (
  `ip` varchar(40) NOT NULL DEFAULT '',
  `agent` text NOT NULL,
  `page` text,
  `date` date NOT NULL,
  `site` varchar(50) NOT NULL DEFAULT '',
  `which` int NOT NULL DEFAULT '0',
  `count` int DEFAULT NULL,
  `lasttime` datetime DEFAULT NULL,
  PRIMARY KEY (`ip`,`agent`(254),`date`,`site`,`which`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `badplayer` (
  `primeid` int NOT NULL AUTO_INCREMENT,
  `id` int DEFAULT NULL,
  `ip` varchar(20) NOT NULL,
  `site` varchar(50) DEFAULT NULL,
  `page` varchar(255) DEFAULT NULL,
  `botAs` varchar(50) DEFAULT NULL,
  `type` varchar(50) NOT NULL,
  `errno` varchar(100) DEFAULT NULL,
  `errmsg` varchar(255) NOT NULL,
  `agent` varchar(255) DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  `lasttime` datetime DEFAULT NULL,
  PRIMARY KEY (`primeid`)
) ENGINE=InnoDB AUTO_INCREMENT=279 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
*/

/* BLP 2024-12-31 - New errno values:
   These all have these error codes plus the $e->getCode().
   It looks like: <below code>: $e->getCode().
   -100 = ID is not numeric: $id
   -102 = Insert to tracker failed
   -103 = NO ID: No tracker logic triggered
   -104 = HAS ID: No tracker logic triggered
   -105 = tracker: NO RECORD for id=$id, type=$msg
*/

define("TRACKER_VERSION", "4.1.0tracker-pdo"); // BLP 2025-03-16 - Add new logic to update bots and bots2 in GET.

$DEBUG_START = true; // start
//$DEBUG_LOAD = true; // load
//$DEBUG_TIMER1 = true; // Timer
//$DEBUG_TIMEE2 = true;
//$DEBUG_DAYCOUNT = true; // Timer real+1
$DEBUG_MSG = true; // AjaxMsg
//$DEBUG_GET1 = true;
//$DEBUG_ISABOT1 = true; // This is in the 'timer' logic
//$DEBUG_ISABOT2 = true; // This is in the 'image' GET logic
//$DEBUG_NOSCRIPT = true; // no script

// If you want the version defined ONLY and no other information.
// BLP 2024-12-17 - removed check for $_site.

if($__VERSION_ONLY === true) {
  return TRACKER_VERSION;
}

//BLP 2024-12-17 - Move GET to top of page.
// START OF IMAGE and CSSTEST FUNCTIONS These are NOT javascript but rather use $_GET.
// NOTE: The image functions are GET calls from the original php file.
//       THESE ARE NOT DONE BY tracker.js!

// Here is an example of the banner.i.php in bartonphillips.com/includes:
/*
if(!class_exists('Database')) header("location: https://bartonlp.com/otherpages/NotAuthorized.php");

return <<<EOF
<header>
  <!-- bartonphillips.com/includes/banner.i.php -->
  <a href="$h->logoAnchor">$image1</a>
  $image2
  $mainTitle
  <noscript>
    <p style='color: red; background-color: #FFE4E1; padding: 10px'>
      $image3
      Your browser either does not support <b>JavaScripts</b> or you have JavaScripts disabled, in either case your browsing
      experience will be significantly impaired. If your browser supports JavaScripts but you have it disabled consider enabaling
      JavaScripts conditionally if your browser supports that. Sorry for the inconvienence.</p>
    <p>The rest of this page will not be displayed.</p>
    <style>#content { display: none; }</style>
  </noscript>
</header>
<div id="content"> <!-- BLP 2024-12-16 - See footer.i.php for ending </div>. -->
EOF;
*/
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

// IMPORTANT *************************
// BLP 2024-12-17 - $_GET['mysitemap'] is the mysitemap.json from the original page not from the tracker
// location. That is, if the original page was https://bartonphillips.com, the mysitemap.json at
// that location is passed as $_GET['mysitemap'], it is NOT from the location of tracker.php. This
// is usually a symlink in https://bartonlp.com/otherpages.
// If you need things like 'trackerImg1' etc. or 'trackerLocation' etc. they must be set in the
// page that call tracker.php.
// When you are using '/var/www/site-class/includes/autoload.php' you will need to add
// 'trackerLocation' and 'trackerLocationJs'. These need to be a URL path either explisit like
// 'https://..." or relative like "./something". You may need to put symlinks in the home directory.
// If you are using the standard /var/www/vendor/bartonlp/site-class/includes/siteload.php, there
// is no need for the 'trackerLocation' and 'trackerLocationJs'.
// **********************************

// Special case for 'csstest'. It does not have an ID.

if($_GET['page'] == "csstest") {
  $msg = strtoupper($_GET['page']);
  $id = $_GET['id'];

  if(!is_numeric($id)) {
    error_log("tracker CSSTEST: id not numeric, id=$id, line=" . __LINE__);
  }
  
  $_site = require_once getenv("SITELOADNAME");
  $_site->noTrack = true; // BLP 2025-01-06 - 
  $_site->noGeo = true;   // BLP 2025-01-06 - 

  $S = new Database($_site);

  $S->sql("select ip, site, page, botAs, agent, referer, isJavaScript from $S->masterdb.tracker where id=$id");
  [$ip, $site, $page, $botAs, $agent, $ref, $js] = $S->fetchrow('num');

  if(!str_contains($botAs, BOTAS_COUNTED)) {
    $botAs = BOTAS_COUNTED . "," . $botAs; // BLP 2024-03-21 - added
    $botAs = rtrim($botAs, ',');
  } else {
    $botAs = BOTAS_COUNTED;
  }
  
  $js |= TRACKER_CSS;
  
  $ref = $_SERVER["HTTP_REFERER"];

  // BLP 2024-12-17 - fixed update $js and referer='$ref'.
  
  if($ref) {
    $sql = "insert into $S->masterdb.tracker (id, ip, site, page, botAs, agent, referer, isJavaScript, error, starttime, lasttime) ".
           "values('$id', '$ip', '$site', '$page', '$botAs', '$agent', '$ref', $js, '$msg', now(), now()) ".
           "on duplicate key update isJavaScript=$js, botAs='$botAs', referer='$ref', lasttime=now()";
  } else {
    $sql = "insert into $S->masterdb.tracker (id, ip, site, page, botAs, agent, isJavaScript, error, starttime, lasttime) ".
           "values('$id', '$ip', '$site', '$page', '$botAs', '$agent', $js, '$msg', now(), now()) ".
           "on duplicate key update isJavaScript=$js, botAs='$botAs', lasttime=now()";
  }

  $S->sql($sql);

  header("Content-Type: text/css");
  echo "/* csstest.css */";
  exit();
}

// This is the rest of the GET for 'normal' and 'noscript'

if($type = $_GET['page']) {
  $id = $_GET['id'];
  $image = $_GET['image'];
  $msg = strtoupper($type);
  $mysitemap = $_GET['mysitemap'];

  // If we have $mysitemap get the mysitemap.json from the caller.
  // This should happend for all types.
  // Get $_site just incase we have an early error and goaway().
  // This $_site will be overwritten by the values we get from the GET 'mysitemap' query parameter.

  //require_once "/var/www/site-class/includes/autoload.php";
  $_site = require_once getenv("SITELOADNAME");

  // BLP 2025-02-25 - add in_array() logic
  
  if(!empty($mysitemap)) {
    $x = preg_replace("~^/var/www/(.*?)/.*$~", "$1", $mysitemap);

    if(!in_array($x, ["bartonphillips.com", "bonnieburch.com", "bartonlp.com", "bartonphillips.net", "bartonlp.org",
                      "newbernzig.com", "jt-lawnservice.com", "newbern-nc.info", "swam.us"])) {
      error_log("tracker require is not from my domains ($x): id=$id, type=$msg, mysitemap=$mysitemap, line=". __LINE__);
      $S = new Database($_site); // This is for goaway which need $S
      goaway("tracker $msg, require is not form my domains ($x)");
      exit();
    }
    
    ob_start();
    require $mysitemap;
    $tmp = ob_get_contents();
    ob_end_clean();

    $_site = json_decode(stripComments($tmp));
  } else {
    error_log("tracker NO_MYSITEMAP: id=$id, type=$msg, image=$image, line=". __LINE__);
    $S = new Database($_site); // This is for goaway which need $S
    goaway("GET $msg no mysitemap.json");
    exit();
  }
  
  $_site->noTrack = true; // BLP 2025-01-06 - 
  $_site->noGeo = true;   // BLP 2025-01-06 - 

  $S = new Database($_site);

  if(!is_numeric($id)) {
    $errno = "-100";
    $errmsg = "ID is not numeric: $id";

    // No id, and ip, site, thepage, and agent are not yet valid. Use $S->...

    $sql = "insert into $S->masterdb.badplayer (ip, site, page, type, errno, errmsg, agent, created, lasttime) ".
           "values('$S->ip', '$S->siteName', '$S->self', '$msg', '$errno', '$errmsg', '$S->agent', now(), now())";

    error_log("tracker GET ID_IS_NOT_NUMERIC: id=$id, ip=$S->ip, site=$S->siteName, errno=$errno, errmsg=$errmsg, agent=$S->agent");

    $S->sql($sql);
    goaway('now');
    exit();
  }

  // $S values are from the original pages mysitemap.json for every thing exept type 'csstest'..
  
  switch($type) {
    case "normal":
      $or = TRACKER_NORMAL;
      break;
    case "noscript":
      $or = TRACKER_NOSCRIPT;
      break;
     default:
      error_log("tracker GET: Switch Error: $S->ip, $S->siteName, type=$type");
      goaway();
      exit();
  }

  // Get information from tracker.

  try {
    $sql = "select site, ip, page, finger, isJavaScript, agent, botAs, difftime from $S->masterdb.tracker where id=$id";

    if($S->sql($sql)) {
      [$site, $ip, $thepage, $finger, $js, $agent, $botAs, $difftime] = $S->fetchrow('num');
    } else {
      throw new Exception("NO RECORD for id=$id, type=$msg", -105); // This will be caught below.
    }
  } catch(Exception $e) { // catches the throw above.
    if(($errno = $e->getCode()) !== -105) {
      $errmsg = $e->getMessage();
      error_log("tracker GET: type=$type, sql=$sql, errno=$errno, errmsg=$errmsg, line=" . __LINE__);
      exit("<h1>Go Away</h1>");
    }

    error_log("tracker GET: type=$type, sql=$sql, errno=$errno, errmsg=" . $e->getMessage() . ", line=" . __LINE__);

    // At this point $site, $ip, $thepage and $agent are NOT VALID, BUT $id should be valid.
    // We don't have a tracker record for this id/ip, so try th create a new record.

    try {
      $ip = "NO_IP"; 

      $sql = "insert into $S->masterdb.tracker (id, ip, error, starttime, lasttime) ".
             "values($id, '$S->ip', 'Select failed insert on $ip $msg', now(), now()) ".
             "on duplicate key update error='Update, Select failed $ip $msg', lasttime=now()";

      $S->sql($sql);
      error_log("tracker GET: record added for id=$id with no data, line=" . __LINE__);
    } catch(Exception $e) {
      // The primary key is ip and type

      $errno = "-102: " . $e->getCode();
      $errmsg = "Insert to tracker failed"; // BLP 2024-12-31 - 

      $sql = "insert into $S->masterdb.badplayer (ip, page, type, errno, errmsg, agent, created, lasttime) ".
             "values('$S->ip', '$S->self', '$msg', '$errno', '$errmsg', '$S->agent', now(), now())";

      if(!$S->sql($sql)) {
        error_log("tracker GET: \$S->ip=$S->ip, \$S->siteName=$S->siteName, \$ip=$ip, errno=$errno, errmsg=$errmsg, badplayer - could not do insert/update");
      }
      goaway('now');
      exit();
    }
  }

  // If we get here the $ip, $site, $thepage and $agent are all valid.

  if($DEBUG_GET1)
     error_log("tracker GET: $id, $ip, $site, $thepage, $msg, java=$java");
  
  if(!str_contains($botAs, BOTAS_COUNTED)) {
    $botAs = BOTAS_COUNTED . "," . $botAs; // BLP 2024-03-21 - added
    $botAs = rtrim($botAs, ',');
  }

  if(empty($agent) || $S->isBot($agent)) {
    // BLP 2025-01-05 - I don't want $js to have the $or yet as I want to be able to check if
    // NORMAL, NOSCRIPT or CSS has happened. So I do the DEBUG test first and then or in the new
    // value.

    if(empty($site)) $site = "NO_SITE";
    if(empty($thepage)) $thepage = "NO_PAGE";
    if(empty($agent)) $agent = "NO_AGENT";

    // At this point we know that either $agent was empty of isBot() is true.
    
    if($DEBUG_ISABOT1 && ($js & (TRACKER_NORMAL | TRACKER_NOSCRIPT | TRACKER_CSS) === 0))
      error_log("tracker GET ISABOT1_$msg: id=$id, ip=$ip, site=$site, page=$thepage, type=$type, isBot=$isBot, java=$java, image=$image, agent=$agent");

    $js |= $or | TRACKER_BOT; // BLP 2025-03-16 -  
    $java = dechex($js);

    if($DEBUG_ISABOT2) {
      if($msg == "NOSCRIPT") {
        error_log("tracker GET ISABOT2_$msg: id=$id, java=$java");
      } else {
        error_log("tracker GET ISABOT2_$msg: id=$id, ip=$ip, site=$site, page=$thepage, type=$type, isBot=$isBot, java=$java, image=$image, agent=$agent");
      }
    }
    
    $S->sql("insert into $S->masterdb.tracker (id, ip, site, page, botAs, agent, isJavaScript, error, starttime, lasttime) ".
            "values($id, '$ip', '$site', '$thepage', '$botAs', '$agent', $js, 'ISABOT type=$msg', now(), now()) ".
            "on duplicate key update error='ISABOT_UPDATE type=$msg', botAs='$botAs', lasttime=now()");

    // BLP 2025-03-16 - New logic to update the bots and bots2 tables.
    
    $S->sql("insert into $S->masterdb.bots (ip, agent, count, robots, site, creation_time, lasttime) ".
            "values('$ip', '$agent', 1, " . BOTS_SITECLASS . ", '$site', now(), now()) ".
            "on duplicate key update robots = robots | " . BOTS_SITECLASS . ", lasttime=now()");

    $S->sql("insert into $S->masterdb.bots2 (ip, agent, page, date, site, which, count, lasttime) ".
            "values('$ip', '$agent', '$thepage', date(now()), '$site', " . BOTS_SITECLASS . ", 1, now()) ".
            "on duplicate key update count=count+1, lasttime=now()");
  } else {
    // BLP 2025-01-05 - if not a bot or in the values.
    
    $js |= $or; 
    $java = dechex($js);
  }
  
  $ref = $_SERVER["HTTP_REFERER"];

  if($DEBUG_GET2) error_log("tracker GET: $id, $ip, $site, $thepage, $msg -- referer=$ref");

  // BLP 2024-12-17 - fixed update $js and referer='$ref'.
  
  if($ref) {
    $sql = "insert into $S->masterdb.tracker (id, ip, site, page, botAs, agent, referer, isJavaScript, error, starttime, lasttime) ".
           "values($id, '$ip', '$site', '$thepage', '$botAs', '$agent', '$ref', $js, '$msg', now(), now()) ".
           "on duplicate key update isJavaScript=$js, botAs='$botAs', referer='$ref', lasttime=now()";
  } else {
    $sql = "insert into $S->masterdb.tracker (id, ip, site, page, botAs, agent, isJavaScript, error, starttime, lasttime) ".
           "values($id, '$ip', '$site', '$thepage', '$botAs', '$agent', $js, '$msg', now(), now()) ".
           "on duplicate key update isJavaScript=$js, botAs='$botAs', lasttime=now()";
  }

  $S->sql($sql);

  // BLP 2024-12-17 - $S->defaultImage is from the mysitemap.json before anything in the original
  // page has done things to $_site. So NOTHING you do in the original page will affect these $S
  // values!!!
  
  $img = $S->defaultImage ?? "/var/www/bartonphillips.net/images/blank.png";

  // script and normal may have an image but
  // noscript has NO IMAGE

  if($image) {
    $pos = strpos($image, "http"); // does $image start with http?
    if($pos !== false && $pos == 0) { 
      $img = $image; // $image has the full url starting with http (could be https)
    } else {
      $trackerLocation = $S->trackerLocation ?? "/var/www/bartonphillips.net/";
      $img = "$trackerLocation/$image";
    }
  }
    
  $imageType = pathinfo($img, PATHINFO_EXTENSION); //preg_replace("~.*\.(.*)$~", "$1", $img);

  $imgFinal = file_get_contents($img);

  if($imageType == 'svg') $imageType = "image/svg+xml";

  header("Content-Type: $imageType");

  // BLP 2024-12-30 - add $DEBUG_NOSCRIPT   
  
  if($msg == "NOSCRIPT" && $DEBUG_NOSCRIPT) {
    if(!empty($difftime)) {
      error_log("tracker GET: NOSCRIPT, difftime=$difftime: $id, $ip, $site, $thepage, $msg, java=$java, agent=$agent");
    } else {
      error_log("tracker GET: $id, $ip, $site, $thepage, $msg, java=$java, agent=$agent");
    }
  }
  
  echo $imgFinal;
  exit();
}
// END OF GET LOGIC

// BLP 2024-12-17 - Moved all of the JavaScript POST logic after the GET.
// ****************************************************************
// All of the following are the result of a javascript interactionl
// ****************************************************************

$_site = require_once getenv("SITELOADNAME"); // This is from vendor/bartonlp/site-class
//$_site = require_once "/var/www/site-class/includes/autoload.php";

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

  // BLP 2024-07-29 - extend logic for my Acer Spin3 and my RPI.
  
  if(str_contains($mysite, "bartonphillips.org")) {
    $port = null;

    if(str_contains($mysite, ":8000")) {
      $port = ":8000";
    } elseif(str_contains($mystie, ":8080")) {
      $port = ":8080";
    } // BLP 2024-11-06 - removed the else with a throw.
    
    $mysite = preg_replace("~/var/www/(.*?)/~", "https://bartonphillips.org$port/", $mysite);
  }

  if($mysite == "mysitemap.json") $mysite = "https://bartonphillips.org/$mysite";
  
  $_site = json_decode(stripComments(file_get_contents($mysite)));

  $ip = $_SERVER['REMOTE_ADDR'];

  if($_site === null) {
    error_log("tracker PRE-POST: \$_site is NULL, ip=$ip");
    echo "ERROR \$_site is NULL";
    exit();
  }
  
  $_site->dbinfo->host = $tmp; // Still use the original dbinfo->host
}

// We have set up $_site from the passed in $mysitemap.

$_site->noTrack = true; // Don't track or do geo!
$_site->noGeo = true;

$S = new Database($_site); // BLP 2023-10-02 - because we use Database noTrack and noGeo are set and we do not do any tracking.

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

  if(!$S->isMyIp($ip) && $DEBUG_MSG) error_log("tracker AJAXMSG: $site, $ip: $msg{$args}");
  
  echo "AJAXMSG OK";
  exit();
}

// 'start' is an ajax call from tracker.js. If JavaScript is enabled we should always do this.

if($_POST['page'] == 'start') {
  $id = $_POST['id'];
  $site = $_POST['site'];
  $ip = $_POST['ip']; // This is the real ip of the program. $S->ip will be the ip of ME.
  $visits = $_POST['visits']; // Visits may be 1 or zero. tracker.js sets the mytime cookie.
  $thepage = $_POST['thepage'];
  $ref = $_POST['referer'];

  if(!$id) {
    error_log("tracker START1 NO ID: site=$site, page=$thepage, line=". __LINE__);
    exit();
  }

  if($S->isMyIp($ip)) {
    $visits = 0;
  }

  // BLP 2024-12-04 - use hex value for $js, remove $java.
  
  if($S->sql("select botAs, isJavaScript, agent from $S->masterdb.tracker where id=$id")) {
    [$botAs, $js, $agent] = $S->fetchrow('num');
  } else { // BLP 2023-02-10 - add for debug
    error_log("tracker START2: id=$id, ip=$ip, site=$site, page=$thispage,  Select of id=$id failed, line=". __LINE__);
  }

  $java = dechex($js);
  $js |= TRACKER_START; 
  $js2 = dechex($js);

  if(!$S->isMyIp($ip) && $DEBUG_START) {
    error_log("tracker START3: id=$id, ip=$ip, site=$site, page=$thepage, botAs=$botAs, referer=$ref, jsin=$java, jsout=$js2, line=". __LINE__);
  }

  // BLP 2025-02-25 - remove if($ref) as if it is not there it will not be posted.
  
  $sql = "insert into $S->masterdb.tracker (id, botAs, site, page, ip, agent, referer, starttime, isJavaScript, lasttime) ".
         "values($id, '$botAs', '$site', '$thepage', '$ip', '$agent', '$ref', now(), '$js', now()) ".
         "on duplicate key update isJavaScript=$js, referer='$ref', lasttime=now()";
  
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
    error_log("tracker LOAD NO ID: ip=$ip, site=$site, page=$thepage, line=". __LINE__);
    echo "Load Error: no id, exiting";
    exit();
  }

  // BLP 2024-12-04 - use hex value for $js, remove $java.

  if($S->sql("select botAs, isJavaScript, agent from $S->masterdb.tracker where id=$id")) {
    [$botAs, $js, $agent] = $S->fetchrow('num');
  }

  $java = dechex($js);
  $js |= TRACKER_LOAD;
  $js2 = dechex($js);

  if(!$S->isMyIp($ip) && $DEBUG_LOAD && strpos($botAs, BOTAS_COUNTED) === false)
    error_log("tracker LOAD: id=$id, ip=$ip, site=$site, page=$thepage, botAs=$botAs, jsin=$java, jsout=$js2, line=". __LINE__);

  // BLP 2023-03-25 - This should maybe be insert/update?
  
  $S->sql("update $S->masterdb.tracker set isJavaScript=$js, lasttime=now() where id='$id'");

  echo "Load OK, java=$js";
  exit();
}

// ON EXIT FUNCTION
// NOTE: There will be very few clients that do not support beacon. Only very old versions of
// browsers and of course MS-Ie. Therefore these should not happen often.
// BLP 2022-10-27 - I have not seen one in several months so I am removing this logic and replacing
// it with an error message!
// BLP 2023-1-006 - Tor does not support 'beacon' so Tor comes here.

if($_POST['page'] == 'onexit') {
  $id = $_POST['id'];
  $site = $_POST['site'];
  $ip = $_POST['ip'];
  $thepage = $_POST['thepage'];
  $type = $_POST['type'];

  if($S->sql("select botAs, isJavaScript, difftime, agent from $S->masterdb.tracker where id=$id")) {
    [$botAs, $js, $difftime, $agent] = $S->fetchrow('num');
  } else {
    error_log("tracker ONEXIT: NO record for $id, line=" . __LINE__);
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
      error_log("tracker ONEXIT SWITCH_ERROR_{$type}: id=$id, ip=$ip, site=$site, page=$thepage, botAs=$botAs -- \$S->ip=$S->ip, \$S->agent=$S->agent, line=". __LINE__);
      exit();
  }

  $botAs = empty($botAs) ? "Tor?" : "$botAs,Tor?";
  $js |= $onexit;
  
  $S->sql("update $S->masterdb.tracker set botAs='$botAs', endtime=now(), difftime=timestampdiff(second, starttime, now()), ".
            "isJavaScript=$js, lasttime=now() where id=$id");

  error_log("tracker ONEXIT: id=$id, ip=$ip, site=$site, page=$thepage, type=$msg, botAs=$botAs, Maybe Tor?");
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
  $time = $_POST['difftime']; // number of seconds.

  //error_log("tracker TIMER: id=$id, ip=$ip, site=$site, page=$thepage, time=$time, line=" . __LINE__);
  
  if(!$id) {
    error_log("tracker TIMER_NOID: ip=$ip,site=$site, difftime=$time, line=". __LINE__);
    echo "Timer Error: no id, exiting";
    exit();
  }

  if(!$S->sql("select botAs, isJavaScript, finger, agent, difftime from $S->masterdb.tracker where id=$id")) {
    error_log("tracker TIMER: No record for id=$id, site=$site, page=$thispage, time=$time");
    echo "Timer Error: no tracker record";
    exit();
  }

  [$botAs, $js, $finger, $agent, $difftime] = $S->fetchrow('num');

  $java = dechex($js);
  $js |= TRACKER_TIMER; // Or in TIMER
  $js2 = dechex($js);

  if(!empty($agent)) {
    if($S->isBot($agent)) {
      if($DEBUG_ISABOT)
        error_log("tracker TIMER_ISABOT1: id=$id, ip=$ip, site=$site, page=$thepage, time=$time, difftime=$difftime, botAs=$botAs, visits=$visits, jsin=$java, jsout=$js2, agent=$agent, line=".__LINE__);
      
      echo "Timer1: agent empty, This is a BOT, $id, $ip, $site, $thepage";
      exit(); // If this is a bot don't bother
    }
  } else {
    error_log("tracker TIMER_EMPTY_AGENT: id=$id, ip=$ip, site=$site, page=$thepage, time=$time, difftime=$difftime, java=$js2, botAs=$botAs, line=". __LINE__);
    echo "Timer Error: no agent";
    exit();
  }

  // BLP 2025-03-03 - 
  // $botAs could have any or all of these: BOTAS_COUNTED,  BOTAS_MATCH, BOTAS_ROBOT, BOTAS_SITEMAP
  // or BOTAS_ZBOT.

  if(str_contains($botAs, BOTAS_COUNTED) === false) { // $botAs is the haystack and BOTAS_COUNTED is the needel.
    // $botAs does not have 'counted', but does it have any other indicators, like from robots.php or
    // sitemap.php?
    
    if($botAs) {
      // $botAs is not empty so add 'counted' at the start.
      $botAs = BOTAS_COUNTED . ",$botAs";
    } else {
      // $botAs is really empty and has no other indicators
      $botAs = BOTAS_COUNTED;
    }
  }
  
  // If we get here either $botAs did contain 'counted' or we fixed it up above.

  $S->sql("update $S->masterdb.tracker set botAs='$botAs', isJavaScript=$js, endtime=now(), ".
          "difftime=timestampdiff(second, starttime, now()), lasttime=now() where id=$id");

  if(!$S->isMyIp($ip) && $DEBUG_TIMER2)
    error_log("tracker TIMER2: id=$id, ip-$ip, site=$site, page=$thepage, botAs=$botAs, time=$time, difftime=$difftime, visits: $visits, jsin=$java, jsout=$js2, agent=$agent, line=". __LINE__);

  echo "Timer OK, visits: $visits, java=$js2, finger=$finger";
  exit();
}
// *********************************************
// This is the END of the javascript AJAX calls.
// *********************************************

// $msg can be 'now' or blank. This allows me to have one goaway() functon that does GOAWAY and
// GOAWAYNOW.

function goaway($msg) {
  global $S;
  
  // If $msg == "now" then skip the first part and do the GOAWAYNOE logic only.

  if(!empty($msg) && $msg != 'now') {
    $errno = "-105";
    $errmsg = $msg;
    
    $S->sql("insert into $S->masterdb.badplayer (ip, site, page, type, errno, errmsg, agent, created, lasttime) " .
            "values('$S->ip', '$S->siteName', '$S->self', 'GOAWAY', '$errno', '$errmsg', '$S->agent', now(), now())");
  
  } elseif(!$msg == 'now') { 
    // Go Away logic

    $id = $_REQUEST['id'];

    if(!is_numeric($id)) {
      $errno = "-103";  
      $errmsg = "NO ID, No tracker logic triggered";

      // No id

      $S->sql("insert into $S->masterdb.badplayer (ip, site, page, type, errno, errmsg, agent, created, lasttime) " .
              "values('$S->ip', '$S->siteName', '$S->self', 'GOAWAY NO ID', '$errno', '$errmsg', '$S->agent', now(), now())");
    } else {
      // If this ID is not in the tracker table, add it with TRACKER_GOAWAY.

      $S->sql("insert into $S->masterdb.tracker (id, site, ip, agent, isJavaScript, starttime, lasttime) ".
              "values($id, '$S->ip', '$S->siteName', '$S->agent', " . TRACKER_GOAWAY . ", now(), now()) ".
              "on duplicate key update isJavaScript=isJavaScript|" . TRACKER_GOAWAY . ", lasttime=now()");

      $errno = "-104: " . $e->getCode();
      $errmsg = "$id, No tracker logic triggered";
      $botAs = BOTAS_COUNTED;

      $S->sql("insert into $S->masterdb.badplayer (ip, id, site, page, type, errno, errmsg, agent, created, lasttime) " .
              "values('$S->ip', $id ,'$S->siteName', '$S->self', 'GOAWAY', '$errno', '$errmsg', '$S->agent', now(), now())");
    }

    if($id) {
      $S->sql("select finger from tracker where id=$id");
      $finger = $S->fetchrow('num')[0] ?? "NONE";
    }
    $request = $_REQUEST ? ", \$_REQUEST: " . print_r($_REQUEST, true) : '';
    $id = $id ?? "NO_ID";

    error_log("tracker GOAWAY: id=$id, ip=$S->ip, site=$S-<siteName, page=$S->self, errno=$errno, errmsg=$errmsg, \$S->agent=$S->agent, agent=$agent finger=$finger{$request}, line=". __LINE__);
  } else {
    // This is the GOAWAYNOW logic.
    
    error_log("tracker GOAWAYNOW: ip=$S->ip, site=$S->siteName, page=$S->self, errno=$errno, errmsg=$errmsg, \$S->agent=$S->agent, agent=$agent finger=$finger{$request}, line=". __LINE__);
  }

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
}
