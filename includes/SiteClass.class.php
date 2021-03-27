<?php
// SITE_CLASS_VERSION must change when the GitHub Release version changes.
// BLP 2021-03-22 -- add 'options' to setSiteCookie().
// BLP 2021-03-22 -- remove daycountwhat from constructor values amd $inc from daycount().
// BLP 2021-03-16 -- removed 'member' logic from counter2(). Remove 'member' logic from daycount().
// Remove $this-id from all functions. ErrorClass::setDevelopment(true) now sets
// $noEmailErrs also to true. This can be overriden by settin ErrorClass::setNoEmailErrs(false)
// after setting development.
// BLP 2021-03-11 -- add escape for agent.
// BLP 2021-03-09 -- removed logagent2 logic.
// BLP 2021-03-09 -- added nodb flag to setmyip(). 
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

define("SITE_CLASS_VERSION", "3.0.1");

// One class for all my sites
// This version has been generalized to not have anything about my sites in it!
/**
 * SiteClass
 *
 * @package SiteClass
 * @author Barton Phillips <barton@bartonphillips.com>
 * @version v2.0.4
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
  
  // Give these default values.
  public $count = true;
  public $countMe = false;
  public $id = 0; // default to not a member
  
  // Current Doc Type
  public $doctype = "<!DOCTYPE html>";

  /**
   * Constructor
   *
   * @param array|object $s
   *  fields: siteDomain, subDomain, headFile,
   *  bannerFile, footerFile, count, emailDomain, nodb,
   *  these fields are all protected. 
   *  If there are more elements in $s they become public properties. You can add myUri to populate
   *  $this->myIp if you don't want to count webmaster activity.
   *  'count' is default true and 'countMe' is default false. The rest of the values are 'null' if not
   *  specifically set in $s.
   */
  
  public function __construct($s=null) {
    ErrorClass::init(); // BLP 2014-12-31 -- Make sure this is done

    //vardump($s);
    
    $this->isSiteClass = true;

    // BLP 2018-04-20 -- INIT SECTION. Moved for altorouter.php
    // Now I can add stuff to mysitemap.json and have it update these also.
    
    $this->ip = $_SERVER['REMOTE_ADDR'];
    $this->self = $_SERVER['PHP_SELF'];
    $this->requestUri = $this->self;

    // BLP 2021-03-06 -- our server 'bartonlp.org' is in New York.
    // Our old server 'bartonlp.com' was in San Fransisco.
    
    date_default_timezone_set("America/New_York");
    
    $arg = array(); // temp array for $s during parsing

    if(!is_null($s)) {
      if(is_array($s)) {
        $arg = $s;
      } elseif(is_object($s)) {
        foreach($s as $k=>$v) {
          $arg[$k] = $v;
        }
      } else {
        throw(new Exception(__CLASS__ . ": Argument to constructor not an array or object"));
      }
    }

    // Now make $this objects of the items that were in $s
    // That means you can put ANYTHING in $s and it will be public in $this!
    
    foreach($arg as $k=>$v) {
      $this->$k = $v; 
    }

    // From here on we don't use $arg any more instead $this
    // If emailDomain is not set force it to siteDomain

    if(!$this->emailDomain) {
      $this->emailDomain = $this->siteDomain;
    }

    // BLP 2018-07-01 -- Add the date to the copyright notice if one exists

    if($this->copyright) {
      $this->copyright = date("Y") . " $this->copyright";
    }
    
    if($this->nodb === true || is_null($this->dbinfo)) {
      // nodb === true so don't do any database stuff
      $this->nodb = true;
      $this->count = $this->countMe = false;
    }

    // BLP 2021-03-11 -- add escape for HTTP_USER_AGENT to cope with ' etc.
    
    if(is_null($this->db) && $this->nodb !== true && !is_null($this->dbinfo)) {
      // instantiate the Database. Pass Everything on to Database
      $this->db = new Database($this);
      $this->agent = $this->escape($_SERVER['HTTP_USER_AGENT']); 
    } else {
      $this->agent = $_SERVER['HTTP_USER_AGENT'];
    }
    
    // If myUri is set get the ip address into myIp
    // BLP 2016-11-27 -- Changed meaning. It can be an object
    // BLP 2018-07-01 -- Added logic to look at bartonphillips.net if myUri is a string and starts
    // with http.

    if(isset($this->myUri)) {
      if(is_array($this->myUri)) { // an array from mysitemap.json. IP addresses or URLs
        foreach($this->myUri as $v) {
          $this->myIp[] = gethostbyname($v); // If this fails it returns $v which can be an ip address not a url.
        }
      } else { // not an array but a string
        if(strpos($this->myUri, 'http') == 0) { // is the string a full URL?
          // Here $this->myUri probably looks like 'https://bartonphillips.net/myUri.json'
          // When we get that it is an array [myUri] so we want the elements of the array 0..n
          // Now $this->myUri looks like it would from mysitemap.json if the array was in that
          // file.

          $this->myUri = json_decode(file_get_contents($this->myUri), true)['myUri'];

          if(is_array($this->myUri)) {
            foreach($this->myUri as $v) { // pick the array apart
              $this->myIp[] = gethostbyname($v); // If this fails it returns $v which can be an ip address not a url.
            }
          } else { // this is a single string
            $this->myIp = gethostbyname($this->myUri); // get my home ip address. Same as above.
          }
        } else { // just a straight string and NOT from a full URL
          $this->myIp = gethostbyname($this->myUri); // get my home ip address. Same as above.
        }
      } 
    }

    // These all use database 'barton'
    // and are always done regardless of 'count' and 'countMe'!
    // These all check $this->nodb first and return at once if it is true.

    if($this->noTrack != true) {
      $this->checkIfBot(); // This set $this->isBot. Does a isMe() so I never get set as a bot!
      $this->trackbots();  // both 'bots' and 'bots2'. This also does a isMe() so never get put into the 'bots*' tables.
      $this->tracker();    // This logs Me and everybody else!
      $this->logagent();   // This logs Me and everybody else!
      $this->setmyip();    //
    }

    // If 'count' is false we don't do these counters

    if($this->count) {
      // Get the count for hitCount. This is done even if countMe is false. The hitCount is always
      // updated (unless the counter file does not exist).
      // That is why it is here rather than after the countMe test below!

      // BLP 2021-03-27 -- NOTE: counter() checks for not isMe() and countMe is false.
      // So it does NOT count Me but it still gets the 'realcnt' even if the cound was NOT done.
      // The 'realcnt' is placed in '$this->hitCount'.
      // I may want to decouple this and have a counter() and a gethitcount() function. OR NOT
      // because gethitcount() would have to happen after a non-Me count?
      
      $this->counter(); // in 'masterdb' database. Does not count Me but always set $this->hitCount.

      // If this is me and $countMe is false (default is false) then don't count.
      // not (true && true) ==   false, it is me and countMe=false
      // not (true && false) ==  true,  it is me and countMe=true 
      // not (false && true) ==  true,  it isn't me and countMe=false
      // not (false && false) == true,  it isn't me and countMe=false

      if(!(($this->isMe()) && ($this->countMe === false))) {
        // These are all checked for existance in the database in the functions and also the nodb
        // is checked and if true we return at once.
        $this->counter2(); // in 'masterdb' database
        $this->daycount(); // in 'masterdb' database
      }
    }
  }

  /**
   * getVersion()
   * @return string version number
   */

  public function getVersion() {
    return SITE_CLASS_VERSION;
  }

  /**
   * isMe()
   * Check if this access is from ME
   * @return an array. The array == true but does not === true!
   */

  public function isMe() {
    if(is_array($this->myIp)) {
      // BLP 2018-07-02 -- use array_intersect()
      return array_intersect([$this->ip], $this->myIp); // returns an array!
    } else {
      return ($this->myIp == $this->ip); // returns a bool
    }
  }

  /**
   * setSiteCookie()
   * @return bool true if OK else false
   */

  public function setSiteCookie($cookie, $value, $expire, $path="/") {
    // bool setcookie ( string $name [, string $value [, int $expire = 0
    // [, string $path [, string $domain [, bool $secure = false
    // [, bool $httponly = false ]]]]]] )

    // BLP 2021-02-28 -- siteDomain is set in mysitemap.json and may not be the actual server.
    //$ref = $this->siteDomain;
    $ref = $_SERVER['SERVER_NAME'];
    //error_log("cookie: $cookie, value: $value, expire: $expire, path: $path");
    // BLP 2021-03-22 -- New. use $options to hold values. Set 'secure' true, 'httponly' true, and
    // 'samesite' None. Samesite is a new feature.
    
    $options =  array(
                      'expires' => $expire,
                      'path' => $path,
                      'domain' => '.' . $ref, // leading dot for compatibility or use subdomain
                      'secure' => true,     // or false
                      'httponly' => true,    // or false
                      'samesite' => 'None' // None || Lax  || Strict
                     );                      
    if(!setcookie($cookie, $value, $options)) {
      return false;
    }
    return true;
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
   * getPageTopBottom()
   * Get Page Top and Footer
   * @param object|array $h top stuff
   * @param object|array|string $b bottom stuff. If string them msg1
   * @return array top, footer
   * BLP 2014-12-31 -- Add footer to $h parameter to have the $b array etc.
   */

  public function getPageTopBottom($h=null, $b=null) {
    // Force $h to be an array.
    $h = is_array($h) ? $h : (array)$h;

    // BLP 2014-12-31 -- New footer item is added to $b.
    
    if(isset($h['footer'])) {
      // BLP 2014-12-31 -- force both to arrays
      $b = (array)$b + (array)$h['footer'];
    }

    // Do getPageTop and getPageFooter
    
    $top = $this->getPageTop($h);
    $footer = $this->getPageFooter($b);
    // return the array which we usually get via list($top, $footer)
    return array($top, $footer);
  }

  /**
   * getPageTop()
   * Get Page Top
   * Gets both the page <head> section and the banner
   * The first argument ($header) is either a string, an array or an object and is required.
   * The array/object version has the 'title', 'description', 'script&styles etc',
   * 'documennt type', 'banner',
   * (it can also look like $header=>array(head=>array(), banner=>"banner",
   * where head can have 'title','desc', 'extra' and 'doctype'
   * and banner has a banner string. This is depreciated).
   * The string version has just the 'title' which is then used for the 'description' also.
   * The second argument is optional and a string with the 'banner'.
   * The banner can either be part of the first argument as 'banner' or the second argument.
   * If the second argument is not present then $header[banner] is used (which could also be null).
   *
   * @param string|array|object $header assoc array [title][desc][extra][doctype][banner][bodytag]
   *   or string title
   * @param string $banner
   * @param string $bodytag a custome body tag, defaults to null
   *   (bodytag can also be a member of the $header array or object)
   * @return string with the <head> section and the banner.
   */

  public function getPageTop($header, $banner=null, $bodytag=null) {
    $arg = array();

    if(is_string($header)) {
      $arg['title'] = $header;
      // $banner and $bodytag are handled below. Therefore we could have
      // an object, string, string in which case $banner string and $bodytag string
      // would override $header->banner etc.
    } elseif(is_object($header)) {
      foreach($header as $k=>$v) {
        $arg[$k] = $v; // turn the object into the $arg array
      }
    } elseif(is_array($header)) {
      if(isset($header[0])) { // BLP 2014-12-31 --
        $header['title'] = $header[0];
        unset($header[0]);
      }
      $arg = $header; // this is then title, desc, extra, doctype, banner, maybe bodytag
    } else {
      throw(new Exception("Error: getPageTop() wrong argument type"));
    }

    // If doctype is not supplied then use the constructor version which may be the default

    if(!$arg['doctype']) {
      $arg['doctype'] = $this->doctype;
    }

    // NOTE: the bodytag and banner strings override the $arg values.
    // So if we have the initial arguments 'object', 'string', 'string' the two string
    // values take presidence!

    $bodytag = $bodytag ? $bodytag : $arg['bodytag'];
    // BLP 2021-03-27 -- if $banner (from constructor) or $arg['banner'] are empty then use
    // mainTitle from mysitemap.json if it exists. 
    $banner = $banner ? $banner : $arg['banner'];
    $banner = $banner ? $banner : $this->mainTitle;

    // Get the page <head> section

    $head = $this->getPageHead($arg);

    // Get the page's banner section

    $banner = $this->getPageBanner($banner, $bodytag);

    return "$head\n$banner";
  }

  /**
   * getDoctype()
   * Returns the CURRENT DocType used by this program
   */

  public function getDoctype() {
    return $this->doctype;
  }

  /**
   * getPageHead()
   * Get the page <head></head> stuff including the doctype etc.
   * This can take either 5 args or an array or object
   * @param string $title
   * @param string $desc or null
   * @param string $extra or null
   * @param string $doctype
   * @param string $lang or null
   * @param string $htmlextra or null. Extra items for the <html tag.
   * or 
   * @param array array[title=>"title", ...]
   * or
   * @param object object->title = "title" etc.
   * NOTE: the array or object can have 'link' or 'preheadcomment'. These are added to the head
   *   section if they exist in the headFile or if the default is used.
   */

  public function getPageHead(/*$title, $desc=null, $extra=null, $doctype, $lang*/) {
    $n = func_num_args();
    $args = func_get_args();
    $arg = array();

    if($n == 1) {
      $a = $args[0];
      if(is_string($a)) {
        $arg['title'] = $a;
      } elseif(is_object($a)) {
        foreach($a as $k=>$v) {
          $arg[$k] = $v;
        }
      } elseif(is_array($a)) {
        $arg = $a;
      } else {
        $this->debug("Error: getPageHead() argument no valid: ". var_export($a, true));
        throw(new Exception("Error: getPageHead() argument no valid: ". var_export($a, true)));
      }
    } elseif($n > 1) {
      $keys = array(title, desc, extra, doctype, lang);
      $ar = array();
      for($i=0; $i < $n; ++$i) {
        $ar[$keys[$i]] = $args[$i];
      }
      $arg = $ar;
    }

    // this->doctype can be initialized in the constuctor. If $arg['doctype'] has a value here
    // we want to use it for this page head. Otherwise use the this->doctype which may be the
    // default set by the constructor

    $arg['doctype'] = !is_null($arg['doctype']) ? $arg['doctype'] : $this->doctype;

    if(is_null($arg['desc'])) {
      $arg['desc'] = $arg['title'];
    }

    if(is_null($arg['lang'])) $arg['lang'] = 'en'; // default language is english

    $html = '<html lang="' . $arg['lang'] . '" ' . $arg['htmlextra'] . ">"; // stuff like manafest etc.

    $dtype = $arg['doctype'];

    // What if headFile is null?

    if(!is_null($this->headFile)) {
      // BLP 2015-04-25 -- If the require has a return value use it.
      if(($p = require_once($this->headFile)) != 1) {
        $pageHeadText = "{$html}\n$p";
      } else {
        $pageHeadText = "{$html}\n$pageHeadText";
      }
    } else {
      // Make a default <head>
      $pageHeadText =<<<EOF
$html
<!-- Default Head -->
<head>
  <title>{$arg['title']}</title>
  <!-- METAs -->
  <meta charset="utf-8"/>
  <meta name="description" content="{$arg['desc']}"/>
  <!-- local link -->
{$arg['link']}
  <!-- extra -->
{$arg['extra']}
  <!-- local script -->
{$arg['script']}
  <!-- local css -->
{$arg['css']}
</head>

EOF;
    }

    // Default header has < /> elements. If not XHTML we remove the /> at the end!
    $pageHead = <<<EOF
{$arg['preheadcomment']}{$dtype}
$pageHeadText

EOF;

    return $pageHead;
  }

  /** getBanner. Depreciated **/

  public function getBanner($mainTitle, $bodytag=null) {
    return $this->getPageBanner($mainTitle, $bodytag);
  }
  
  /**
   * getPageBanner()
   * Get Page Banner
   * @param string $mainTitle
   * @param string $bodytag
   * @return string banner
   */

  public function getPageBanner($mainTitle, $bodytag=null) {
    $bodytag = $bodytag ? $bodytag : "<body>";

    if(!is_null($this->bannerFile)) {
      // BLP 2015-04-25 -- if return use it.
      if(($b = require($this->bannerFile)) != 1) {
        $pageBannerText = $b;
      }
    } else {
      // a default banner
      // The default banner does not have the IE warnings etc.
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

  /** getFooter. Depreciated **/

  public function getFooter() {
    return $this->getPageFooter();
  }
  
  /**
   * getPageFooter()
   * Get Page Footer
   * @param variable number of args.
   *   arguments can be seperate args (defaults: $msg='', $msg1='', $msg2='', $ctrmsg=''. $nofooter='')
   *   an assoc array, or an object.
   *   for array and object the elements are 'msg', 'msg1', 'msg2', 'ctrmsg', 'nofooter',
   *   Seperate args must be in the above order. nofooter can be true, false or null etc.
   * @return string
   */

  public function getPageFooter(/* mixed */) {
    // If called from getPageTopBottom($h, $b) then $b
    // will be there even though it may be null. This is not an error.

    $n = func_num_args();
    $args = func_get_args();
    $arg = array();

    if($n == 1) {
      $a = $args[0];
      if(is_string($a)) {
        $arg['msg'] = $a;
      } elseif(is_object($a)) {
        foreach($a as $k=>$v) {
          $arg[$k] = $v;
        }
      } elseif(is_array($a)) {
        if(isset($a[0])) { // BLP 2014-12-31 --
          $a['msg'] = $a[0];
        }

        $arg = $a;
      } // elseif(is_null($a)) this is OK because getPageTopBottom($h, $b) will always pass a $b
      //   even if it is null.
    } elseif($n > 1) {
    // String items are being passed and must be in this order.
      $keys = array('msg', 'msg1', 'msg2', 'ctrmsg', 'nofooter');
      $ar = array();
      for($i=0; $i < $n; ++$i) {
        $ar[$keys[$i]] = $args[$i];
      }
      $arg = $ar;
    }

    // Make the bottom of the page counter

    // If $arg['ctrmsg'] use it.
    // If $this->ctrmsg use it.
    // Else blank

    $arg['ctrmsg'] = $arg['ctrmsg'] ? $arg['ctrmsg'] : $this->ctrmsg;

    // counterWigget is available to the footerFile to used if wanted.
    
    $counterWigget = $this->getCounterWigget($arg['ctrmsg']); // ctrmsg may be null which is OK

    if(!is_null($this->footerFile)) {
      // BLP 2015-04-25 -- if return value use it.
      if(($p = require($this->footerFile)) != 1) { // bring in $pageFooterText
        $pageFooterText = $p;
      }
    } else {
      $pageFooterText = <<<EOF
<!-- Default Footer -->
<footer>
EOF;
      // If nofooter then only <footer></footer></body></html>
      
      if(!$arg['nofooter']) {
        // BLP 2014-12-31 -- added msg. string them together

        if($arg['msg'] || $arg['msg1']) {
          $pageFooterText .= "<div id='footerMsg'>{$arg['msg']}{$arg['msg1']}</div>\n";
        }

        // BLP 2015-04-10 -- only if we are counting

        if($this->count) {
          $pageFooterText .= $counterWigget;
        }

        $rdate = getlastmod();
        $date = date("M d, Y H:i:s", $rdate);

        if(defined('EMAILFROM')) {
          $mailtoName = EMAILFROM;
        } elseif(isset($this->EMAILFROM)) {
          $mailtoName = $this->EMAILFROM;
        } else {
          $mailtoName = "webmaster@$this->emailDomain";
        }

        $pageFooterText .= <<<EOF
<div style="text-align: center;">
<p id='lastmodified'>Last Modified&nbsp;$date</p>
<p id='contactUs'><a href='mailto:$mailtoName'>Contact Us</a></p>
</div>
EOF;
        if(!empty($arg['msg2'])) {
          $pageFooterText .=  $arg['msg2'];
        }
      }
      $pageFooterText .= <<<EOF
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

    // BLP 2018-07-02 -- replace old logic with 'isMe()'
    if($this->isMe()) {
      return;
    }

    $this->query("select count(*) from information_schema.tables ".
                 "where (table_schema = '$this->masterdb') and (table_name = 'bots')");

    list($ok) = $this->fetchrow('num');

    if($ok == 1) {
      $agent = $this->escape($this->agent);

      $this->isBot = preg_match("~\+*https?://|Googlebot-Image|python|java|wget|nutch|perl|libwww|lwp-trivial|curl|PHP/|urllib|".
                                "GT::WWW|Snoopy|MFC_Tear_Sample|HTTP::Lite|PHPCrawl|URI::Fetch|Zend_Http_Client|".
                                "http client|PECL::HTTP~i", $this->agent)
                     || ($this->query("select ip from $this->masterdb.bots where ip='$this->ip'")) ? true : false;          
    }
  }
  
  // ********
  // Counters
  // ********

  /**
   * trackbots()
   * Track both bots and bots2
   * This sets $this->isBot unless the 'bots' table is not found.
   */

  protected function trackbots() {
    if($this->nodb) {
      return;
    }

    if($this->isMe()) {
      return;
    }
    
    // This has been set by checkIfBot()
    
    if($this->isBot) {
      // BLP 2018-06-08 -- $agent set
      $agent = $this->agent;
    
      $this->query("select count(*) from information_schema.tables ".
                 "where (table_schema = '$this->masterdb') and (table_name = 'bots')");

      list($ok) = $this->fetchrow('num');
      if($ok == 1) {
        try {
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

            $this->query("update $this->masterdb.bots set robots=robots | 8, site='$who', count=count+1, lasttime=now() ".
                         "where ip='$this->ip' and agent='$agent'");
          } else {
            throw($e);
          }
        }
      }

      // Now do bots2

      $this->query("select count(*) from information_schema.tables ".
                   "where (table_schema = '$this->masterdb') and (table_name = 'bots2')");

      list($ok) = $this->fetchrow('num');

      if($ok == 1) {
        $this->query("insert into $this->masterdb.bots2 (ip, agent, date, site, which, count, lasttime) ".
                     "values('$this->ip', '$agent', current_date(), '$this->siteName', 2, 1, now()) ".
                     "on duplicate key update count=count+1, lasttime=now()");
      } else {
        $this->debug("$this->siteName: $this->self: table bots2 does not exist in the $this->masterdb database");
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

    list($ok) = $this->fetchrow('num');
    
    if($ok == 1) {
      $agent = $this->escape($this->agent);
      
      $java = 0;
      
      if($this->isBot) { // can NEVER be me!
        $java = 0x2000; // This is the robots tag
      }

      $refid = $this->escape($_SERVER['HTTP_REFERER']);
  
      //$this->debug("SiteClass: tracker, $this->siteName, $this->ip, $agent, $this->self");
      
      $this->query("insert into $this->masterdb.tracker (site, page, ip, agent, refid, starttime, isJavaScript, lasttime) ".
                   "values('$this->siteName', '$this->requestUri', '$this->ip','$agent', '$refid', now(), $java, now())");

      $this->LAST_ID = $this->getLastInsertId();
    } else {
      $this->debug("$this->siteName: $this->self: table tracker does not exist in the $this->masterdb database");
    }
  }

  /**
   * setmyip()
   * insert ignore to table myip
   */

  protected function setmyip() {
    // BLP 2021-03-09 -- add nodb flag
    // BLP 2018-07-02 -- replace old logic with 'isMe()'
    // BLP 2021-02-20 -- changed to == false and fixed error below
    if($this->nodb === true || $this->isMe() == false) { // because isMe() could return an empte array which == false but not === false
      return;
    }
    
    $this->query("select count(*) from information_schema.tables ".
                 "where (table_schema = '$this->masterdb') and (table_name = 'myip')");

    list($ok) = $this->fetchrow('num');

    if($ok == 0) {
      $this->debug("$this->siteName: $this->self: table myip does not exist in the $this->masterdb database");
      return;
    }

    // BLP 2021-02-20 -- this was wrong. It did $ip instead of $this->ip
    $this->query("insert ignore into $this->masterdb.myip values('$this->ip', now())");
  }

  /**
   * counter()
   * This is the page counter feature in the footer
   * WARNING this could be  overriden in your sites class if you need more features.
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

    list($ok) = $this->fetchrow('num');
      
    if($ok == 1) {
      $filename = $this->requestUri; // get the name of the file

      if(!(($this->isMe()) && ($this->countMe === false))) {
        // realcnt is ONLY NON BOTS
        
        $realcnt = $this->isBot ? 0 : 1;

        // count is total of ALL hits!

        $sql = "insert into $this->masterdb.counter (site, filename, count, realcnt, lasttime) ".
               "values('$this->siteName', '$filename', '1', '$realcnt', now()) ".
               "on duplicate key update count=count+1, realcnt=realcnt+$realcnt, lasttime=now()";

        $this->query($sql);
      }

      // Now retreive the hit count value after it may have been incremented
      // BLP 2021-03-16 -- replace queryfetch() with query() and fetchrow('num')
      
      $sql = "select realcnt ".
             "from $this->masterdb.counter ".
             "where site='$this->siteName' and filename='$filename'";
      $this->query($sql);
      list($cnt) = $this->fetchrow('num');
      $this->hitCount = $cnt ? $cnt : 0;
    } else {
      $this->debug("$this->siteName: $this->self: table counter does not exist in the $this->masterdb database");
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

    list($ok) = $this->fetchrow('num');

    if($ok) {
      $bot = $this->isBot ? 1 : 0;

      // BLP 2017-11-01 -- add left to keep from getting too long errors
      $sql = "insert into $this->masterdb.counter2 (site, date, filename, count, bots, lasttime) ".
             "values('$this->siteName', now(), left('$this->requestUri', 254), 0, 1, now()) ".
             "on duplicate key update bots=bots+$bot, lasttime=now()";
      $this->query($sql);
    } else {
      $this->debug("$this->siteName: $this->self: table bots does not exist in the $this->masterdb database");
    }
  }
  
  /**
   * daycount()
   * Day Counts
   * WARNING this could be overriden in a child class.
   * BLP 2021-03-22 -- remove $inc
   * BLP 2021-03-22 -- 
   * May need to redefine in an extended class
   * BLP 2021-03-16 -- removed 'member' logic
   */

  protected function daycount() {
    if($this->nodb) {
      return;
    }

    $this->query("select count(*) from information_schema.tables ".
                 "where (table_schema = '$this->masterdb') and (table_name = 'daycounts')");

    list($ok) = $this->fetchrow('num');

    if($ok == 0) {
      $this->debug("$this->siteName: $this->self: table daycounts does not exist in the $this->masterdb database");
      return;
    }

    $ip = $this->ip;

    [$real, $bots] = $this->isBot ? [0,1] : [1,0];

    $curdate = date("Y-m-d");

    try {
      // BLP 2021-02-20 -- note date and real must be escaped with `
      $sql = "insert into $this->masterdb.daycounts (site, `date`, `real`, bots, visits, lasttime) " .
             "values('$this->siteName', '$curdate', $real, $bots, 1, now())";

      $this->query($sql);

      $cookietime = time() + (60*10);
      if(!$this->setSiteCookie("mytime", time(), $cookietime)) {
        $this->debug("$this->siteName: Can't setSiteCookie() at ".__LINE__);
      }
    } catch(Exception $e) {
      if($e->getCode() != 1062) { // 1062 is dup key error
        throw(new Exception(__CLASS__ ."::daycount() error=$e"));
      }

      // This is the 10 minute time delay for visitors vs hits

      if($_COOKIE['mytime']) {        
        $sql = "update $this->masterdb.daycounts set `real`=`real`+$real, bots=bots+$bots, lasttime=now() ".
               "where site='$this->siteName' and date='$curdate'";
      } else {
        // set cookie to expire in 10 minutes
        $cookietime = time() + (60*10);
        if(!$this->setSiteCookie("mytime", time(), $cookietime)) {
          $this->debug("$this->siteName: Can't setSiteCookie() at ".__LINE__);
        }

        $sql = "update $this->masterdb.daycounts set `real`=`real`+$real, bots=bots+$bots, visits=visits+1, ".
               "lasttime=now() ".
               "where site='$this->siteName' and date='$curdate'";
      }
      $this->query($sql);
    }
  }
  
  /**
   * logagent()
   * Log logagent
   * logagent is now used for 'analysis'
   * BLP 2021-03-16 -- remove $this->id and id from 'logagent' table.
   */
  
  protected function logagent() {
    if($this->nodb) {
      return;
    }

    $agent = $this->escape($this->agent);

    $this->query("select count(*) from information_schema.tables ".
                 "where (table_schema = '$this->masterdb') and (table_name = 'logagent')");

    list($ok) = $this->fetchrow('num');

    if($ok == 1) {
      $sql = "insert into $this->masterdb.logagent (site, ip, agent, count, created, lasttime) " .
             "values('$this->siteName', '$this->ip', '$agent', '1', now(), now()) ".
             "on duplicate key update count=count+1, lasttime=now()";
        
      $this->query($sql);
    } else {
      $this->debug("$this->siteName: $this->self: table logagent does not exist in the $this->masterdb database");
    }
  }

  /*
   * debug()
   * If noErrorLog is set in mysitemap.json then don't do error_log()
   */

  protected function debug($msg) {
    if($this->noErrorLog === true) {
      return;
    }
    error_log($msg);
  }
} // End of Class
