<?php
// SITE_CLASS_VERSION must change when the GitHub Release version changes.

define("SITE_CLASS_VERSION", "3.6.1"); // BLP 2023-02-24 - 

// One class for all my sites
// This version has been generalized to not have anything about my sites in it!
/**
 * SiteClass
 *
 * @package SiteClass
 * @author Barton Phillips <barton@bartonphillips.com>
 * @link http://www.bartonphillips.com
 * @copyright Copyright (c) 2022, Barton Phillips
 * @license  MIT
 */

/**
 * @package SiteClass
 * This class can be extended to handle special issues and add methods.
 */

require_once(__DIR__ . "/defines.php"); // This has the constants for TRACKER, BOTS, BOTS2, and BEACON

class SiteClass extends Database {
  private $hitCount = 0;

  // Give these default values incase they are not mentioned in mysitemap.json.
  // Note they could still be null from mysitemap.json!
  
  public $count = true;
  
  // Current Doc Type
  public $doctype = "<!DOCTYPE html>";

  /**
   * Constructor
   *
   * @param object $s. If this is not an object we get an error!
   *  The $s is almost always from mysitemap.json.
   *  Once in a while they can be changed by the program instantiating the class.
   *  'count' is default true.
   *  $s has the values from $_site = require_once(getenv("SITELOADNAME"));
   *  which uses siteload.php to gets values from mysitemap.json.
   */
  
  public function __construct(object $s) {
    // Do the parent Database constructor which does the dbAbstract constructor.
    
    parent::__construct($s, true); // set true to tell Database that it has been called from here.
    
    // BLP 2018-07-01 -- Add the date to the copyright notice if one exists

    if($this->copyright) {
      $this->copyright = date("Y") . " $this->copyright";
    }

    // If no database in mysitemap.json set everything so the database is not loaded.
    
    if($this->nodb === true || is_null($this->dbinfo)) {
      // nodb === true so don't do any database stuff
      $this->nodb = true;
      $this->count = false;
      $this->noTrack = true; // If nodb then noTrack is true also.
    }
    
    // These all use database 'barton' ($this->masterdb)
    // and are always done regardless of 'count'!
    // If $this->nodb or there is no $this->dbinfo we have made $this->noTrack true and
    // $this->count false
    
    if($this->noTrack !== true) {
      $this->logagent();   // This logs Me and everybody else! This is done regardless of $this->isBot or $this->isMe().

      // checkIfBot() must be done before the rest because everyone uses $this->isBot.

      $this->checkIfBot(); // This set $this->isBot. Does a isMe() so I never get set as a bot!

      // Now do all of the rest.

      $this->trackbots();  // both 'bots' and 'bots2'. This also does a isMe() so never get put into the 'bots*' tables.
      $this->tracker();    // This logs Me and everybody else but uses the $this->isBot! Note this is done before daycount()
      $this->updatemyip(); // Update myip if it is ME

      // If 'count' is false we don't do these counters

      if($this->count) {
        // Get the count for hitCount. The hitCount is always
        // updated (unless the counter table does not exist).

        $this->counter(); // in 'masterdb' database. Does not count Me but always set $this->hitCount.

        if(!$this->isMe()) { //If it is NOT ME do counter2 and daycount
          $this->counter2(); // in 'masterdb' database
          $this->daycount(); // in 'masterdb' database
        }
      }
    }
  } // End of constructor.

  /**
   * getVersion()
   * @return string version number
   */

  public static function getVersion():string {
    return SITE_CLASS_VERSION;
  }

  /**
   * getHitCount()
   */

  public function getHitCount():int {
    return $this->hitCount;
  }

  /**
   * getDoctype()
   * Returns the CURRENT DocType used by this program
   */

  public function getDoctype():string {
    return $this->doctype;
  }

  /**
   * getPageTopBottom()
   * Get Page Top (<head> and <header> ie banner) and Footer
   * @param ?object $h top stuff
   * @param ?object $b bottom stuff
   *  if $h->footer then use it for the footer and do not call getPageFooter()
   * @return array top, footer
   */

  // BLP 2023-02-20 - reworked how we capture and ues $h, $b
  // See the other getPage... functions which now have not $h or $b passed in.
  // All of the information is placed in $this right here.
  
  public function getPageTopBottom(?object $h=null, ?object $b=null):array {
    // BLP 2023-02-23 - check if $h or $b have any properties.

    if(count((array)$h) != 0) {
      $this->h_inlineScript = $h->inlineScript; // A little klug here
      $this->h_script = $h->script;
    }

    if(count((array)$b) != 0) {
      $this->b_inlineScript = $b->inlineScript;
      $this->b_script = $b->script;
    }

    // and in these as we must not put the inlineScript or the script into $this as it has already
    // been added above.
    
    foreach($h as $k=>$v) {
      if($k == "inlineScript" || $k == "script") continue;
      $this->$k = $v;
    }
    foreach($b as $k=>$v) {
      if($k == "inlineScript" || $k == "script") continue;
      $this->$k = $v;
    }

    //error_log("SiteClass this: " . print_r($this, true));
    
    // Do getPageTop and getPageFooter

    $top = $this->getPageTop();

    // BLP 2022-04-09 - We can pass in a footer via $h.
    
    $footer = $this->footer ?? $this->getPageFooter();

    // return the array which we usually get via '[$top, $footer] = $S->getPageTopBottom($h, $b)'

    return [$top, $footer];
  }

  /**
   * getPageTop()
   * Get Page Top
   * Gets both the page <head> and <header> sections
   * @return string with the <head>  and <header> (ie banner) sections
   */
  
  public function getPageTop():string {
    // Get the page <head> section

    $head = $this->getPageHead();

    // Get the page's banner section (<header>...</header>)
    
    $banner = $this->getPageBanner();

    return "$head\n$banner";
  }

  /**
   * getPageHead()
   * Get the page <head></head> stuff including the doctype etc.
   * @return string $pageHead
   */

  public function getPageHead():string {
    $this->getPageHead = true; // BLP 2023-01-31 -
    
    $h = new stdClass;

    // use either $h or $this values or a constant

    $dtype = $this->doctype; // note that $this->doctype could also be from mysitemap.json see the constructor.

    $h->base = $this->base ? "<base src='$this->base'>" : null;

    // All meta tags

    $h->title = $this->title ? "<title>$this->title</title>" : null;
    $h->desc = $this->desc ? "<meta name='description' content='$this->desc'>" : null;
    $h->keywords = $this->keywords ? "<meta name='keywords' content='$this->keywords'>" : null;
    $h->copyright = $this->copyright ? "<meta name='copyright' content='$this->copyright'>" : null;
    $h->author = $this->author ? "<meta name='author' content='$this->author'>" : null;
    $h->charset = $this->charset ? "<meta charset='$this->charset'>" : "<meta charset='utf-8'>";
    $h->viewport = $this->viewport ? "<meta name='viewport' content='$this->viewport'>" :
                   "<meta name='viewport' content='width=device-width, initial-scale=1'>";
    $h->canonical = $this->canonical ? "<link rel='canonical' href='$this->canonical'>" : null;
    $h->meta = $this->meta;
    
    // link tags
    
    $h->favicon = $this->favicon ? "<link rel='shortcut icon' href='$this->favicon'>" :
                  "<link rel='shortcut icon' href='https://bartonphillips.net/images/favicon.ico'>";

    if($this->defaultCss === false) { // If this is false NO default
      $h->defaultCss = null;
    } else { // Else either add the value or the default.
      $h->defaultCss = $this->defaultCss ? "<link rel='stylesheet' href='$this->defaultCss' title='default'>" :
                       "<link rel='stylesheet' href='https://bartonphillips.net/css/blp.css' title='default'>";
    }
    
    // $h->css is a special case. If the style is not already there incase the text in <style> tags.

    $h->css = $this->css;
    
    if($this->css && preg_match("~<style~", $this->css) == 0) {
      $h->css = "<style>$this->css</style>";
    }

    // We set the $h->inlineScript here with h_inlineScript
    
    $h->inlineScript = $this->h_inlineScript ? "<script>\n$this->h_inlineScript\n</script>" : null;
    
    // The rest, $h->link, $h->script and $h->extra need the full '<link' or '<script' text.

    $h->script = $this->h_script;
    $h->link = $this->link;
    $h->extra = $this->extra;
    
    $preheadcomment = $this->preheadcomment; // Must be a real html comment ie <!-- ... -->
    $lang = $this->lang ?? 'en';
    $htmlextra = $this->htmlextra; // Must be full html
    
    // If nojquery is true then don't add $trackerStr

    if($this->nojquery !== true) {
      $jQuery = <<<EOF
  <!-- jQuery BLP 2022-12-21 - Latest version -->
  <script src="https://code.jquery.com/jquery-3.6.3.min.js" integrity="sha256-pvPw+upLPUjgMXY0G+8O0xUf+/Im1MZjXxxgOcBQBXU=" crossorigin="anonymous"></script>
  <script src="https://code.jquery.com/jquery-migrate-3.4.0.min.js" integrity="sha256-mBCu5+bVfYzOqpYyK4jm30ZxAZRomuErKEFJFIyrwvM=" crossorigin="anonymous"></script>
  <script>jQuery.migrateMute = false; jQuery.migrateTrace = false;</script>
EOF;
      // Should we use tracker.js? If either noTrack or nodb are set in mysitemap.json then don't

      if($this->noTrack === true || $this->nodb === true) {
        $trackerStr = '';
      } else {
        // BLP 2023-02-20 - trackerLocationJs needs to be part of $this for whatisloaded.class.php.
      
        $this->trackerLocationJs = $this->trackerLocationJs ?? "https://bartonlp.com/otherpages/js/tracker.js";

        $trackerLocation = $this->trackerLocation ?? "https://bartonlp.com/otherpages/tracker.php";
        $beaconLocation = $this->beaconLocation ?? "https://bartonlp.com/otherpages/beacon.php";

        $trackerStr =<<<EOF
  <script data-lastid="$this->LAST_ID" src="$this->trackerLocationJs"></script>
  <script>
    var thesite = "$this->siteName",
    theip = "$this->ip",
    thepage = "$this->self",
    trackerUrl = "$trackerLocation",
    beaconUrl = "$beaconLocation";
  </script>
EOF;
      }
    }
    
    $html = '<html lang="' . $lang . '" ' . $htmlextra . ">"; // stuff like manafest etc.

    // What if headFile is null? Use the Default Head.

    if(!is_null($this->headFile)) {
      if(($p = require_once($this->headFile)) != 1) {
        $pageHeadText = "{$html}\n$p";
      } else {
        throw new SqlException(__CLASS__ . " " . __LINE__ .": $this->siteName, getPageHead() headFile '$this->headFile' returned 1", $this);
      }
    } else {
      // Make a default <head>
      
      $pageHeadText =<<<EOF
$html
<!-- Default Head -->
<head>
$h->title
  <!-- METAs -->
  <meta charset="utf-8"/>
  <meta name="description" content="{$this->desc}"/>
  <!-- local link -->
$this->link
$jQuery
$trackerStr
  <!-- extra -->
$h->extra
  <!-- remote script -->
$h->script
  <!-- inline script -->
$h->inlineScript
  <!-- local css -->
$h->css
</head>
EOF;
    }

    // Default header has < /> elements. If not XHTML we remove the /> at the end!
    $pageHead = <<<EOF
{$preheadcomment}{$dtype}
$pageHeadText
EOF;

    return $pageHead;
  }
  
  /**
   * getPageBanner()
   * Get Page Banner
   * BLP 2022-01-30 -- New logic
   * @return string banner
   */

  public function getPageBanner():string {
    $h = new stdClass;

    // BLP 2022-04-09 - These need to be checked here.
    
    $bodytag = $this->bodytag ?? "<body>";
    $mainTitle = $this->banner ?? $this->mainTitle;

    // BLP 2022-04-09 - if we have nodb or noTrack then there will be no tracker.js or tracker.php
    // so we can't set the images at all.

    $trackerLocation = $this->trackerLocation ?? "https://bartonlp.com/otherpages/tracker.php";

    if($this->nodb !== true && $this->noTrack !== true) {
      // BLP 2022-03-24 -- Add alt and add src='blank.gif'
      // BLP 2022-04-09 - for now I am leaving trackerImg1 and trackerImg2 only on $this.
    
      $image1 = "<img id='logo' data-image='$this->trackerImg1' alt='logo' src=''>";
      $image2 = "<img id='headerImage2' alt='headerImage2' src='$trackerLocation?page=normal&amp;id=$this->LAST_ID&amp;image=$this->trackerImg2'>";
      $image3 = "<img id='noscript' alt='noscriptImage' src='$trackerLocation?page=noscript&amp;id=$this->LAST_ID'>";
    }

    if(!is_null($this->bannerFile)) {
      $pageBannerText = require($this->bannerFile);
    } else {
      // a default banner
      $pageBannerText =<<<EOF
<!-- Default Header/Banner -->
<header>
<div id='pagetitle'>
$mainTitle
</div>
<noscript style="color: red; border: 1px solid black; padding: 10px; font-size: large;">
<strong>Your browser either does not support JavaScripts
or you have JavaScripts disabled.</strong>
</noscript>
</header>

EOF;
    }

    // Return the Banner

    return <<<EOF
$bodytag
$pageBannerText

EOF;
  }

  /**
   * getPageFooter()
   * Get Page Footer
   * @return string
   */
  
  public function getPageFooter():string {
    // BLP 2022-01-02 -- if nofooter is true just return an empty footer

    $b = new stdClass;
    
    if($this->nofooter === true) {
      return <<<EOF
<footer>
</footer>
</body>
</html>
EOF;
    }
    
    // BLP 2022-02-23 -- added the following.
    
    $b->ctrmsg = $this->ctrmsg;
    $b->msg = $this->msg;
    $b->msg1 = $this->msg1;
    $b->msg2 = $this->msg2;
    
    $b->address = $this->noAddress ? null : ($this->address . "<br>");
    $noCopyright = $this->noCopyright;
    $b->copyright = $noCopyright ? null : ($this->copyright . "<br>");
    if(preg_match("~^\d{4}~", $b->copyright) === 1) {
      $b->copyright = "Copyright &copy; $b->copyright";
    }
    
    $b->aboutwebsite = $this->aboutwebsite ??
                       "<h2><a target='_blank' href='https://bartonlp.com/otherpages/aboutwebsite.php?site=$this->siteName&domain=$this->siteDomain'>About This Site</a></h2>";
    
    $b->emailAddress = $this->noEmailAddress ? null : ($this->emailAddress ?? $this->EMAILADDRESS);
    $b->emailAddress = $this->emailAddress ? "<a href='mailto:$this->emailAddress'>$this->emailAddress</a>" : null;

    // Set the $b values from the b_ values
    
    $b->inlineScript = $this->b_inlineScript ? "<script>\n$this->b_inlineScript\n</script>" : null;
    $b->script = $this->b_script;

    // counterWigget is available to the footerFile to use if wanted.
    // BLP 2022-01-02 -- if count is set then use the counter
    
    if($this->noCounter !== true) {
      $counterWigget = $this->getCounterWigget($this->ctrmsg); // ctrmsg may be null which is OK
    }

    // BLP 2021-10-24 -- lastmod is also available to footerFile to use if wanted.

    if($this->noLastmod !== true) {
      $lastmod = "Last Modified: " . date("M j, Y H:i", getlastmod());
    }

    // BLP 2022-01-28 -- add noGeo

    if($this->noGeo !== true) {
      $geo = $this->gioLocation ?? "https://bartonphillips.net/js";
      
      $geo = "<script src='$geo/geo.js'></script>";
    }

    // BLP 2022-04-09 - We can put the footerFile into $b or use it from mysitemap.json
    // If either is set to 'false' then use the default footer, else use $this->footerFile unless
    // it is false.
    
    if($this->footerFile !== false && $this->footerFile !== null) {
      $pageFooterText = require($this->footerFile);
    } else {
      $pageFooterText = <<<EOF
<!-- Default Footer -->
<footer>
$b->aboutwebsite
$counterWigget
$lastmod
$b->script
$b->inlineScript
</footer>
</body>
</html>
EOF;
    }

    return $pageFooterText;
  }

  /**
   * __toString();
   */

  public function __toString() {
    return __CLASS__;
  }

  /**
   * getCounterWigget()
   */

  public function getCounterWigget(?string $msg="Page Hits"):?string {
    // Counter at bottom of page
    // hitCount is updated by 'counter()'

    $hits = number_format($this->hitCount);
        
    // Let the redered appearance be up to the pages css!
    // #F5DEB3==rgb(245,222,179) is 'wheat' for the background
    // rgb(123, 16, 66) is a burgundy for the number
    // We place the counter in the center of the page in a div, in a table
    return <<<EOF
<div id="hitCounter">
$msg
<table id="hitCountertbl">
<tr id='hitCountertr'>
<th id='hitCounterth'>
$hits
</th>
</tr>
</table>
</div>
EOF;
  }

  // ********************************************************************************
  // Private and protected methods.
  // Protected methods can be overridden in child classes so most things that would be private
  // should be protected in this base class

  // **************
  // Start Counters
  // **************

  /**
   * trackbots()
   * Track both bots and bots2
   * This sets $this->isBot unless the 'bots' table is not found.
   * SEE defines.php for the values for isJavaScript.
     CREATE TABLE `bots` (
       `ip` varchar(40) NOT NULL DEFAULT '',
       `agent` text NOT NULL,
       `count` int DEFAULT NULL,
       `robots` int DEFAULT '0',
       `site` varchar(255) DEFAULT NULL, // this is $who which can be multiple sites seperated by commas.
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
     ) ENGINE=InnoDB DEFAULT CHARSET=latin1
     Things enter the bots table from 'robots.txt', 'Sitemap.xml' and BOTS_CRON_ZERO from checktracker2.php.
     Also if we have found BOTS_MATCH or BOTS_TABLE we enter it here.
   */

  protected function trackbots():void {
    if(!empty($this->foundBotAs)) {
      $agent = $this->agent;

      try {
        $this->query("insert into $this->masterdb.bots (ip, agent, count, robots, site, creation_time, lasttime) ".
                     "values('$this->ip', '$agent', 1, " . BOTS_SITECLASS . ", '$this->siteName', now(), now())");
      } catch(SqlException $e) {
        if($e->getCode() == 1062) { // duplicate key
          // We need the site info first. This can be one or multiple sites seperated by commas.

          $this->query("select site from $this->masterdb.bots where ip='$this->ip' and agent='$agent'");

          $who = $this->fetchrow('num')[0]; // get the site which could have multiple sites seperated by commas.

          // Look at who (the haystack) and see if siteName is there. If it is not there this
          // returns false.

          if(strpos($who, $this->siteName) === false) {
            $who .= ", $this->siteName";
          }

          $this->query("update $this->masterdb.bots set robots=robots | " . BOTS_SITECLASS . ", site='$who', count=count+1, lasttime=now() ".
                       "where ip='$this->ip' and agent='$agent'");
        } else {
          throw new SqlException(__CLASS__ . " " . __LINE__ . ":$e", $this);
        }
      }

      // Now do bots2

      $this->query("insert into $this->masterdb.bots2 (ip, agent, page, date, site, which, count, lasttime) ".
                   "values('$this->ip', '$agent', '$this->self', now(), '$this->siteName', " . BOTS_SITECLASS . ", 1, now())".
                   "on duplicate key update count=count+1, lasttime=now()");
    }
  }
   
  /**
   * tracker()
   * track if java script or not.
   * CREATE TABLE `tracker` (
   *  `id` int NOT NULL AUTO_INCREMENT,
   *  `botAs` varchar(30) DEFAULT NULL,
   *  `site` varchar(25) DEFAULT NULL,
   *  `page` varchar(255) NOT NULL DEFAULT '',
   *  `finger` varchar(50) DEFAULT NULL,
   *  `nogeo` tinyint(1) DEFAULT NULL,
   *  `ip` varchar(40) DEFAULT NULL,
   *  `agent` text,
   *  `starttime` datetime DEFAULT NULL,
   *  `endtime` datetime DEFAULT NULL,
   *  `difftime` varchar(20) DEFAULT NULL,
   *  `isJavaScript` int DEFAULT '0',
   *  `lasttime` datetime DEFAULT NULL,
   *  PRIMARY KEY (`id`),
   *  KEY `site` (`site`),
   *  KEY `ip` (`ip`),
   *  KEY `lasttime` (`lasttime`),
   *  KEY `starttime` (`starttime`)
   * ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3
   */

  protected function tracker():void {
    $agent = $this->agent;

    // BLP 2021-12-28 -- Explanation.
    // Here we set $java (isJavaScript) to 0x8000 or zero.
    // We then look at isBot and if nothing was found in the bots table and the regex did not
    // match something in the list then isJavaScript will be zero.
    // The visitor was probably a bot and will be added to the bots table as a 0x100 by the cron
    // job checktracker2.php and to the bots2 table as 16. The bot was more than likely curl,
    // wget, python or the like that sets its user-agent to something that would not trigger my
    // regex. Such visitor leave very little footprint.

    $java = $this->isMe() ? TRACKER_ME : TRACKER_ZERO;

    if($this->isBot) { // can NEVER be me!
      $java = TRACKER_BOT; // This is the robots tag
    }

    // The primary key is id which is auto incrementing so every time we come here we create a
    // new record.

    // Add foundBotAs to end of agent.

    if($this->foundBotAs != '') {
      $tmp = preg_replace("~,~", "<br>", $this->foundBotAs);
      $agent .= $this->foundBotAs ? '<br><span class="botas">' . $tmp . '</span>' : '';
    }
    $agent = $this->escape($agent);

    $this->query("insert into $this->masterdb.tracker (botAs, site, page, ip, agent, starttime, isJavaScript, lasttime) ".
                 "values('$this->foundBotAs', '$this->siteName', '$this->self', '$this->ip','$agent', now(), $java, now())");

    $this->LAST_ID = $this->getLastInsertId();
  }

  /**
   * counter()
   * This is the page counter feature in the footer
   * By default this uses a table 'counter' with 'filename', 'count', and 'lasttime'.
   *  'filename' is the primary key.
   * counter() updates $this->hitCount
   */

  protected function counter():void {
    $filename = $this->self; // get the name of the file

    try {
      $this->query("insert into $this->masterdb.counter (site, filename, count, lasttime) values('$this->siteName', '$filename', 1, now())");
    } catch(SqlException $e) {
      if($e->getCode() != 1062) {
        throw new SqlException(__CLASS__ . " " . __LINE__ . ":$e", $this);
      }
    }
    
    // Is it me?
    
    if(!$this->isMe()) { // No it is NOT me.
      // realcnt is ONLY NON BOTS

      $realcnt = $this->isBot ? 0 : 1;

      // count is total of ALL hits that are NOT ME!

      $sql = "update $this->masterdb.counter set count=count+1, realcnt=realcnt+$realcnt, lasttime=now() ".
             "where site='$this->siteName' and filename='$filename'";

      $this->query($sql);
    }

    // Now retreive the hit count value after it may have been incremented above. NOTE, I am NOT
    // included here.

    $sql = "select realcnt from $this->masterdb.counter where site='$this->siteName' and filename='$filename'";

    $this->query($sql);

    $this->hitCount = ($this->fetchrow('num')[0]) ?? 0; // This is the number of REAL (non BOT) accesses and NON Me.
  }

  /**
   * counter2
   * count files accessed per day
   * Primary key is 'site', 'date', 'filename'.
   */
  
  protected function counter2():void {
    [$real, $bot] = $this->isBot ? [0,1] : [1,0];

    $sql = "insert into $this->masterdb.counter2 (site, date, filename, `real`, bots, lasttime) ".
           "values('$this->siteName', now(), left('$this->self', 254), $real , $bot, now()) ".
           "on duplicate key update `real`=`real`+$real, bots=bots+$bot, lasttime=now()";

    $this->query($sql);
  }

  /*
   * daycount()
   * This creates the very first record then if this is a BOT it updates 'bots' and 'lasttime'.
   * We only count robots here. Reals are counted via the AJAX from tracker.js by tracker.php and beacon.php
     CREATE TABLE `daycounts` (
      `site` varchar(50) NOT NULL DEFAULT '',
      `date` date NOT NULL,
      `real` int DEFAULT '0',
      `bots` int DEFAULT '0',
      `visits` int DEFAULT '0',
      `lasttime` datetime DEFAULT NULL,
      PRIMARY KEY (`site`,`date`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;
   */
  
  protected function daycount():void {
    try {
      // This will create the very first daycounts entry for the day.
      
      $this->query("insert into $this->masterdb.daycounts (site, `date`, lasttime) values('$this->siteName', current_date(), now())");
    } catch(SqlException $e) {
      if($e->getCode() != 1062) { // I expect this to fail for dupkey after the first insert per day.
        throw new SqlException(__CLASS__ . "$e", $this);
      }
    }
    
    if($this->isBot === false) return; // If NOT a bot return.

    // Only count bots here.
    
    $this->query("update $this->masterdb.daycounts set bots=bots+1, lasttime=now() where date=current_date() and site='$this->siteName'");
  }
  
  /**
   * logagent()
   * Log logagent
   * This counts everyone!
   * logagent is used by 'analysis.php'
   */
  
  protected function logagent():void {
    // site, ip and agent(256) are the primary key. Note, agent is a text field so we look at the
    // first 256 characters here (I don't think this will make any difference).

    $sql = "insert into $this->masterdb.logagent (site, ip, agent, count, created, lasttime) " .
           "values('$this->siteName', '$this->ip', '$this->agent', '1', now(), now()) ".
           "on duplicate key update count=count+1, lasttime=now()";

    $this->query($sql);
  }

  // ************
  // End Counters
  // ************
} // End of Class
