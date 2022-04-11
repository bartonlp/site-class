<?php
// SITE_CLASS_VERSION must change when the GitHub Release version changes.

// BLP 2022-04-09 - majer rework of getPageTopBottom(), getPageTop(), getPageHead(),
// getPageBanner() and getPageFooter().

define("SITE_CLASS_VERSION", "3.2.2"); // BLP 2022-04-09 - 

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
    // of 'barton' and $this->nodb false. Still the program can NOT do any calls via masterdb!

    if($this->dbinfo->user == "barton" && $this->nodb !== true) { // BLP 2021-12-31 -- make sure its the 'barton' user!
      $this->query("select count(*) from information_schema.tables ".
                   "where (table_schema = '$this->masterdb') and (table_name = 'myip')");

      if($this->fetchrow('num')[0]) {
        $sql = "select myIp from $this->masterdb.myip";
        $this->query($sql);
        while($ip = $this->fetchrow('num')[0]) {
          $this->myIp[] = $ip;
        }
      } else {
        $this->debug("SiteClass $this->siteName: $this->self: table myip does not exist in the $this->masterdb database: ". __LINE__);
      }
    }
    
    //error_log("SiteClass: myIp: " . print_r($this->myIp, true));
    
    // These all use database 'barton' ($this->masterdb)
    // and are always done regardless of 'count' and 'countMe'!
    // These all check $this->nodb first and return at once if it is true.
    
    if($this->noTrack !== true) {
      // checkIfBot() must be done first
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

  public function setSiteCookie(string $cookie, string $value, int $expire, string $path="/", ?string $thedomain=null,
                                ?bool $secure=null, ?bool $httponly=null, ?string $samesite=null):bool {
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
      $this->debug("SiteClass $this->siteName: $this->self: setcookie failed ". __LINE__);
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

    // BLP 2022-04-10 - make favicon, defaultCss, title, desc and css have full text.

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
    $h->defaultCss = ($h->defaultCss = ($h->defaultCss ?? $this->defaultCss)) ? "<link rel='stylesheet' href='$h->defaultCss' title='default'>" : null;

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
    $b->aboutwebsite = ($b->aboutwebsite ?? $this->aboutwebsite) ?? (file_exists('aboutwebsite.php') ? "<h2><a target='_blank' href='aboutwebsite.php'>About This Site</a></h2>" : null);
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

  // ********************************************************************************
  // Private and protected methods
  // protected methods can be overridden in child classes so most things that would be private
  // should be protected in this base class

  /**
   * checkIfBot()
   * Checks if the user-agent looks like a bot or if the ip is in the bots table.
   * Set $this->isBot true/false.
   * return nothing.
   */

  protected function checkIfBot():void {
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
      $this->debug("SiteClass $this->siteName: $this->self: table bots does not exist in the $this->masterdb database: ". __LINE__);
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

  protected function trackbots():void {
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
        $this->debug("SiteClass $this->siteName: $this->self: table bots2 does not exist in the $this->masterdb database: ". __LINE__);
      }
    }
  }

  /**
   * tracker()
   * track if java script or not.
   * BLP 2016-12-20 -- added refid. This could be overwritten by tracker.php 'script' by the id of a previous item.
   */

  protected function tracker():void {
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

      // Temp remove when done
      if(empty($this->self)) {
        $this->debug("SiteClass: tracker NO page, $this->siteName, $this->ip, $agent, self=$this->self, java=$java, " . __LINE__);
      }

      $this->query("insert into $this->masterdb.tracker (site, page, ip, agent, refid, starttime, isJavaScript, lasttime) ".
                   "values('$this->siteName', '$this->self', '$this->ip','$agent', '$refid', now(), $java, now())");

      $this->LAST_ID = $this->getLastInsertId();
    } else {
      $this->debug("SiteClass $this->siteName: $this->self: table tracker does not exist in the $this->masterdb database: ". __LINE__);
    }
  }

  /**
   * updatemyip()
   * This is NOT done if we are not using a database or isMe() is false. That is it is NOT me.
   */

  protected function updatemyip():void {
    if($this->nodb === true || $this->isMe() === false) {
      return;
    }

    // This IS ME so make sure the myip table exists and then insert/update the myip table.
    
    $this->query("select count(*) from information_schema.tables ".
                 "where (table_schema = '$this->masterdb') and (table_name = 'myip')");

    if($this->fetchrow('num')[0] == 0) {
      $this->debug("SiteClass $this->siteName: $this->self: table myip does not exist in the $this->masterdb database: ". __LINE__);
      return;
    }

    // BLP 2022-01-16 -- NOTE there are only two places where the ip address is added:
    // bartonphillips.com/register.php and bonnieburch.com/addcookie.com.
    
    $sql = "update $this->masterdb.myip set count=count+1, lasttime=now() where myIp='$this->ip'";

    if(!$this->query($sql)) {
      $this->debug("SiteClass $this->siteName: update of myip failed, ip: $this->ip, " .__LINE__); // this should not happen
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
      $this->debug("SiteClass $this->siteName: $this->self: table counter does not exist in the $this->masterdb database: ". __LINE__);
    }      
  }

  /**
   * counter2
   * count files accessed per day
   * WARNING this may be overriden in a child class
   * BLP 2021-03-16 -- removed 'member' logic
   */
  
  protected function counter2():void {
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
      $this->debug("SiteClass $this->siteName: $this->self: table bots does not exist in the $this->masterdb database: ". __LINE__);
    }
  }
  
  /**
   * daycount()
   * Day Counts
   * This updates the 'mytime' cookie.
   */

  protected function daycount():void {
    if($this->nodb) {
      return;
    }

    $this->query("select count(*) from information_schema.tables ".
                 "where (table_schema = '$this->masterdb') and (table_name = 'daycounts')");

    if($this->fetchrow('num')[0] == 0) {
      $this->debug("SiteClass $this->siteName: $this->self: table daycounts does not exist in the $this->masterdb database: ". __LINE__);
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
        $this->debug("SiteClass $this->siteName: Can't setSiteCookie() at ".__LINE__);
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
          $this->debug("SiteClass $this->siteName: Can't setSiteCookie() at ".__LINE__);
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
  
  protected function logagent():void {
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
      $this->debug("SiteClass $this->siteName: $this->self: table logagent does not exist in the $this->masterdb database: " . __LINE__);
    }
  }

  // ************
  // End Counters
  // ************

  /*
   * debug()
   * If noErrorLog is set in mysitemap.json then don't do error_log()
   */

  protected function debug(string $msg, $exit=false):void {
    if($this->noErrorLog === true) {
      return;
    }

    error_log("SiteClass:: $msg");

    if($exit === true) {
      exit();
    }
  }
} // End of Class
