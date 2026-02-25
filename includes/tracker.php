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
  `botAsBits` int DEFAULT 0,
  `site` varchar(25) DEFAULT NULL,
  `page` varchar(255) NOT NULL DEFAULT '',
  `finger` varchar(50) DEFAULT NULL,
  `nogeo` tinyint(1) DEFAULT NULL,
  `browser` varchar(50) DEFAULT NULL,
  `ip` varchar(40) DEFAULT NULL,
  `count` int DEFAULT 1,
  `agent` text,
  `referer` varchar(255) DEFAULT '',
  `starttime` datetime DEFAULT NULL,
  `endtime` datetime DEFAULT NULL,
  `difftime` varchar(20) DEFAULT NULL,
  `isJavaScript` int DEFAULT '0',
  `error` varchar(256) DEFAULT NULL,
  `lasttime` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `site` (`site`),
  KEY `ip` (`ip`),
  KEY `lasttime` (`lasttime`),
  KEY `starttime` (`starttime`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;

// New table, replaces bots.

CREATE TABLE `bots3` (
  `ip` varchar(50) NOT NULL COMMENT 'big enough to handle IP6',
  `agent` text NOT NULL COMMENT 'big enough to handle anything',
  `count` int DEFAULT '1' COMMENT 'the number of time this has been updated',
  `robots` int DEFAULT NULL COMMENT 'bit mapped values as above see defines.php',
  `site` int DEFAULT NULL COMMENT 'bitmasked values of sites see defines.php',
  `page` varchar(255) DEFAULT NULL COMMENT 'the page on my site',
  `created` datetime DEFAULT NULL COMMENT 'when record created',
  `lasttime` datetime DEFAULT CURRENT_TIMESTAMP COMMENT 'auto, the lasttime this was updated',
  UNIQUE KEY `ip_agent_page` (`ip`,`agent`(255),`page`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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

/* New errno values:
   These all have these error codes plus the $e->getCode().
   It looks like: <below code>: $e->getCode().
   -100 = ID is not numeric: $id
   -102 = Insert to tracker failed
   -103 = NO ID: No tracker logic triggered
   -104 = HAS ID: No tracker logic triggered
   -105 = tracker: NO RECORD for id=$id, type=$msg
*/

define("TRACKER_VERSION", "5.1.0tracker-pdo");

//$DEBUG_START = true; // start
//$DEBUG_LOAD = true; // load
//$DEBUG_TIMER1 = true; // Timer
//$DEBUG_TIMEE2 = true;
//$DEBUG_DAYCOUNT = true; // Timer real+1
//$DEBUG_MSG = true; // AjaxMsg
//$DEBUG_GET1 = true;
//$DEBUG_ISABOT2 = true; // This is in the 'image' GET logic
//$DEBUG_NOSCRIPT = true; // no script

$_site = require_once getenv("SITELOADNAME");
//$_site = require_once getenv("AUTOLOADNAME");

// If you want the version defined ONLY and no other information.

if($__VERSION_ONLY === true) {
  return TRACKER_VERSION;
}

// I don't think I should do it.
//$_site->noTrack = true; // Don't track or do geo!
//$_site->noGeo = true;

// START OF IMAGE and CSSTEST FUNCTIONS. These are NOT javascript but rather use $_GET.
// NOTE: The image functions are GET calls from the original php file.
//       THESE ARE NOT DONE BY tracker.js!

// IMPORTANT *************************
// $_GET['mysitemap'] is the mysitemap.json from the original page not from the tracker
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

// Special case for 'csstest'. It does not have an ip, agent or page so we can't log into bots3
// table, and it does not have 'mysitemap'!

if($_GET['page'] == "csstest") {
  $S = new Database($_site);

  $msg = strtoupper($_GET['page']);
  $id = $_GET['id'];

  if(!is_numeric($id)) {
    // I don't think I can ever have a duplicate key but just in case 'ignore' added to 'insert'.
    // Because we do not have ip etc. we use the default from tracker.php.
    
    $S->sql("
insert ignore into $S->masterdb.badplayer (ip, site, page, type, errno, errmsg, agent, created, lasttime)
values('$S->ip', '$S->siteName', '$S->self', '$msg', -980, 'NO_ID', '$S->agent', now(), now())");

    logInfo("tracker.php: csstest.css-NO_ID, line=".__LINE__);
    
    header("Content-Type: text/css");
    echo "/* csstest.css-NO_ID */";
    exit();
  }

  // The $id that was passed in from .htaccess is valid so we can get the rest of the information.
  
  try { // BLP 2025-03-29 - try/catch
    $S->sql("select ip, site, page, botAsBits, agent, referer, isJavaScript from $S->masterdb.tracker where id=$id");
    [$ip, $site, $page, $botAsBits, $agent, $ref, $js] = $S->fetchrow('num');
  } catch(Exception $e) {
    $errno = $e->getCode();
    $errmsg = $e->getMessage();
    logInfo("tracker $msg: id=$id, errno=$errno, errmsg=$errmsg, line=". __LINE__);
    exit();
  }

  $js |= TRACKER_CSS;
  $botAsBits |= BOTS_COUNTED;
  
  $S->sql("
update $S->masterdb.tracker set isJavaScript=$js,
botAsBits=botAsBits|$botAsBits, referer='$ref', count=count+1 where id=$id"); // BLP 2025-03-29 - 

  // Now we can do the bots3 update because we have ip, agent, and page.

  $S->updateBots3($ip, $agent, $page, $site, $botAsBits);
  
  header("Content-Type: text/css");
  echo "/* csstest.css */";
  exit();
}

// *******************************************************
// This is the rest of the GET for 'normal' and 'noscript'

if($type = $_GET['page']) {
  $id = $_GET['id'];
  $image = $_GET['image'];
  $msg = strtoupper($type);
  $mysitemap = $_GET['mysitemap'];

  // If $mysitemap is empty we can't proceed.

  if(empty($mysitemap)) {
    $S = new Database($_site);

    // Do I have an $id?
    
    if(is_numeric($id)) {
      // Yes $id is a number
      // See if I can get real info for $id?

      if($S->sql("select ip, page, site, agent, botAsBits, isJavaScript from $S->masterdb.tracker where id=$id")) {
        [$ip, $page, $site, $agent, $botAsBits, $java] = $S->fetchrow('num');
      } else {
        goaway($S, "tracker: No tracker entry for id=$id", __LINE__);
        // GOAWAYNOW (exit)
      }

      // It looks like I have a real id and ip, page, site and agent my be real.
      $botAsBits |= BOTS_NO_MYSITEMAP;
      $java |= TRACKER_BOT;
      try {
        $S->sql("update $S->masterdb.tracker set botAsBits=$botAsBits, isJavaScript=$java where id=$id");

        $S->updateBots3($ip, $agent, $page, $site, BOTS_NO_MYSITEMAP);
      } catch(Exception $e) {
        $err = $e->getCode();
        $errmsg = $e->getMessage();
        logInfo("tracker.php updates failed: id=$id, err=$err, errmsg=$errmsg, line=". __LINE__);
      }
    }
    goaway($S, "tracker GET $msg no mysitemap.json: id=$id", __LINE__);
    // GOAWAYNOW (exit)
  }    

  // ****************************************************************************************
  // If we have $mysitemap get the mysitemap.json from the caller.
  // This should happend for both types.
  // Here I am looking for just the part that looks like the domain name.
  // All of my server directory names are the same as the domain name without any prefix like
  // 'www' etc.

  $x = preg_replace("~^/var/www/(.*?)/.*$~", "$1", $mysitemap);

  // Is this from one of my domains?

  if(!in_array($x, ["bartonphillips.com", "bonnieburch.com", "bartonlp.com", "bartonphillips.net", "bartonlp.org",
                    "newbernzig.com", "jt-lawnservice.com", "newbern-nc.info", "swam.us",
                    "bartonphillips.org", "bartonphillips.org:8000"
                   ]))
  {
    // NOT one of my domains.

    goaway($S, "tracker $msg, require is not form my domains ($x): id=$id, type=$type, mysitemap=$mysitemap", __LINE__);
    // GOAWAYNOW (exit)
  }

  // This is from one of my domains so get the mysitemap.json informaton from the passed in
  // $mysitemap. We need to capture the output and put it into $tmp.

  ob_start();
  require $mysitemap; // This is not PHP it is JSON so no returned value.
  $tmp = ob_get_contents();
  ob_end_clean();

  // -------------------------------------------------------------------------------------------
  // IMPORTANT ***
  // Now that we have the actual value from the mysitemap.json use it to get our $_site and then
  // instanciate my $S with Database.

  $_site = json_decode(stripComments($tmp));

  // -----------------------------------------------
  // This is the second time we do noTrack and noGeo
  //$_site->noTrack = true; 
  //$_site->noGeo = true;   

  $S = new Database($_site);
  $S->noTrack = true; // Don't track or do geo!
  $S->noGeo = true;

  // -----------------------------------------------
  
  // *************************************************************************************************
  // Now after all the hoki-pokie we have a valid $_site with information form $mysitemap, the calling
  // sites mysitemap.json
  // *************************************************************************************************  

  // If the $id is not numeric we can't go on.
  
  if(!is_numeric($id)) {
    $errno = "-100";
    $errmsg = "ID is not numeric: $id";

    // No id, and ip, site, page, and agent are not yet valid. Use $S->...

    $sql = "
insert into $S->masterdb.badplayer (ip, site, page, type, errno, errmsg, agent, created, lasttime)
values('$S->ip', '$S->siteName', '$S->self', '$msg', '$errno', '$errmsg', '$S->agent', now(), now())";

    $S->sql($sql);
    goaway($S,
           "tracker GET ID_IS_NOT_NUMERIC: id=$id, ip=$S->ip, site=$S->siteName, ".
           "errno=$errno, errmsg=$errmsg, agent=$S->agent", __LINE__, true);
    // GOAWAY (exit)
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
      goaway($S, "tracker GET: Switch Error: ip=$S->ip, site=$S->siteName, type=$type", __LINE__, true);
      // GOAWAY (exit) 
  }

  // Get information from tracker.

  if($S->sql("
select site, ip, page, finger, isJavaScript, agent, botAsBits, difftime
from $S->masterdb.tracker where id=$id")) {
    [$site, $ip, $page, $finger, $js, $agent, $botAsBits, $difftime] = $S->fetchrow('num');
  } else {
    goaway($S, "tracker GET, NO RECORD: id=$id, type=$type", __LINE__, true);
    // GOAWAY (exit)
  }
  
  // I HAVE A VALID 'tracker' RECORD, with $id, $ip, $site, $page, $finger, $js, $agent,
  // $botAsBits, $difftime.
  // The variables, except for $id, may still my be empty if the original values in tracker were not correct.

  $page = basename($page);
  $java = dechex($js);
  $hexBotAsBits = dechex($botAsBits);
  
  if($DEBUG_GET1)
     logInfo("tracker GET: $id, $ip, $site, $page, $msg, botAsBits=$hexBotAsBits, java=$java, line=". __LINE__);

  $ref = $_SERVER["HTTP_REFERER"];

  if(empty($agent) || $S->isBot($agent) || $type == "noscript") {
    // BLP 2025-01-05 - I don't want $js to have the $or yet as I want to be able to check if
    // NORMAL or NOSCRIPT has happened. So I do the DEBUG test first and then 'or' in the new
    // value.

    $botAsBits |= $S->botAsBits;
    
    if(empty($site)) $site = "NO_SITE";
    if(empty($page)) $page = "NO_PAGE";
    if(empty($agent)) {
      $agent = "NO_AGENT";
      $botAsBits |= BOTS_NOAGENT;
    }

    // Do I expect $difftime to ever be true?
    // This is happening because of a NORMAL or NOSCRIPT. I don't think it should be set yet.

    if($difftime) {
      logInfo("tracker HAS DIFFTIME: ip=$ip, line=". __LINE__);
    } else {
      //$botAsBits |= BOTS_COUNTED | BOTS_BOT;
      $botAsBits |= BOTS_COUNTED; // BLP 2025-04-23 -  
      $js |= $or | TRACKER_BOT;
    }
    
    $hexBotAsBits = dechex($botAsBits);
    $java = dechex($js);

    // At this point we know that either $agent was empty of isBot() is true.
    
    if($DEBUG_ISABOT2) {
      $hexBotAsBits = dechex($botAsBits);
      if($type == "noscript") {
        logInfo("tracker GET ISABOT2_$msg: id=$id, botAsBits=$hexBotAsBits, java=$java, line=". __LINE__);
      } else {
        logInfo("tracker GET ISABOT2_$msg: id=$id, ip=$ip, site=$site, page=$page, type=$type, ".
                "isBot=$S->isBot, botAsBits=$hexBotAsBits, java=$java, image=$image, agent=$agent, line=". __LINE__);
      }
    }

    $S->sql("
update $S->masterdb.tracker set count=count+1, botAsBits=botAsBits|$botAsBits, isJavaScript=isJavaScript|$js, referer='$ref'
where id=$id");

    $S->updateBots3($ip, $agent, $page, $site, $botAsBits);
  } else {
    // $agent is not empty and the $agent is not a BOT and $type is not 'noscript'
    
    $js |= $or; 
    $java = dechex($js);

    // BLP 2025-04-23 - Again I don't think $difftime would be set yet.
    
    //$botAsBits |= BOTS_COUNTED | (empty($difftime) ? BOTS_BOT : BOTS_HAS_DIFFTIME);

    $botAsBits |= BOTS_COUNTED; // BLP 2025-04-23 - 
    
    $S->sql("
update $S->masterdb.tracker set count=count+1, botAsBits=botAsBits|$botAsBits, isJavaScript=isJavaScript|$js, referer='$ref'
where id=$id");

    $S->updateBots3($ip, $agent, $page, $site, $botAsBits);
  }

  $hexBotAsBits = dechex($botAsBits);
  
  if($DEBUG_GET2) logInfo("tracker GET: id=$id, ip=$ip, site=$site, page=$page, ".
                          "botAsBits=$hexBotAsBits, java=$java type=$msg, referer=$ref, line=". __LINE__);

  // $S->defaultImage is from the mysitemap.json before anything in the original
  // page has done things to $_site. So NOTHING you do in the original page will affect these $S
  // values!!!
  
  $img = $S->defaultImage ?? "/var/www/bartonphillips.net/images/blank.png";

  // $type='noscript' has NO $image so it uses the default image above which is normally blank.png.
  // $type='normal' has an $image
  // The type='normal' $image must have http or just a /. Also, trackerLocation must be either an
  // absolute url or a relative url, but NOT a file prefix like /var/www!
  
  if($image) {
    if(str_contains($image, 'http')) { 
      $img = $image; // $image has the full url starting with http (could be https)
    } else {
      $trackerLocation = $S->trackerLocation ?? SITECLASS_DEFAULT_NAME;
      $img = "$trackerLocation/$image";
    }
  }
    
  $imageType = pathinfo($img, PATHINFO_EXTENSION); //preg_replace("~.*\.(.*)$~", "$1", $img);

  $imgFinal = file_get_contents($img);

  if($imageType == 'svg') $imageType = "image/svg+xml";

  header("Content-Type: $imageType");

  // BLP 2024-12-30 - add $DEBUG_NOSCRIPT   
  
  if($msg == "NOSCRIPT" && $DEBUG_NOSCRIPT) {
    logInfo("tracker GET NOSCRIPT, difftime=$difftime: id=$id, ip=$ip, site=$site, page=$page, type=$msg, ".
            "botAsBits=$hexBotAsBits, java=$java, agent=$agent, line=". __LINE__);
  }
  
  echo $imgFinal;
  exit();
}
// END OF GET LOGIC

// Moved all of the JavaScript POST logic after the GET.
// ****************************************************************
// All of the following are the result of a javascript interactionl
// ****************************************************************

// If this is a POST from tracker.js via ajax get the $_site via a
// file_get_contents($_POST['mysitemap']) but use the host from $_site->dbinfo->host above. See
// SiteClass::getPageHead(), siteload.php. 

if($_POST) {
  // Here isMeFalse is a string '1'.
  if($_POST['isMeFalse']) $_site->isMeFalse = true;

  // This allow us to keep the tracker.php at bartonlp.com/otherpages with a
  // symlink to vendor/bartonlp/site-class/includes/tracker.php
  // There are two remote sites where I have to do a get_file_contentes(): HP-Envy and RPI.
  // Everything on the server can use require.

  $mysite = $_POST['mysitemap'];

  unset($_site);

  $_site = json_decode(stripComments(file_get_contents($mysite)));

  $ip = $_SERVER['REMOTE_ADDR'];

  if($_site === null) {
    logInfo("tracker PRE-POST: \$_site is NULL, ip=$ip, line=".__LINE__);
    echo "ERROR \$_site is NULL";
    exit();
  }
  
  $S = new Database($_site);
  // This is not exit!! We save $S in Database.
  // Now everything will be set to $S.
} 

// $S is now valid!

// Post an ajax error message

if($_POST['page'] == 'ajaxmsg') {
  $msg = $_POST['msg'];
  $id = $_POST['id'];
  $ip = $_POST['ip'];
  $agent = $_POST['agent'];
  $site = $_POST['site'];
  $arg1 = $_POST['arg1'];
  $arg2 = $_POST['arg2'];
  
  if($arg1) {
    $args = ", arg1=$arg1";
  }
  if($arg2) {
    $args .= ", arg2=$arg2";
  }

  if(!$S->isMyIp($ip) && $DEBUG_MSG) logInfo("tracker AJAXMSG: id=$id, ip=$ip, site=$site, msg=$msg{$args}, line=".__LINE__);
  
  echo "AJAXMSG OK";
  exit();
}

// 'start' is an ajax call from tracker.js. If JavaScript is enabled we should always do this.

if($_POST['page'] == 'start') {
  $id = $_POST['id']; // BLP 2025-03-24 - 
  $site = $_POST['site'];
  $ip = $_POST['ip']; // This is the real ip of the program. $S->ip will be the ip of ME.
  $agent = $_POST['agent'];
  $visits = $_POST['visits']; // Visits may be 1 or zero. tracker.js sets the mytime cookie.
  $thepage = $_POST['thepage'];
  $ref = $_POST['referer'];

  if(!$id) {
    logInfo("tracker START1 NO ID: site=$site, page=$thepage, line=". __LINE__);
    exit();
  }

  // Use hex value for $js, remove $java.
  
  if($S->sql("select hex(botAsBits), isJavaScript, agent from $S->masterdb.tracker where id=$id")) {
    [$hexBotAsBits, $js, $agent] = $S->fetchrow('num');
  } else { 
    logInfo("tracker START2: id=$id, ip=$ip, site=$site, page=$thispage,  Select of id=$id failed, line=". __LINE__);
    exit();
  }

  $java = dechex($js);
  $js |= TRACKER_START; 
  $js2 = dechex($js);

  if(!$S->isMyIp($ip) && $DEBUG_START) {
    logInfo("tracker START3: id=$id, ip=$ip, site=$site, page=$thepage, botAsBits=$hexBotAsBits, ".
            "referer=$ref, jsin=$java, jsout=$js2, line=". __LINE__);
  }

  $S->sql("
update $S->masterdb.tracker set isJavaScript=$js, referer='$ref', count=count+1 where id=$id");

  echo "Start OK, java=$js";
  exit();
}

// 'load' is an ajax call from tracker.js

if($_POST['page'] == 'load') {
  $id = $_POST['id'];
  $agent = $_POST['agent'];
  $site = $_POST['site'];
  $ip = $_POST['ip'];
  $thepage = $_POST['thepage'];
  
  if(!$id) {
    logInfo("tracker LOAD NO ID: ip=$ip, site=$site, page=$thepage, line=". __LINE__);
    echo "Load Error: no id, exiting";
    exit();
  }

  if($S->sql("select botAsBits, isJavaScript, agent from $S->masterdb.tracker where id=$id")) {
    [$botAsBits, $js, $agent] = $S->fetchrow('num');
  }

  $java = dechex($js);
  $js |= TRACKER_LOAD;
  $js2 = dechex($js);
  $hexBotAsBits = dechex($botAsBits);
  
  if(!$S->isMyIp($ip) && $DEBUG_LOAD && $botAsBits & BOTS_COUNTED === 0)
    logInfo("tracker LOAD: id=$id, ip=$ip, site=$site, page=$thepage, botAsBits=$hexBotAsBits, ".
            "jsin=$java, jsout=$js2, line=". __LINE__);

  // BLP 2023-03-25 - This should maybe be insert/update?
  $botAsBits |= BOTS_COUNTED;
  $S->sql("
update $S->masterdb.tracker set botAsBits=botAsBits|$botAsBits, isJavaScript=isJavaScript|$js, count=count+1
where id='$id'");
  $S->updateBots3($ip, $agent, $thepage, $site, $botAsBits);
  
  echo "Load OK, java=$js";
  exit();
}

// ON EXIT FUNCTION
// NOTE: There will be very few clients that do not support beacon. Only very old versions of
// browsers and of course MS-Ie. Therefore these should not happen often.
// I have not seen one in several months so I am removing this logic and replacing
// it with an error message!
// Tor does not support 'beacon' so Tor comes here.

if($_POST['page'] == 'onexit') {
  $id = $_POST['id'];
  $agent = $_POST['agent'];
  $site = $_POST['site'];
  $ip = $_POST['ip'];
  $thepage = $_POST['thepage'];
  $type = $_POST['type'];

  logInfo("tracker onexit: id=$id, ip=$ip, site=$site, page=$thepage, type=$type, OLD BROWSER or TUR, ".
          "agent=$agent, line=". __LINE__);
  exit;
}
// END OF EXIT FUNCTIONS

// 'timer' is an ajax call from tracker.js
// TIMER. This runs while the page is up.

if($_POST['page'] == 'timer') {
  $id = $_POST['id'];
  $site = $_POST['site'];
  $ip = $_POST['ip'];
  $agent = $_POST['agent'];
  $visits = $_POST['visits'];
  $thepage = $_POST['thepage'];
  $time = $_POST['difftime']; // number of seconds.

  if(!$id) {
    logInfo("tracker TIMER_NOID: ip=$ip,site=$site, difftime=$time, line=". __LINE__);
    echo "Timer Error: no id, exiting";
    exit();
  }

  if(!$S->sql("select botAsBits, isJavaScript, finger, agent, difftime from $S->masterdb.tracker where id=$id")) {
    logInfo("tracker TIMER: No record for id=$id, site=$site, page=$thispage, time=$time, line=".__LINE__);
    echo "Timer Error: no tracker record";
    exit();
  }

  [$botAsBits, $js, $finger, $agent, $difftime] = $S->fetchrow('num');

  $java = dechex($js);
  $js |= TRACKER_TIMER; // Or in TIMER
  $js2 = dechex($js);

  if(!empty($agent)) {
    if($S->isBot($agent)) {
      $hexBotAsBits = dechex($S->botAsBits);

      if($DEBUG_ISABOT)
        logInfo("tracker TIMER_ISABOT1: id=$id, ip=$ip, site=$site, page=$thepage, time=$time, difftime=$difftime, ".
                "botAsBits=$hexBotAsBits, visits=$visits, jsin=$java, jsout=$js2, agent=$agent, line=".__LINE__);
      
      echo "Timer1: botAsBits=$hexBotAsBits. This is a BOT, $id, $ip, $site, $thepage";
      exit(); // If this is a bot don't bother
    }
  } else {
    logInfo("tracker TIMER_EMPTY_AGENT: id=$id, ip=$ip, site=$site, page=$thepage, time=$time, difftime=$difftime, ".
            "java=$js2, botAsBits=$hexBotAsBits, line=". __LINE__);
    echo "Timer Error: no agent";
    exit();
  }

  $botAsBits = BOTS_COUNTED | ($difftime ? BOTS_HAS_DIFFTIME : 0);
  
  $S->sql("
update $S->masterdb.tracker set botAsBits=botAsBits|$botAsBits, count=count+1, isJavaScript=$js, endtime=now(),
difftime=timestampdiff(second, starttime, now()) where id=$id");

  if(!$S->isMyIp($ip) && $DEBUG_TIMER2) {
    $hexBotAsBits = dechex($botAsBits);
    logInfo("tracker TIMER2: id=$id, ip-$ip, site=$site, page=$thepage, botAsBits=$hexBotAsBits, time=$time, ".
            "difftime=$difftime, visits: $visits, jsin=$java, jsout=$js2, agent=$agent, line=". __LINE__);
  }
  
  echo "Timer OK, visits: $visits, java=$js2, finger=$finger";
  exit();
}
// *********************************************
// This is the END of the javascript AJAX calls.
// *********************************************

// GOAWAY is only used by the GET logic.
// goaway($S, $msg, $type). if $type is true enter into badplayer.
// GOAWAYNOW.
// @param: $S SiteClass. The site class
// @param: $msg string.  $msg should have a full header plus a message. That is it should have "tracker ...: other info.
// @param: $line int. The line number where this occured. Default is null
// @param: $type bool. False if $S is from the tracker mysitemap.json, true if from callers
// mysitemap.json. Default to false.
// @return: does not return it does an exit().

function goaway(Database $S, string $msg, ?int $myline=null, bool $type=false): void {
  // If $type === true then skip the first part and do the GOAWAYNON logic only.
  // The information is from the tracker.php mysitemap.json NOT the site that called this.

  if($type !== true) {
    $errno = "-105";
    $errmsg = $msg; // Pass the message along.
    
    // We don't have any real values. We instantiated Database from the tracker.php mysitemap.json
    // not from the $mysitemap variable because it was not there.
    
    $S->sql("
insert into $S->masterdb.badplayer (ip, site, page, type, errno, errmsg, agent, created, lasttime)
values('$S->ip', '$S->siteName', '$S->self', 'GOAWAYNOW', '$errno', '$errmsg', '$S->agent', now(), now())");

    // This is the GOAWAYNOW logic.

    logInfo("tracker.php: $msg, GOAWAYNOW, errno=$errno, errmsg=$errmsg, myline=$myline, line=".__LINE__);
  } else {
    // Here we have a valid mysitemap.json from the caller via $mysitemap.
    
    $id = $_REQUEST['id'];

    if(!is_numeric($id)) {
      $errno = "-103";  
      $errmsg = "$msg, NO ID, No tracker logic triggered, myline=$myline";

      // No id

      $S->sql("
insert into $S->masterdb.badplayer (ip, site, page, type, errno, errmsg, agent, created, lasttime)
values('$S->ip', '$S->siteName', '$S->self', 'GOAWAY NO ID', '$errno', '$errmsg', '$S->agent', now(), now())");
    } else {
      // If this ID is not in the tracker table, add it with TRACKER_GOAWAY.
      // Here $S->ip etc are from the $mysitemap.

      $trackerGoaway = TRACKER_GOAWAY;
      $botAsBits = BOTS_BOT;
      
      $S->sql("
insert into $S->masterdb.tracker (id, site, ip, agent, botAsBits, isJavaScript, starttime)
values($id, '$S->ip', '$S->siteName', '$S->agent', $botAsBits, $trackerGoaway, now())
on duplicate key update count=count+1, botAsBits=botAsBits|$botAsBits, isJavaScript=isJavaScript|$trackerGoaway");

      $errmsg = "$msg, No id. No tracker logic triggered";

      logInfo("tracker.php: $errmsg, myline=$myline, line=".__LINE__);
      
      $S->sql("
insert into $S->masterdb.badplayer (ip, id, site, page, type, errno, errmsg, agent, created, lasttime)
values('$S->ip', $id ,'$S->siteName', '$S->self', 'GOAWAY', '$errno', '$errmsg', '$S->agent', now(), now())");

      $S->sql("select finger from tracker where id=$id");
      $finger = $S->fetchrow('num')[0] ?? "NONE";
    }

    $req = '';
    foreach($_REQUEST as $k=>$v) {
      $req .= "$k=>$v, ";
    }
    $req = rtrim($req, ', ');
    
    $req = $_REQUEST ? ", \$_REQUEST $req" : '';
    $id = $id ?? "NO_ID";

    logInfo("tracker.php: $msg, finger=$finger{$req}, myline=$myline, line=".__LINE__);

    // Now try to update the tracker record (if it exists) with TRACKER_GOAWAY.

    try {
      $java = TRACKER_GOAWAY;
      $S->sql("update $S->masterdb.tracker set isJavaScript=isJavaScript|$java where id=$id");
    } catch(Exception $e) {
      // There may not be a tracker record for this id or id may be empty.

      $err = $e->getCode();
      $errmsg = $e->getMessage();
      logInfo("tracker.php: $msg, No tracker record to update, myline=$myline, line=".__LINE__);
    }
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
  exit();
}

header("Location: NotAuthorized.php");
exit();

// This is the end of PHP '?>'  
?>
<h1>Go Away</h1>
