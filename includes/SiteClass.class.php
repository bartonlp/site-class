<?php
// SITE_CLASS_VERSION must change when the GitHub Release version changes.
// BLP 2022-06-14 - Moved setSiteCookie() to Database.
// BLP 2022-05-26 - SiteClass now extends Database which extends dbAbstract.
// daycount(), checkIfBot(), trackBots() and tracker have been completley reworked.

define("SITE_CLASS_VERSION", "3.3.1"); // BLP 2022-06-14 - 

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
  private $hitCount = null;

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
   * Some values are added to $s and then we call parent::__constructor with $s and true (isSiteClass).
   */
  
  public function __construct(object $s) {
    ErrorClass::init(); // Make sure this is done

    $s->ip = $_SERVER['REMOTE_ADDR'];
    $s->agent = $_SERVER['HTTP_USER_AGENT'] ?? ''; // BLP 2022-01-28 -- CLI agent is NULL so make it blank ''
    // self is like '/tracker.php'
    // requestUri is like '/tracker.php?page=script&id=6218713&image=/images/blp-image.png'
    $s->self = htmlentities($_SERVER['PHP_SELF']); // BLP 2021-12-20 -- add htmlentities to protect against hacks.
    $s->requestUri = $_SERVER['REQUEST_URI']; // BLP 2021-12-30 -- change from $this->self

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
        // updated (unless the counter file does not exist).

        $this->counter(); // in 'masterdb' database. Does not count Me but always set $this->hitCount.

        if(!$this->isMe()) { //If it is NOT ME do counter2 and daycount
          // These are all checked for existance in the database in the functions and also the nodb
          // is checked and if true we return at once.

          $this->counter2(); // in 'masterdb' database
          $this->daycount(); // in 'masterdb' database
        }
      }
    }
    //error_log("SiteClass this: " . print_r($this, true));
  }

  /**
   * isMe()
   * Check if this access is from ME
   * @return true if $this->ip == $this->myIp else false!
   */

  public function isMe():bool {
    return (array_intersect([$this->ip], $this->myIp)[0] === null) ? false : true;
  }

  /**
   * getVersion()
   * @return string version number
   */

  public function getVersion():string {
    return SITE_CLASS_VERSION;
  }

  /**
   * getIp()
   * Get the ip address
   * @return int ip address
   */

  public function getIp():string {
    return $this->ip;
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

  public function getPageTopBottom(?object $h=null, ?object $b=null):array {
    $h = $h ?? new stdClass;
        
    // Do getPageTop and getPageFooter

    $top = $this->getPageTop($h);

    // BLP 2022-04-09 - We can pass in a footer via $h.
    
    $footer = $h->footer ?? $this->getPageFooter($b);

    // return the array which we usually get via '[$top, $footer] = $S->getPageTopBottom($h, $b)'

    return [$top, $footer];
  }

  /**
   * getPageTop()
   * Get Page Top
   * Gets both the page <head> and <header> sections
   * @param ?object $h
   * @return string with the <head>  and <header> (ie banner) sections
   */
  
  public function getPageTop(?object $h=null):string {
    $h = $h ?? new stdClass;
    
    // Get the page <head> section

    $head = $this->getPageHead($h);

    // Get the page's banner section (<header>...</header>)
    
    $banner = $this->getPageBanner($h);

    return "$head\n$banner";
  }

  /**
   * getPageHead()
   * Get the page <head></head> stuff including the doctype etc.
   * @param object $h
   * @return string $pageHead
   */

  public function getPageHead(?object $h=null):string {
    $h = $h ?? new stdClass;

    // use either $h or $this values or a constant

    $dtype = $h->doctype ?? $this->doctype; // note that $this->doctype could also be from mysitemap.json see the constructor.

    $h->base = ($h->base = ($h->base ?? $this->base)) ? "<base src='$h->base'>" : null;

    // All meta tags

    $h->title = ($h->title = ($h->title ?? $this->title)) ? "<title>$h->title</title>" : null;
    $h->desc = ($h->desc = ($h->desc ?? $this->desc)) ? "<meta name='description' content='$h->desc'>" : null;
    $h->keywords = ($h->keywords = ($h->keywords ?? $this->keywords)) ? "<meta name='keywords' content='$h->keywords'>" : null;
    $h->copyright = ($h->copyright = ($h->copyright ?? $this->copyright)) ? "<meta name='copyright' content='$h->copyright'>" : null;
    $h->author = ($h->author = ($h->author ?? $this->author)) ? "<meta name='author' content='$h->author'>" : null;
    $h->charset = ($h->charset = ($h->charset ?? $this->charset)) ? "<meta charset='$h->charset'>" : "<meta charset='utf-8'>";
    $h->viewport = ($h->viewport = ($h->viewport ?? $this->viewport)) ?
                   "<meta name='viewport' content='$h->viewport'>" : "<meta name='viewport' content='width=device-width, initial-scale=1'>";
    $h->canonical = ($h->canonical = ($h->canonical ?? $this->canonical)) ? "<link rel='canonical' href='$h->canonical'>" : null;

    // link tags
    
    $h->favicon = ($h->favicon = ($h->favicon ?? $this->favicon ?? 'https://bartonphillips.net/images/favicon.ico')) ?
                  "<link rel='shortcut icon' href='$h->favicon'>" : null;

    $h->defaultCss = ($h->defaultCss = ($h->defaultCss ?? $this->defaultCss)) ?
                     (($h->defaultCss !== true ) ? "<link rel='stylesheet' href='$h->defaultCss' title='default'>" : null)
                      : "<link rel='stylesheet' href='https://bartonphillips.net/css/blp.css' title='default'>";

    // $h->css is a special case. If the style is not already there incase the text in <style> tags.

    if($h->css && preg_match("~<style~", $h->css) == 0) {
      $h->css = "<style>$h->css</style>";
    }

    // $h->inlineScript is new. Incase it in script tags

    $h->inlineScript = $h->inlineScript ? "<script>$h->inlineScript</script>" : null;
    
    // The rest, $h->link, $h->script and $h->extra need the full '<link' or '<script' text.
    
    $h->preheadcomment = $h->preheadcomment ?? $this->preheadcomment; // Must be a real html comment ie <!-- ... -->
    $h->lang = $h->lang ?? $this->lang ?? 'en';
    $h->htmlextra = $h->htmlextra ?? $this->htmlextra; // Must be full html
    
    $h->headFile = $h->headFile ?? $this->headFile;
    $h->nojquery = $h->nojquery ?? $this->nojquery; // BLP 2022-04-09 - new

    // If nojquery is true then don't add $trackerStr

    if($h->nojquery !== true) {
      $jQuery = <<<EOF
  <!-- jQuery -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js" integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=" crossorigin="anonymous"></script>
  <script src="https://code.jquery.com/jquery-migrate-3.3.2.min.js"
    integrity="sha256-Ap4KLoCf1rXb52q+i3p0k2vjBsmownyBTE1EqlRiMwA=" crossorigin="anonymous"></script>
  <script>jQuery.migrateMute = false; jQuery.migrateTrace = false;</script>
EOF;
      // Should we use tracker.js? If either noTrack or nodb are set in mysitemap.json then don't

      $h->noTrack = $h->noTrack ?? $this->noTrack;
      $h->nodb = $h->nodb ?? $this->nodb;

      if($h->noTrack === true || $h->nodb === true) {
        $trackerStr = '';
      } else {
        $trackerStr =<<<EOF
<script data-lastid="$this->LAST_ID" src="https://bartonphillips.net/js/tracker.js"></script>
<script>var thesite = "$this->siteName", theip = "$this->ip";</script>
EOF;
      } 
    }
    
    $html = '<html lang="' . $h->lang . '" ' . $h->htmlextra . ">"; // stuff like manafest etc.

    // What if headFile is null? Use the Default Head.

    if(!is_null($h->headFile)) {
      if(($p = require_once($h->headFile)) != 1) {
        $pageHeadText = "{$html}\n$p";
      } else {
        throw new Exception(__CLASS__ . " " . __LINE__ .": $this->siteName, getPageHead() headFile '$this->headFile' returned 1");
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
  <meta name="description" content="{$h->desc}"/>
  <!-- local link -->
$h->link
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
{$h->preheadcomment}{$dtype}
$pageHeadText
EOF;

    return $pageHead;
  }
  
  /**
   * getPageBanner()
   * Get Page Banner
   * BLP 2022-01-30 -- New logic
   * @param ?object $h
   * @return string banner
   */

  public function getPageBanner(?object $h=null):string {
    $h = $h ?? new stdClass;

    // BLP 2022-04-09 - These need to be checked here.
    
    $h->nodb = $h->nodb ?? $this->nodb;
    $h->noTrack = $h->noTrack ?? $this->noTrack;

    $h->bannerFile = $h->bannerFile ?? $this->bannerFile;
    $bodytag = $h->bodytag ?? $this->bodytag ?? "<body>";
    $mainTitle = $h->banner ?? $this->mainTitle;

    // BLP 2022-04-09 - if we have nodb or noTrack then there will be no tracker.js or tracker.php
    // so we can't set the images at all.
    
    if($h->nodb !== true && $h->noTrack !== true) {
      // BLP 2022-03-24 -- Add alt and add src='blank.gif'
      // BLP 2022-04-09 - for now I am leaving trackerImg1 and trackerImg2 only on $this.
    
      $image1 = "<img id='logo' data-image='$this->trackerImg1' alt='logo' src='https://bartonphillips.net/images/blank.gif'>";
      $image2 = "<img id='headerImage2' alt='headerImage2' src='https://bartonphillips.net/tracker.php?page=normal&amp;id=$this->LAST_ID&amp;image=$this->trackerImg2'>";
      $image3 = "<img id='noscript' alt='noscriptImage' src='https://bartonphillips.net/tracker.php?page=noscript&amp;id=$this->LAST_ID'>";
    }
    
    if(!is_null($this->bannerFile)) {
      $pageBannerText = require($h->bannerFile);
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
   * @param object $b. 
   * @return string
   */
  
  public function getPageFooter(?object $b=null):string {
    // BLP 2022-01-02 -- if nofooter is true just return an empty footer

    $b = $b ?? new stdClass;
    
    if(($b->nofooter ?? $this->nofooter) === true) {
      return <<<EOF
<footer>
</footer>
</body>
</html>
EOF;
    }
    
    // BLP 2022-02-23 -- added the following.
    
    $b->ctrmsg = $b->ctrmsg ?? $this->ctrmsg;
    $b->msg = $b->msg ?? $this->msg;
    $b->msg1 = $b->msg1 ?? $this->msg1;
    $b->msg2 = $b->msg2 ?? $this->msg2;
    
    $b->address = (($b->noAddress ?? $this->noAddress) ? null : ($b->address ?? $this->address)) . "<br>";
    $b->noCopyright = $b->noCopyright ?? $this->noCopyright;
    $b->copyright = $b->noCopyright ? null : ($b->copyright ?? $this->copyright) . "<br>";
    if(preg_match("~^\d{4}~", $b->copyright) === 1) {
      $b->copyright = "Copyright &copy; $b->copyright";
    }
    $b->aboutwebsite = $b->aboutwebsite ??
                       $this->aboutwebsite ??
                       "<h2><a target='_blank' href='https://bartonphillips.net/aboutwebsite.php?site=$this->siteName&domain=$this->siteDomain'>About This Site</a></h2>";
    
    $b->emailAddress = ($b->noEmailAddress ?? $this->noEmailAddress) ? null : ($b->emailAddress ?? $this->EMAILADDRESS);
    $b->emailAddress = $b->emailAddress ? "<a href='mailto:$b->emailAddress'>$b->emailAddress</a>" : null;
    $b->inlineScript = $b->inlineScript ? "<script>$b->inlineScript</script>" : null;
    
    // counterWigget is available to the footerFile to use if wanted.
    // BLP 2022-01-02 -- if count is set then use the counter
    
    if(($b->noCounter ?? $this->noCounter) !== true) {
      $counterWigget = $this->getCounterWigget($b->ctrmsg); // ctrmsg may be null which is OK
    }
    
    // BLP 2021-10-24 -- lastmod is also available to footerFile to use if wanted.

    if(($b->noLastmod ?? $this->noLastmod) !== true) {
      $lastmod = "Last Modified: " . date("M j, Y H:i", getlastmod());
    }

    // BLP 2022-01-28 -- add noGeo
    
    if(($b->noGeo ?? $this->noGeo) !== true) {
      $geo = "<script src='https://bartonphillips.net/js/geo.js'></script>";
    }

    // BLP 2022-04-09 - We can put the footerFile into $b or use it from mysitemap.json
    // If either is set to 'false' then use the default footer, else use $this->footerFile unless
    // it is false.
    
    if(($b->footerFile ?? $this->footerFile) !== false && $this->footerFile !== null) {
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
    if($this->hitCount) {
      $hits = number_format($this->hitCount);
    }

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

  /**
   * checkIfBot() before we do any of the other protected functions.
   * Checks if the user-agent looks like a bot or if the ip is in the bots table
   * or previous tracker records had something other than zero or 0x2000.
   * Set $this->isBot true/false.
   * return nothing.
   * SEE defines.php for the values for TRACKER_BOT, BOTS_SITECLASS
   * $this-isBot is false or there is no entry in the bots table
   */

  protected function checkIfBot():void {
    $this->isBot = false;
    
    if($this->isMe()) {
      return; 
    }

    if(($x = preg_match("~\+*https?://|@|bot|spider|scan|HeadlessChrome|python|java|wget|nutch|perl|libwww|lwp-trivial|curl|PHP/|urllib|".
                        "crawler|GT::WWW|Snoopy|MFC_Tear_Sample|HTTP::Lite|PHPCrawl|URI::Fetch|Zend_Http_Client|".
                        "http client|PECL::HTTP~i", $this->agent)) === 1) { // 1 means a match
      $this->isBot = true;
      $this->foundBotAs = BOTAS_MATCH; // "preg_match";
      return;
    } elseif($x === false) { // false is error
      // This is an unexplained ERROR
      throw new Exceiption(__CLASS__ . " " . __LINE__ . ": preg_match() returned false");
    }

    // If $x was 1 or false we have returned with true and BOTAS_MATCH or we threw an exception.
    // $x is zero so there was NO match.

    if($this->query("select robots from $this->masterdb.bots where ip='$this->ip'")) { // Is it in the bots table?
      // Yes it is in the bots table.

      $tmp = '';

      while($robots = $this->fetchrow('num')[0]) {
        if($robots & BOTS_ROBOTS) {
          $tmp = "," . BOTAS_ROBOT;
        }
        if($robots & BOTS_SITEMAP) {
          $tmp .= "," . BOTAS_SITEMAP;
        }
        if($robots & BOTS_CRON_ZERO) {
          $tmp .= "," . BOTAS_ZERO;
        }
        if($tmp != '') break;
      }
      if($tmp != '') {
        $this->foundBotAs = BOTAS_TABLE . $tmp; //'bots table' plus $tmp;
        $this->isBot = true; // BOTAS_TABLE plus robot and/or sitemap
      } else {
        $this->foundBotAs = BOTAS_NOT;
        $this->isBot = false;
      }
      //error_log("SiteClass checkIfBot: foundBotAs=$this->foundBotAs, ip=$this->ip, agent=$this->agent, " . __LINE__);
      return;
    }

    // The ip was NOT in the bots table either.

    $this->foundBotAs = BOTAS_NOT; // not a bot
    $this->isBot = false;
  }

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
    if($this->foundBotAs == BOTAS_MATCH || (strpos($this->foundBotAs, BOTAS_TABLE) !== false)) { // This has been set by checkIfBot()
      //error_log("SiteClass trackbots: $this->foundBotAs, $this->ip, $this->agent, $this->siteName");

      $agent = $this->agent;

      try {
        $this->query("insert into $this->masterdb.bots (ip, agent, count, robots, site, creation_time, lasttime) ".
                     "values('$this->ip', '$agent', 1, " . BOTS_SITECLASS . ", '$this->siteName', now(), now())");
      } catch(Exception $e) {
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
          throw new Exception(__CLASS__ . " " . __LINE__ . ":$e");
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
      $tmp = rtrim($this->foundBotAs, ',');
      $tmp = preg_replace("~,~", "<br>", $tmp);
      //error_log("SiteClass tracker: $this->siteName, ip=$this->ip, foundBotAs=$this->foundBotAs, java=" . dechex($java) . ", " . __LINE__);

      $agent .= $this->foundBotAs ? '<br><span class="botas">' . $tmp . '</span>' : '';
    }
    $agent = $this->escape($agent);

    $this->query("insert into $this->masterdb.tracker (botAs, site, page, ip, agent, starttime, isJavaScript, lasttime) ".
                 "values('$this->foundBotAs', '$this->siteName', '$this->self', '$this->ip','$agent', now(), $java, now())");

    $this->LAST_ID = $this->getLastInsertId();
  }

  /**
   * updatemyip()
   * This is NOT done if we are not using a database or isMe() is false. That is it is NOT me.
   */

  protected function updatemyip():void {
    if($this->ip == DO_SERVER || $this->isMe() === false) {
      // If it is my server or it is not ME. If it is my server we don't look at the OR.
      return; // This is not me.
    }

    // BLP 2022-01-16 -- NOTE there are only two places where the ip address is added:
    // bartonphillips.com/register.php and bonnieburch.com/addcookie.com.
    
    $sql = "update $this->masterdb.myip set count=count+1, lasttime=now() where myIp='$this->ip'";

    if(!$this->query($sql)) {
      $this->db->debug("SiteClass $this->siteName: update of myip failed, ip: $this->ip, " .__LINE__, true); // this should not happen
    }
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

    // Is it me?
    
    if(!$this->isMe()) { // No it is NOT me.
      // realcnt is ONLY NON BOTS

      $realcnt = $this->isBot ? 0 : 1;

      // count is total of ALL hits that are NOT ME!

      $sql = "insert into $this->masterdb.counter (site, filename, count, realcnt, lasttime) ".
             "values('$this->siteName', '$filename', '1', '$realcnt', now()) ".
             "on duplicate key update count=count+1, realcnt=realcnt+$realcnt, lasttime=now()";

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
    } catch(Exception $e) {
      if($e->getCode() != 1062) {
        throw new Exception(__CLASS__ . "$e");
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
