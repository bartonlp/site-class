<?php
// SITE_CLASS_VERSION must change when the GitHub Release version changes.
// BLP 2022-01-24 -- getPageTop() use title if not banner
// BLP 2022-01-04 -- getPageHead() $h->title. Change final default from siteName to self
// BLP 2022-01-02 -- in getPageFooter() add/update use of nofooter, count, noLastmod and footerFile.
// BLP 2021-12-31 -- Before checking table 'myip' make sure that the database user is 'barton'.
// Add __LINE__ to all throw().
// Rename setmyip() to updatemyip().
// Change counter2 table to `real` from 'count' and make changes to counter2().
// BLP 2021-12-30 -- Removed depreciated myUrl logic. I no longer use myUrl.json in
// bartonphillipsnet any more. Updated SITE_CLASS_VERSION.
// Changed $this->requestUri from $this->self to $_SERVER['REQUEST_URI'] and changed all the places
// where $this->requestUri was used to $this->self.
// BLP 2021-12-28 -- Added $b->script in default footer in getPageFooter(). Put not $this->count in side not $this->noTrack
// Add explanation of how zero gets into isJavaScript in tracker.
// Also remove $ok and use fetchrow('num')[0] instead in table checks.
// Also added __LINE__ to all debug messages.
// BLP 2021-12-23 -- add just plain 'bot', 'spider' and "HeadlessChrome" to list.
// BLP 2021-12-20 -- tracker() makes isJavaScript = 0x8000 if it is isMe() true.
// BLP 2021-12-20 -- setSiteCookie() add defaults for $secure, $httponly and $samesite
// BLP 2021-12-16 -- changed bots2 which to 8.
// BLP 2021-12-13 -- add $S->refid
// BLP 2021-10-24 -- Database checks for isSiteClass and if NOT set it sets ip and agent.
// BLP 2021-10-24 -- move $this->agent up with $this-ip and make a not by $this->escape() that it
// is only available after Datebase is loaded. Removed escape() $agent because if nodb is set it is already escaped
// BLP 2021-10-24 -- add $lastmod to getPageFooter().
// BLP 2021-10-13 -- Major rework of getPage*(). Only pass $h in. It has everything along with
// $this from mysitemap.json. Logic determins what to use.
// BLP 2021-10-06 -- in setSiteCookie() changed httponly to false. Use $this->siteDomain for ref.
// see comment
// BLP 2021-09-24 -- guard myip below from other users. See the 'test' database and 'test' user.
// I added a check for $this->dbinfo->user equal 'barton' and $this->noTrack not true.
// Also added $this->noTrack to the check for $this->count before doing the counter().
// BLP 2021-09-24 -- add if($this->nodb !== true) to BLP 2021-09-15
// BLP 2021-09-15 -- use 'myip' table to extend what might be in myUri. We could stop using myUri
// in mysitemap.json if we want to. I have actually commented myUri out of all the mysitemap.json
// files.
// BLP 2021-09-02 -- Using PHP_MAJOR_VERSION looked like a good idea but it just does not work. If
// I use the null coalesing oporator I get a parser error because the code is parsed before wht
// Version can be checked. I am removing the PHP_MAJOR_VERSION code.
// BLP 2021-09-02 -- add PHP_MAJOR_VERSION to deferentiate between PHP 5 and PHP 7 (search for
// PHP_MAJOR_VERION)
// BLP 2021-03-22 -- add 'options' to setSiteCookie().
// BLP 2021-03-22 -- remove daycountwhat from constructor values amd $inc from daycount().
// BLP 2021-03-16 -- removed 'member' logic from counter2(). Remove 'member' logic from daycount().
// Remove $this-id from all functions. ErrorClass::setDevelopment(true) now sets
// $noEmailErrs also to true. This can be overriden by settin ErrorClass::setNoEmailErrs(false)
// after setting development.
// BLP 2021-03-11 -- add escape for agent.
// BLP 2021-03-09 -- removed logagent2 logic.
// BLP 2021-03-09 -- added nodb flag to updatemyip(). 
// BLP 2021-02-28 -- use $_SERVER['SERVER_NAME'] instead of $this->siteDomain.
// BLP 2018-07-02 -- Change 'isMe()' logic to use array_intersect() and then use 'isMe()' instead of old logic.
// BLP 2018-07-01 -- Added logic to look at bartonphillips.net if myUri is a string and starts with
// http. Also add logic to add a date to copyright.
// BLP 2018-06-10 -- If our ip is in myIp then WE ARE NOT A BOT!
// BLP 2018-06-08 -- fix $agent in trackbots()
// BLP 2018-04-20 -- move the init section
// BLP 2017-11-01 -- counter2 left() for filename
// BLP 2016-12-20 -- in tracker() add refid=$_SERVER['HTTP_REFERER'] and alter table tracker change
// refid to varchar(255).
// BLP 2016-11-27 -- changed the sense of $this->myIp and $this->myUri. Now $this->myUri can be an
// object and $this->myIp can be an array.

define("SITE_CLASS_VERSION", "3.0.12"); // BLP 2022-01-28 -- 

// One class for all my sites
// This version has been generalized to not have anything about my sites in it!
/**
 * SiteClass
 *
 * @package SiteClass
 * @author Barton Phillips <barton@bartonphillips.com>
 * @link http://www.bartonphillips.com
 * @copyright Copyright (c) 2010, Barton Phillips
 * @license  MIT
 */

/**
 * @package SiteClass
 * This class can be extended to handle special issues and add methods.
 */

class SiteClass extends dbAbstract {
  private $hitCount = null;

  public $isSiteClass = true; // True if we instantiated SiteClass so Database knows that we have.
  
  // Give these default values incase they are not mentioned in mysitemap.json.
  // Note they could still be null from mysitemap.json!
  
  public $count = true;
  public $countMe = false;
  
  // Current Doc Type
  public $doctype = "<!DOCTYPE html>";

  /**
   * Constructor
   *
   * @param object $s. If this is not an object we get an error!
   *  The $s is almost always from mysitemap.json.
   *  Once in a while they can be changed by the program instantiating the class.
   *  'count' is default true and 'countMe' is default false (above).
   *  The rest of the values are 'null' if not specifically set by $s (mysitemap.json).
   *  $s has the values from $_site = require_once(getenv("SITELOADNAME"));
   *  which uses siteload.php to gets values from mysitemap.json.
   */
  
  public function __construct(object $s) {
    ErrorClass::init(); // BLP 2014-12-31 -- Make sure this is done

    // Initialize a few items. NOTE they could be changed once $s is processed.
    
    $this->ip = $_SERVER['REMOTE_ADDR'];
    $this->agent = $_SERVER['HTTP_USER_AGENT'] ?? ''; // BLP 2022-01-28 -- CLI agent is NULL so make it blank ''
    $this->self = htmlentities($_SERVER['PHP_SELF']); // BLP 2021-12-20 -- add htmlentities to protect against hacks.
    $this->refid = $_SERVER['HTTP_REFERER'] ?? ''; // BLP 2022-01-28 -- CLI refid is NULL so make it blank ''
    $this->requestUri = $_SERVER['REQUEST_URI']; // BLP 2021-12-30 -- change from $this->self
    
    // BLP 2021-03-06 -- our server 'bartonlp.org' is in New York.
    
    date_default_timezone_set("America/New_York");

    // Put the stuff from $s into $this
    
    foreach($s as $k=>$v) {
      $this->$k = $v;
    }
        
    // BLP 2018-07-01 -- Add the date to the copyright notice if one exists

    if($this->copyright) {
      $this->copyright = date("Y") . " $this->copyright";
    }

    // If no database in mysitemap.json set everything so the database is not loaded.
    // BLP 2021-10-24 -- combine with else.
    
    if($this->nodb === true || is_null($this->dbinfo)) {
      // nodb === true so don't do any database stuff
      $this->nodb = true;
      $this->count = $this->countMe = false;
      $this->noTrack = true; // BLP 2021-09-24 -- 
    } else {
      // BLP 2021-03-11 -- add escape for HTTP_USER_AGENT to cope with ' etc.
      // If we have already instantiated a Database the $this->db will not be null so don't do the
      // Database again!
      // instantiate the Database. Pass Everything on to Database

      $this->db = new Database($this);
      // BLP 2021-10-24 -- NOTE escape is part of mysqli which is only instantiated after Database.
      // if CLI then it is '' blank not null from above.
      $this->agent = $this->escape($this->agent);
    }
    
    // BLP 2021-09-15 -- add items to myIp from the myip table.
    // See the bartonphillips.com/ index.php, index.i.php and register.php files
    // for how the myip table is updated.

    // BLP 2021-09-24 -- The masterdb must be owned by 'barton'. That is the dbinfo->user must be
    // 'barton'. There is one database where this is not true. The 'test' database has a
    // mysitemap.json file that has dbinfo->user as 'test'. It is in the
    // bartonphillips.com/exmples.js/user-test directory.
    // In general all databases that are going to do anything with counters etc. must have a user
    // of 'barton' or set $this->noTrack to true. Still the program can NOT do any calls via masterdb!

    if($this->dbinfo->user == "barton" && $this->nodb !== true) { // BLP 2021-12-31 -- make sure its the 'barton' user!
      $this->query("select count(*) from information_schema.tables ".
                   "where (table_schema = '$this->masterdb') and (table_name = 'myip')");

      if($this->fetchrow('num')[0]) {
        if($this->dbinfo->user == 'barton' && $this->nodb !== true && $this->noTrack !== true) { // BLP 2021-09-24 -- add full list
          $sql = "select myIp from $this->masterdb.myip";
          $this->query($sql);
          while([$ip] = $this->fetchrow('num')) {
            $this->myIp[] = $ip;
          }
        }
      } else {
        $this->debug("$this->siteName: $this->self: table myip does not exist in the $this->masterdb database: ". __LINE__);
      }
    }
    
    //error_log("SiteClass: myIp: " . print_r($this->myIp, true));
    
    // These all use database 'barton' ($this->masterdb)
    // and are always done regardless of 'count' and 'countMe'!
    // These all check $this->nodb first and return at once if it is true.
    
    if($this->noTrack !== true) {
      // checkIfBots() must be done first
      $this->checkIfBot(); // This set $this->isBot. Does a isMe() so I never get set as a bot!
      $this->trackbots();  // both 'bots' and 'bots2'. This also does a isMe() so never get put into the 'bots*' tables.
      $this->tracker();    // This logs Me and everybody else!
      $this->logagent();   // This logs Me and everybody else!
      $this->updatemyip(); // Update myip if it is ME

      // If 'count' is false we don't do these counters

      if($this->count) {
        // Get the count for hitCount. This is done even if countMe is false. The hitCount is always
        // updated (unless the counter file does not exist).
        // That is why it is here rather than after the countMe test below!

        // BLP 2021-03-27 -- NOTE: counter() checks for not isMe() and countMe is false.
        // So it does NOT count Me but it still gets the 'realcnt' even if the count was NOT done.
        // The 'realcnt' is placed in '$this->hitCount'.

        $this->counter(); // in 'masterdb' database. Does not count Me but always set $this->hitCount.

        // BLP 2021-08-20 -- Change countMe to not true because countMe could be null.
        // If this is me and $countMe is false (default is false) then don't count.
        //     isMe() && countMe!==true
        // not (true  && false)  == true, it is me and countMe=true 
        // not (true  && true)   == false,  it is me and countMe=false (or null)
        // not (false && false)  == true,  it isn't me and countMe=true
        // not (false && true)   == true,  it isn't me and countMe=false (or null)
        // So basically I only want to NOT do the counter2 and daycount if it is Me and countMe is
        // false.

        if(!(($this->isMe()) && ($this->countMe !== true))) {
          // These are all checked for existance in the database in the functions and also the nodb
          // is checked and if true we return at once.

          $this->counter2(); // in 'masterdb' database
          $this->daycount(); // in 'masterdb' database
        }
      }
    }
  }

  /**
   * setSiteCookie()
   * @return bool true if OK else false
   * BLP 2021-12-20 -- add $secure, $httponly and $samesite as default to null. Then check them with ?? and set defaults.
   * BLP 2021-10-25 -- added thedomain
   */

  public function setSiteCookie($cookie, $value, $expire, $path="/", $thedomain=null, $secure=null, $httponly=null, $samesite=null) {
    $ref = $thedomain ?? "." . $this->siteDomain; // BLP 2021-10-16 -- added dot back to ref.
    $secure = $secure ?? true;
    $httponly = $httponly ?? false;
    $samesite = $samesite ?? 'Lax'; // BLP 2021-12-20 -- Make the default 'Lax' it was 'Strict'
    
    // BLP 2021-03-22 -- New. use $options to hold values. Set 'secure' true, 'httponly' true, and
    // 'samesite' None. Samesite is a new feature.
    // BLP 2021-10-16 -- as of PHP 7.3 an array can be used and samesite is added.
    
    $options =  array(
                      'expires' => $expire,
                      'path' => $path,
                      'domain' => $ref, // leading dot for compatibility or use subdomain
                      'secure' => $secure,      // or false
                      'httponly' => $httponly,    // or true. If true javascript can't be used.
                      'samesite' => $samesite    // None || Lax  || Strict // BLP 2021-12-20 -- changed to Lax
                     );

    if(!setcookie($cookie, $value, $options)) {
      return false;
    }
    return true;
  }

  /**
   * isMe()
   * Check if this access is from ME
   * @return true if $this->ip == $this->myIp else false!
   * BLP 2021-12-31 -- Remove myUrl stuff. myIp is always an array and never a string!
   */

  public function isMe() {
    return (array_intersect([$this->ip], $this->myIp)[0] === null) ? false : true;
  }

  /**
   * getVersion()
   * @return string version number
   */

  public function getVersion() {
    return SITE_CLASS_VERSION;
  }

  /**
   * getIp()
   * Get the ip address
   * @return int ip address
   */

  public function getIp() {
    return $this->ip;
  }

  /**
   * getHitCount()
   */

  public function getHitCount() {
    return $this->hitCount;
  }

  /**
   * getDoctype()
   * Returns the CURRENT DocType used by this program
   */

  public function getDoctype() {
    return $this->doctype;
  }

  /**
   * getPageTopBottom()
   * Get Page Top (<head> and <header> ie banner) and Footer
   * @param ?object $h top stuff
   * @param ?object $b bottom stuff
   * @return array top, footer
   * BLP 2014-12-31 -- Add footer to $h parameter to have the $b array etc.
   */

  public function getPageTopBottom(?object $h=null, ?object $b=null) {
    $h = $h ?? new stdClass;

    // If $b is null use $h->footer which could also be null

    $b = $b ?? $h->footer ?? new stdClass;

    // Do getPageTop and getPageFooter

    $top = $this->getPageTop($h);
    $footer = $this->getPageFooter($b);
    // return the array which we usually get via '[$top, $footer] = $S->getPageTopBottom($h, $b)'
    return array($top, $footer);
  }

  /**
   * getPageTop()
   * Get Page Top
   * Gets both the page <head> and <header> sections
   * @param ?object $h
   * @return string with the <head>  and <header> (ie banner) sections
   */
  
  public function getPageTop(?object $h=null) {
    $h = $h ?? new stdClass;
    
    // from getPageTopBottom($h.. or from mysitemap.json
    // BLP 2022-01-29 -- $h->banner or $this-mainTitle or $h->title in <h1>s or blank.

    $h->banner = $h->banner ?? ($this->mainTitle ?? ($h->title ? "<h1>$h->title</h1>" : '')); // BLP 2022-01-29 -- if nothing then blank
    
    // Get the page <head> section

    $head = $this->getPageHead($h);

    // Get the page's banner section
    // BLP 2022-01-30 -- we now pass $h instead of $banner and $h->bodytag
    
    $banner = $this->getPageBanner($h);
    return "$head\n$banner";
  }

  /**
   * getPageHead()
   * Get the page <head></head> stuff including the doctype etc.
   * @param object $h
   * @return string $pageHead
   */

  public function getPageHead(?object $h=null) {
    // BLP 2022-01-24 -- moved this from head.i.php to here

    $h = $h ?? new stdClass;

    // Should we use tracker.js? If either noTrack or nodb are set in mysitemap.json then don't
    
    if($this->noTrack === true || $this->nodb === true) {
      $trackerStr = '';
    } else {
      $trackerStr =<<<EOF
<script data-lastid="$this->LAST_ID" src="https://bartonphillips.net/js/tracker.js"></script>
EOF;
    } 

    // use either $h or $this values or a constant

    $dtype = $h->doctype ?? $this->doctype; // note that $this->doctype (from the top) could also be from mysitemap.json see the constructor.

    $h->base = $h->base ?? $this->base; // BLP 2022-01-28 -- new
    $h->title = $h->title ?? $this->title ?? ltrim($this->self, '/'); // BLP 2022-01-04 -- change from siteName to self
    $h->desc = $h->desc ?? $h->title ?? $this->title; // BLP 2021-12-08 -- add $this->title from mysitemap.json
    $h->keywords = $h->keywords ?? $this->keywords ?? $h->desc ?? "Something Interesting";
    $h->favicon = $h->favicon ?? $this->favicon ?? 'https://bartonphillips.net/images/favicon.ico';
    $h->defaultCss = $h->defaultCss ?? $this->defaultCss ?? 'https://bartonphillips.net/css/blp.css';
    $h->preheadcomment = $h->preheadcomment ?? $this->preheadcomment;
    $h->lang = $h->lang ?? $this->lang ?? 'en';
    $h->htmlextra = $h->htmlextra ?? $this->htmlextra; // can also be from mysitemap.json

    $html = '<html lang="' . $h->lang . '" ' . $h->htmlextra . ">"; // stuff like manafest etc.

    // What if headFile is null?

    if(!is_null($this->headFile)) {
      // BLP 2022-01-24 -- $trackerStr is available.
      // If the require returns -1 it is an error.

      if(($p = require_once($this->headFile)) != 1) {
        $pageHeadText = "{$html}\n$p";
      } else {
        throw new Exception(__CLASS__ . " " . __LINE__ .": $this->siteName, getPageHead() headFile '$this->headFile' returned 1");
      }
    } else {
      // Make a default <head>
      // BLP 2022-01-24 -- added jquery to default along with $trackerStr
      
      $pageHeadText =<<<EOF
$html
<!-- Default Head -->
<head>
  <title>{$h->title}</title>
  <!-- METAs -->
  <meta charset="utf-8"/>
  <meta name="description" content="{$h->desc}"/>
  <!-- local link -->
{$h->link}
  <!-- jQuery -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://code.jquery.com/jquery-migrate-3.3.2.min.js"></script>
  <script>jQuery.migrateMute = false; jQuery.migrateTrace = false;</script>
$trackerStr
  <!-- extra -->
{$h->extra}
  <!-- local script -->
{$h->script}
  <!-- local css -->
{$h->css}
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

  public function getPageBanner(?object $h=null) {
    $h = $h ?? new stdClass;

    $bodytag = $h->bodytag ?? $this->bodytag ?? "<body>";
    $mainTitle = $h->banner ?? $this->mainTitle;
    
    $image1 = "<img id='logo' data-image='$this->trackerImg1' src=''></a>";
    if($this->nodb !== true && $this->noTrack !== true) {
      $image2 = "<img src='https://bartonphillips.net/tracker.php?page=normal&id=$this->LAST_ID&image=$this->trackerImg2' alt='linux counter image.'>";
      $image3 = "<img src='https://bartonphillips.net/tracker.php?page=noscript&id=$this->LAST_ID'>";
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
   * @param object $b. 
   * @return string
   */
  
  public function getPageFooter(?object $b=null) {
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
    
    // Make the bottom of the page counter

    $b->ctrmsg = $b->ctrmsg ?? $this->ctrmsg;

    // counterWigget is available to the footerFile to use if wanted.
    // BLP 2022-01-02 -- if count is set then use the counter
    
    if(($b->count ?? $this->count) !== false) {
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
    
    if(($b->footerFile ?? $this->footerFile) !== false && $this->footerFile !== null) {
      $pageFooterText = require($this->footerFile);
    } else {
      $pageFooterText = <<<EOF
<!-- Default Footer -->
<footer>
EOF;
      if($b->msg || $b->msg1) { // Only set via $b
        $pageFooterText .= "<div id='footerMsg'>{$b->msg}{$b->msg1}</div>\n";
      }

      if(defined('EMAILFROM')) {
        $mailtoName = EMAILFROM;
      } elseif(isset($this->EMAILFROM)) {
        $mailtoName = $this->EMAILFROM;
      } else {
        $mailtoName = "webmaster@$this->siteDomain";
      }

      $pageFooterText .= <<<EOF
$counterWigget
$lastmod
EOF;
      if(!empty($b->msg2)) { // Only set via $b
        $pageFooterText .=  $b->msg2;
      }
      // BLP 2021-12-28 -- Add $b->script after everything
      
      $pageFooterText .= <<<EOF
{$b->script}
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

  // ********************************************************************************
  // Private and protected methods
  // protected methods can be overridden in child classes so most things that would be private
  // should be protected in this base class

  /**
   * getCounterWigget()
   */

  protected function getCounterWigget($msg="Page Hits") {
    if($this->nodb) return null;

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

  /**
   * checkIfBot()
   * Checks if the user-agent looks like a bot or if the ip is in the bots table.
   * Set $this->isBot true/false.
   * return nothing.
   */

  protected function checkIfBot() {
    if($this->nodb) {
      return;
    }

    if($this->isMe()) {
      return; 
    }

    $this->query("select count(*) from information_schema.tables ".
                 "where (table_schema = '$this->masterdb') and (table_name = 'bots')");

    // BLP 2021-12-23 -- add just plain 'bot', 'spider' and 'HeadlessChrome' to list.
    // NOTE our check of 'bots' happens before we would have added this client.
    
    if($this->fetchrow('num')[0]) {
      if(($x = preg_match("~\+*https?://|bot|spider|HeadlessChrome|python|java|wget|nutch|perl|libwww|lwp-trivial|curl|PHP/|urllib|".
                          "GT::WWW|Snoopy|MFC_Tear_Sample|HTTP::Lite|PHPCrawl|URI::Fetch|Zend_Http_Client|".
                          "http client|PECL::HTTP~i", $this->agent)) === 1) {
        $this->isBot = true;
      } elseif($x === false) {
        throw new Exceiption(__CLASS__ . " " . __LINE__ . ": preg_match() returned false");
      } elseif($this->query("select ip from $this->masterdb.bots where ip='$this->ip'")) {
        $this->isBot = true;
      }
    } else {
      $this->debug("$this->siteName: $this->self: table bots does not exist in the $this->masterdb database: ". __LINE__);
    }
  }

  // **************
  // Start Counters
  // **************

  /**
   * trackbots()
   * Track both bots and bots2
   * This sets $this->isBot unless the 'bots' table is not found.
   */

  protected function trackbots() {
    if($this->nodb) {
      return;
    }

    if($this->isMe()) { // I can never be a bot!!!
      return;
    }

    // This has been set by checkIfBot()

    if($this->isBot) {
      $this->query("select count(*) from information_schema.tables ".
                   "where (table_schema = '$this->masterdb') and (table_name = 'bots')");


      if($this->fetchrow('num')[0]) {
        // BLP 2018-06-08 -- $agent set
        $agent = $this->agent;

        try {
          // BLP 2021-11-12 -- first time robots=4.
          
          $this->query("insert into $this->masterdb.bots (ip, agent, count, robots, site, creation_time, lasttime) ".
                       "values('$this->ip', '$agent', 1, 4, '$this->siteName', now(), now())");
        } catch(Exception $e) {
          if($e->getCode() == 1062) { // duplicate key
            $this->query("select site from $this->masterdb.bots where ip='$this->ip' and agent='$agent'");

            list($who) = $this->fetchrow('num');

            if(!$who) {
              $who = $this->siteName;
            }

            if(strpos($who, $this->siteName) === false) {
              $who .= ", $this->siteName";
            }

            // BLP 2021-11-12 -- on update we or in an 8 (0xa).
            $this->query("update $this->masterdb.bots set robots=robots | 8, site='$who', count=count+1, lasttime=now() ".
                         "where ip='$this->ip' and agent='$agent'");
          } else {
            throw new Exception(__CLASS__ . " " . __LINE__ . ":$e");
          }
        }
      }

      // Now do bots2

      $this->query("select count(*) from information_schema.tables ".
                   "where (table_schema = '$this->masterdb') and (table_name = 'bots2')");

      if($this->fetchrow('num')) {
        // BLP 2021-10-27 -- Primary key is (ip, agent, date, site, which). There is only one of
        // these with the which value. On update just inc count and set lasttime.
        // BLP 2021-12-16 -- changed from 2 to 8 (robots=2, sitemap=4, cron=16)
        
        $this->query("insert into $this->masterdb.bots2 (ip, agent, date, site, which, count, lasttime) ".
                     "values('$this->ip', '$agent', now(), '$this->siteName', 8, 1, now())".
                     "on duplicate key update count=count+1, lasttime=now()");
      } else {
        $this->debug("$this->siteName: $this->self: table bots2 does not exist in the $this->masterdb database: ". __LINE__);
      }
    }
  }

  /**
   * tracker()
   * track if java script or not.
   * BLP 2016-12-20 -- added refid. This could be overwritten by tracker.php 'script' by the id of a previous item.
   */

  protected function tracker() {
    if($this->nodb) {
      return;
    }

    $this->query("select count(*) from information_schema.tables ".
                 "where (table_schema = '$this->masterdb') and (table_name = 'tracker')");

    if($this->fetchrow('num')[0]) {
      $agent = $this->agent;

      // BLP 2021-12-28 -- Explanation.
      // Here we set $java (isJavaScript) to 0x8000 or zero.
      // We then look at isBot and if nothing was found in the bots table and the regex did not
      // match something in the list then isJavaScript will be zero.
      // The visitor was probably a bot and will be added to the bots table as a 0x100 by the cron
      // job checktracker2.php and to the bots2 table as 16. The bot was more than likely curl,
      // wget, python or the like that sets its user-agent to something that would not trigger my
      // regex. Such visitor leave very little footprint.
      
      $java = $this->isMe() ? 0x8000 : 0; // BLP 2021-12-20 -- if isMe() then make $java 0x8000

      if($this->isBot) { // can NEVER be me!
        $java = 0x2000; // This is the robots tag
      }

      if($refid !== null) {
        $refid = $this->escape($this->refid); // if CLI this is blank not null.
      }
      
      //$this->debug("SiteClass: tracker, $this->siteName, $this->ip, $agent, $this->self");

      $this->query("insert into $this->masterdb.tracker (site, page, ip, agent, refid, starttime, isJavaScript, lasttime) ".
                   "values('$this->siteName', '$this->self', '$this->ip','$agent', '$refid', now(), $java, now())");

      $this->LAST_ID = $this->getLastInsertId();
    } else {
      $this->debug("$this->siteName: $this->self: table tracker does not exist in the $this->masterdb database: ". __LINE__);
    }
  }

  /**
   * updatemyip()
   * This is NOT done if we are not using a database or isMe() is false. That is it is NOT me.
   */

  protected function updatemyip() {
    if($this->nodb === true || $this->isMe() === false) {
      return;
    }

    // This IS ME so make sure the myip table exists and then insert/update the myip table.
    
    $this->query("select count(*) from information_schema.tables ".
                 "where (table_schema = '$this->masterdb') and (table_name = 'myip')");

    if($this->fetchrow('num')[0] == 0) {
      $this->debug("$this->siteName: $this->self: table myip does not exist in the $this->masterdb database: ". __LINE__);
      return;
    }

    // BLP 2022-01-16 -- NOTE there are only two places where the ip address is added:
    // bartonphillips.com/register.php and bonnieburch.com/addcookie.com.
    
    $sql = "update $this->masterdb.myip set count=count+1, lasttime=now() where myIp='$this->ip'";

    if(!$this->query($sql)) {
      $this->debug(__LINE__. ", update of myip failed, ip: $this->ip"); // this should not happen
    }
  }

  /**
   * counter()
   * This is the page counter feature in the footer
   * By default this uses a table 'counter' with 'filename', 'count', and 'lasttime'.
   *  'filename' is the primary key.
   * counter() updates $this->hitCount
   */

  protected function counter() {
    if($this->nodb) {
      return;
    }

    $this->query("select count(*) from information_schema.tables ".
                 "where (table_schema = '$this->masterdb') and (table_name = 'counter')");

    if($this->fetchrow('num')[0]) {
      $filename = $this->self; // get the name of the file

      // BLP 2021-08-20 -- The only time I don't want to do this is if isMe===true and
      // countMe===false.
      
      if(!(($this->isMe()) && ($this->countMe !== true))) {
        // realcnt is ONLY NON BOTS
        
        $realcnt = $this->isBot ? 0 : 1;

        // count is total of ALL hits!

        $sql = "insert into $this->masterdb.counter (site, filename, count, realcnt, lasttime) ".
               "values('$this->siteName', '$filename', '1', '$realcnt', now()) ".
               "on duplicate key update count=count+1, realcnt=realcnt+$realcnt, lasttime=now()";

        $this->query($sql);
      }

      // Now retreive the hit count value after it may have been incremented above.
      // It will only not be incremented if isMe===true and countMe===false.
      // In otherwords it is ME but countMe is false, so even though it is me DON'T count ME!
      
      $sql = "select realcnt ".
             "from $this->masterdb.counter ".
             "where site='$this->siteName' and filename='$filename'";
      
      $this->query($sql);
      $cnt = $this->fetchrow('num')[0];
      $this->hitCount = $cnt ?? 0; // This is the number of REAL (non BOT) accesses.
    } else {
      $this->debug("$this->siteName: $this->self: table counter does not exist in the $this->masterdb database: ". __LINE__);
    }      
  }

  /**
   * counter2
   * count files accessed per day
   * WARNING this may be overriden in a child class
   * BLP 2021-03-16 -- removed 'member' logic
   */
  
  protected function counter2() {
    if($this->nodb) {
      return;
    }

    $this->query("select count(*) from information_schema.tables " .
                 "where (table_schema = '$this->masterdb') ".
                 "and (table_name = 'counter2')");

    if($this->fetchrow('num')[0]) {
      [$real, $bot] = $this->isBot ? [0,1] : [1,0];
      
      // BLP 2021-12-31 -- change count to real here and in counter2 table.
      $sql = "insert into $this->masterdb.counter2 (site, date, filename, `real`, bots, lasttime) ".
             "values('$this->siteName', now(), left('$this->self', 254), $real , $bot, now()) ".
             "on duplicate key update `real`=`real`+$real, bots=bots+$bot, lasttime=now()";
      
      $this->query($sql);
    } else {
      $this->debug("$this->siteName: $this->self: table bots does not exist in the $this->masterdb database: ". __LINE__);
    }
  }
  
  /**
   * daycount()
   * Day Counts
   * This updates the 'mytime' cookie.
   */

  protected function daycount() {
    if($this->nodb) {
      return;
    }

    $this->query("select count(*) from information_schema.tables ".
                 "where (table_schema = '$this->masterdb') and (table_name = 'daycounts')");

    if($this->fetchrow('num')[0] == 0) {
      $this->debug("$this->siteName: $this->self: table daycounts does not exist in the $this->masterdb database: ". __LINE__);
      return;
    }

    $ip = $this->ip;

    [$real, $bots] = $this->isBot ? [0,1] : [1,0];
        
    try {
      // BLP 2021-02-20 -- note date and real must be escaped with `
      $sql = "insert into $this->masterdb.daycounts (site, `date`, `real`, bots, visits, lasttime) " .
             "values('$this->siteName', current_date(), $real, $bots, 1, now())";

      $this->query($sql);

      // Set $cookietime expires in 10 minutes
      
      $cookietime = time() + (60*10);
      
      if(!$this->setSiteCookie("mytime", time(), $cookietime)) {
        $this->debug("$this->siteName: Can't setSiteCookie() at ".__LINE__);
      }
    } catch(Exception $e) {
      if($e->getCode() != 1062) { // 1062 is dup key error
        throw new Exception(__CLASS__ . " " . __LINE__ .": daycount() error=$e");
      }

      // This is the 10 minute time delay for visitors vs hits

      if($_COOKIE['mytime']) {        
        $sql = "update $this->masterdb.daycounts set `real`=`real`+$real, bots=bots+$bots, lasttime=now() ".
               "where site='$this->siteName' and date=current_date()";
      } else {
        // set cookie to expire in 10 minutes
        $cookietime = time() + (60*10);
        if(!$this->setSiteCookie("mytime", time(), $cookietime)) {
          $this->debug("$this->siteName: Can't setSiteCookie() at ".__LINE__);
        }

        $sql = "update $this->masterdb.daycounts set `real`=`real`+$real, bots=bots+$bots, visits=visits+1, ".
               "lasttime=now() ".
               "where site='$this->siteName' and date=current_date()";
      }
      $this->query($sql);
    }
  }
  
  /**
   * logagent()
   * Log logagent
   * logagent is now used for 'analysis'
   */
  
  protected function logagent() {
    if($this->nodb) {
      return;
    }

    $agent = $this->agent;

    $this->query("select count(*) from information_schema.tables ".
                 "where (table_schema = '$this->masterdb') and (table_name = 'logagent')");

    if($this->fetchrow('num')[0]) {
      $sql = "insert into $this->masterdb.logagent (site, ip, agent, count, created, lasttime) " .
             "values('$this->siteName', '$this->ip', '$agent', '1', now(), now()) ".
             "on duplicate key update count=count+1, lasttime=now()";
        
      $this->query($sql);
    } else {
      $this->debug("$this->siteName: $this->self: table logagent does not exist in the $this->masterdb database: " . __LINE__);
    }
  }

  // ************
  // End Counters
  // ************

  /*
   * debug()
   * If noErrorLog is set in mysitemap.json then don't do error_log()
   */

  protected function debug($msg, $exit=false) {
    if($this->noErrorLog === true) {
      return;
    }

    error_log("SiteClass:: $msg");

    if($exit === true) {
      exit();
    }
  }
} // End of Class
