<?php
// SITE_CLASS_VERSION must change when the GitHub Release version changes.  
define("SITE_CLASS_VERSION", "2.0.0");

// One class for all my sites
// This version has been generalized to not have anything about my sites in it!
/**
 * SiteClass
 *
 * @package SiteClass
 * @author Barton Phillips <barton@bartonphillips.com>
 * @version v2.0.0
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
  protected $databaseClass = null;
  
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
   *  fields: databaseClass, siteDomain, subDomain, headFile,
   *  bannerFile, footerFile, count, daycountwhat, emailDomain, nodb:
   *  these fields are all protected. Note: nodb can also be a member of databaseClass->nodb.
   *  If there are more elements in $s they become public properties. You can add myUri to populate
   *  $this->myIp if you don't want to count webmaster activity.
   *  count is default true and countMe is default false. The rest of the values are 'null' if not
   *  specifically set in $s.
   */
  
  public function __construct($s=null) {
    // This is ugly!
    // Wordpress has a class named Error. So for conejo we have to use a different name.
    // I may try to fix this at some point.

    ErrorClass::init(); // BLP 2014-12-31 -- Make sure this is done

    date_default_timezone_set("America/Los_Angeles");
    
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

    // BLP 2016-03-16 -- if nodb is true anywhere set it true and count and countMe false.
    // The database could have been instantiated before the call to this constructor and the object
    // placed in databaseClass.
    
    if($this->databaseClass->nodb === true || $this->nodb === true || is_null($this->dbinfo)) {
       // nodb === true so don't do any database stuff
       // could be either no $databaseClass or $databaseClass->nodb===true so be sure and
       // set $this->nodb true also.
      $this->nodb = true;
      $this->count = $this->countMe = false;
    }

    if(is_null($this->databaseClass) && $this->nodb !== true && !is_null($this->dbinfo)) {
      // instantiate the Database with dbinfo
      $this->databaseClass = new Database($this->dbinfo);
    }

    if(isset($this->db2info)) {
      $this->databaseClass2 = new Database($this->db2info);
      $this->db2 = $this->databaseClass2->db;
    }

    // Populate the dbAbstract class's protected $db. This allows the dbAbstract class's
    // methods to be accessed via SiteClass. Can't use getDb() until $this->db is valid!

    if(isset($this->databaseClass)) {
      $this->db = $this->databaseClass->db; // use property not method getDb()!
    }

    // If myUri is set get the ip address into myIp
    
    if(isset($this->myUri)) {
      $this->myIp = gethostbyname($this->myUri); // get my home ip address
    }

    $this->ip = $_SERVER['REMOTE_ADDR'];
    $this->agent = $_SERVER['HTTP_USER_AGENT'];
    $this->self = $_SERVER['PHP_SELF'];
    if($this->siteName == "Conejoskiclub") {
      $this->requestUri =  $_SERVER['REQUEST_URI'];
    } else {
      $this->requestUri = $this->self;
    }

    // These all use database 'barton'
    // and are always done regardless of 'count' and 'countMe'!
    // These all check $this->nodb first and return at once if it is true.

    if($this->noTrack != true) {
      $this->trackbots(); // Should be the FIRST in the group. This sets $this->isBot
      $this->tracker();
      $this->logagent(); // in 'masterdb' database. logip and logagent
      $this->setmyip();
    }
    
    // If 'count' is false we don't do these counters
    
    if($this->count) {
      // Get the count for hitCount. This is done even if countMe is false. The hitCount is always
      // updated (unless the counter file does not exist).
      // That is why it is here rather than after the countMe test below!

      $this->counter(); // in 'masterdb' database

      // If this is me and $countMe is false (default is false) then don't count.
      // not (true && true) ==   false, it is me and countMe=false
      // not (true && false) ==  true,  it is me and countMe=true 
      // not (false && true) ==  true,  it isn't me and countMe=false
      // not (false && false) == true,  it isn't me and countMe=false

      if(!(($this->isMe()) && ($this->countMe === false))) {
        // These are all checked for existance in the database in the functions and also the nodb
        // is checked and if true we return at once.
        $this->counter2(); // in 'masterdb' database
        // arg can be ALL or a file or an array of files OR nothing! 
        $this->daycount($this->daycountwhat); // in 'masterdb' database
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
   * getDatabaseClass()
   * returns the Database class
   */
  
  public function getDatabaseClass() {
    return $this->databaseClass;
  }

  /**
   * isMe()
   * Check if this access is from ME
   * @return bool true if me else false
   */

  public function isMe() {
    return ($this->myIp == $this->ip);
  }

  /**
   * setSiteCookie()
   */

  public function setSiteCookie($cookie, $value, $expire, $path="/") {
    // bool setcookie ( string $name [, string $value [, int $expire = 0
    // [, string $path [, string $domain [, bool $secure = false
    // [, bool $httponly = false ]]]]]] )

    $ref = $this->siteDomain;

    // setcookie($name, $value, $expire=0, $path="", $domain="", $secure=false, $httponly=false) 
    if(!setcookie($cookie, "$value", $expire, $path, $ref)) {
      throw(new Exception("Error: setSiteCookie() can't set cookie"));
    }
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

  public function getPageTopBottom($h, $b=null) {
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
   * 'documennt type', 'banner' and 'nonav',
   * (it can also look like $header=>array(head=>array(), banner=>"banner", nonav=>bool),
   * where head can have 'title','desc', 'extra' and 'doctype'
   * and banner has a banner string. This is depreciated).
   * The string version has just the 'title' which is then used for the 'description' also.
   * The second argument is optional and a string with the 'banner'.
   * The banner can either be part of the first argument as 'banner' or the second argument.
   * If the second argument is not present then $header[banner] is used (which could also be null).
   *
   * NOTE: added nonav which can only be used as part of the header array!
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
      $arg = $header; // this is then title, desc, extra, nonav, doctype, banner, maybe bodytag
    } else {
      throw(new Exception("Error: getPageTop() wrong argument type"));
    }

    // If doctype is not supplied then use the constructor version which may be the default

    if(!$arg['doctype']) {
      $arg['doctype'] = $this->doctype;
    }

    $nonav = $arg['nonav'] ? $arg['nonav'] : false;

    // NOTE: the bodytag and banner strings override the $arg values.
    // So if we have the initial arguments 'object', 'string', 'string' the two string
    // values take presidence!

    $bodytag = $bodytag ? $bodytag : $arg['bodytag'];    
    $banner = $banner ? $banner : $arg['banner']; 

    // Get the page <head> section

    $head = $this->getPageHead($arg);

    // Get the page's banner section

    $banner = $this->getPageBanner($banner, $nonav, $bodytag);

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
          //echo "$k=$v<br>\n";
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

  public function getBanner($mainTitle, $nonav=false, $bodytag=null) {
    return $this->getPageBanner($mainTitle, $nonav, $bodytag);
  }
  
  /**
   * getPageBanner()
   * Get Page Banner
   * @param string $mainTitle
   * @param bool $nonav if set to true then the navigation bar is NOT displayed (for homepage).
   * @param string $bodytag
   * @return string banner
   */

  public function getPageBanner($mainTitle, $nonav=false, $bodytag=null) {
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

    // Set ctrmsg to 'Counter Reset: 2016-03-27' if not set

    $arg['ctrmsg'] = $arg['ctrmsg'] ? $arg['ctrmsg'] : 'Counter Reset: 2016-03-27';

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

    if(gethostbyname('bartonlp.com') == $this->ip || gethostbyname('bartonlp.org') == $this->ip) {
      return;
    }
    
    $this->query("select count(*) from information_schema.tables ".
                 "where (table_schema = '$this->masterdb') and (table_name = 'bots')");

    list($ok) = $this->fetchrow('num');

    if($ok == 1) {
      $agent = $this->escape($this->agent);

      $this->isBot = preg_match("~\+*http://|Googlebot-Image|python|java|wget|nutch|perl|libwww|lwp-trivial|curl|PHP/|urllib|".
                                "GT::WWW|Snoopy|MFC_Tear_Sample|HTTP::Lite|PHPCrawl|URI::Fetch|Zend_Http_Client|".
                                "http client|PECL::HTTP~i", $this->agent)
                     || ($this->query("select ip from $this->masterdb.bots where ip='$this->ip'")) ? true : false;
          
      if($this->isBot) {
        try {
          $this->query("insert into $this->masterdb.bots (ip, agent, count, robots, who, creation_time, lasttime) ".
                       "values('$this->ip', '$agent', 1, 4, '$this->siteName', now(), now())");
        } catch(Exception $e) {
          if($e->getCode() == 1062) { // duplicate key
            $this->query("select who from $this->masterdb.bots where ip='$this->ip' and agent='$agent'");

            list($who) = $this->fetchrow('num');

            if(!$who) {
              $who = $this->siteName;
            }

            if(strpos($who, $this->siteName) === false) {
              $who .= ", $this->siteName";
            }

            $this->query("update $this->masterdb.bots set robots=robots | 8, who='$who', count=count+1, lasttime=now() ".
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
        if($this->isBot) {
          $this->query("insert into $this->masterdb.bots2 (ip, agent, date, site, which, count, lasttime) ".
                       "values('$this->ip', '$agent', current_date(), '$this->siteName', 2, 1, now()) ".
                       "on duplicate key update count=count+1, lasttime=now()");
        }
      } else {
        $this->debug("$this->siteName: $this->self: table bots2 does not exist in the $this->masterdb database");
      } 
    } else {
      $this->debug("$this->siteName: $this->self: table bots does not exist in the $this->masterdb database");
    }
  }


  
  /**
   * tracker()
   * track if java script or not.
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
      
      if($this->isBot) {
        $java = 0x2000; // This is the robots tag
      }
      
      //$this->debug("SiteClass: tracker, $this->siteName, $this->ip, $agent, $this->self");
      
      $this->query("insert into $this->masterdb.tracker (site, page, ip, agent, starttime, isJavaScript, lasttime) ".
                   "values('$this->siteName', '$this->requestUri', '$this->ip','$agent', now(), $java, now())");

      $this->LAST_ID = $this->getLastInsertId();
    } else {
      $this->debug("$this->siteName: $this->self: table tracker does not exist in the $this->masterdb database");
    }
  }

  /**
   * doanalysis()
   */

  protected function doanalysis() {
    if($this->nodb) {
      return;
    }

    if($this->analysis) {
      $this->query("select count(*) from information_schema.tables ".
                   "where (table_schema = '$this->masterdb') and ".
                   "((table_name = 'analysis' or table_name = 'analysis2'))");

      list($ok) = $this->fetchrow('num');

      if($ok == 2) {
        // Don't count ME
        if(!$this->isMe()) {
          $agent = $this->escape($this->agent);
          $this->query("insert into $this->masterdb.analysis (agent, count, created, lasttime) ".
                       "values('$agent', 1, current_date(), now()) ".
                       "on duplicate key update count=count+1, lasttime=now()");

          // analysis2 only keeps 60 days of data. Every night a cron job removes old data.
          
          $this->query("insert into $this->masterdb.analysis2 (agent, count, created, lasttime) ".
                       "values('$agent', 1, current_date(), now()) ".
                       "on duplicate key update count=count+1, lasttime=now()");
        }
      } else {
        $this->debug("$this->siteName: $this->self: table analysis and/or analysis2 do not exist in the $this->masterdb database");
      }
    }
  }

  /**
   * setmyip()
   * insert ignore to table myip
   */

  protected function setmyip() {
    if($this->nodb || !$this->myIp) {
      return;
    }

    $this->query("select count(*) from information_schema.tables ".
                 "where (table_schema = '$this->masterdb') and (table_name = 'myip')");

    list($ok) = $this->fetchrow('num');

    if($ok == 0) {
      $this->debug("$this->siteName: $this->self: table myip does not exist in the $this->masterdb database");
      return;
    }

    $this->query("insert ignore into $this->masterdb.myip values('$this->myIp', now())");
  }

  /**
   * counter()
   * This is the page counter feature in the footer
   * WARNING this could be  overriden in your sites class if you need more features.
   * By default this uses a table 'counter' with 'filename', 'count', and 'lasttime'.
   *  'filename' is the primary key.
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

      list($cnt) = $this->queryfetch("select realcnt ".
                                     "from $this->masterdb.counter ".
                                     "where site='$this->siteName' and filename='$filename'");
      
      $this->hitCount = ($cnt[0]) ? $cnt[0] : 0;
    } else {
      $this->debug("$this->siteName: $this->self: table counter does not exist in the $this->masterdb database");
    }      
  }

  /**
   * counter2
   * count files accessed per day
   * WARNING this may be overriden is a child class
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
      if($this->isBot) {
        $sql = "insert into $this->masterdb.counter2 (site, date, filename, count, bots, lasttime) ".
               "values('$this->siteName', now(), '$this->requestUri', 0, 1, now()) ".
               "on duplicate key update bots=bots+1, lasttime=now()";
      } else {
        $member = 0;
        $memberUpdate = '';

        if($this->id) {
          $member = 1;
          $memberUpdate = ", members=members+1";
        }

        $sql = "insert into $this->masterdb.counter2 (site, date, filename, count, members, lasttime) ".
               "values('$this->siteName', now(), '$this->requestUri', 1, $member, now()) ".
               "on duplicate key update count=count+1$memberUpdate, lasttime=now()";
      }
      $this->query($sql);
    } else {
      $this->debug("$this->siteName: $this->self: table bots does not exist in the $this->masterdb database");
    }
  }
  
  /**
   * daycount()
   * Day Counts
   * WARNING this could be overriden in a child class.
   * @param string|array $inc. String is the name of the file to count. Array is multiple files to count.
   *   An array should look like array('/index', '/antherpage', 'etc'). We will in_array($this->self, $inc) === true
   *   then we count it.
   *   If $inc == 'all' or 'All' etc. then $check=$what="all";
   * May need to redefine in an extended class
   */

  protected function daycount($inc) {
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

    $what = basename($this->self);

    $ip = $this->ip;

    if($inc) {
      if(is_array($inc)) {
        if(in_array($what, $inc)) {
          $check = $what;
        } else {
          $check = null; // not in array so make sure it does not match $what!
        }
      } elseif(strtolower($inc) == "all") {
        // Not an array and ALL
        $what = $check = "all";
      } else {
        $check = $inc;
      }
    } else {
      $what = $check = "all"; // BLP 2016-02-09 -- chance from "index.php" to "all" if not specified;
    }

    $member = $real = $bots = 0;
    $memberUpdate = $realUpdate = $botsUpdate = '';
    
    if($this->id) {
      $member = 1;
      $memberUpdate = " members=members+1,";
    }

    if($this->isBot) {
      $bots = 1;
      $botsUpdate = " bots=bots+1,";
    } else {
      $real = 1;
      $realUpdate = " `real`=`real`+1,";
    }

    if($what == $check) {
      $curdate = date("Y-m-d");
      
      try {
        $sql = "insert into $this->masterdb.daycounts (site, `date`, `real`, bots, members, visits, lasttime) " .
               "values('$this->siteName', '$curdate', $real, $bots, $member, 1, now())";

        $this->query($sql);

        $cookietime = time() + (60*10);
        $this->setSiteCookie("mytime", time(), $cookietime);
      } catch(Exception $e) {
        if($e->getCode() != 1062) { // 1062 is dup key error
          throw(new Exception(__CLASS__ ."::daycount() error=$e"));
        }

        // This is the 10 minute time delay for visitors vs hits

        if($_COOKIE['mytime']) {        
          $sql = "update $this->masterdb.daycounts set$realUpdate$botsUpdate$memberUpdate lasttime=now() ".
                 "where site='$this->siteName' and date='$curdate'";
        } else {
          // set cookie to expire in 10 minutes
          $cookietime = time() + (60*10);
          $this->setSiteCookie("mytime", time(), $cookietime);

          $sql = "update $this->masterdb.daycounts set$realUpdate$botsUpdate$memberUpdate visits=visits+1, ".
                 "lasttime=now() ".
                 "where site='$this->siteName' and date='$curdate'";
        }
        $this->query($sql);
      }
    }
  }
  
  /**
   * logagent()
   * Log logagent
   * logagent and logagent2 are now used for 'analysis'
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
      $sql = "insert into $this->masterdb.logagent (site, ip, agent, count, id, created, lasttime) " .
             "values('$this->siteName', '$this->ip', '$agent', '1', '$this->id', now(), now()) ".
             "on duplicate key update count=count+1, lasttime=now()";
        
      $this->query($sql);
    } else {
      $this->debug("$this->siteName: $this->self: table logagent does not exist in the {$this->dbinfo['database']} database");
    }

    // Do insert into logagent2 which has only the last n days
    
    $this->query("select count(*) from information_schema.tables ".
                 "where (table_schema = '$this->masterdb') and (table_name = 'logagent2')");

    list($ok) = $this->fetchrow('num');
      
    if($ok == 1) {
      $sql = "insert into $this->masterdb.logagent2 (site, ip, agent, count, id, created, lasttime) ".
             "values('$this->siteName', '$this->ip', '$agent', '1', '$this->id', now(), now()) ".
             "on duplicate key update count=count+1, id='$this->id', lasttime=now()";

      $this->query($sql);
    } else {
      $this->debug("$this->siteName: $this->self: table logagent2 does not exist in the {$this->dbinfo['database']} database");
    }
  }

  /**
   * trackmember()
   * Track activity on site
   * This table is in the siteName's database.
   * NOTE: override this in your sites class if you need more features.
   * By default this uses the 'logagent' and 'memberpagecnt' tables.
   */

  protected function trackmember() {
    if($this->nodb) {
      return;
    }

    // If there is a member 'id' then update the memberTable

    if($this->id && $this->memberTable) {
      $agent = $this->escape($this->agent);

      $this->query("select count(*) from information_schema.tables ".
                   "where (table_schema = '{$this->dbinfo['database']}') and (table_name = '$this->memberTable')");

      list($ok) = $this->fetchrow('num');

      if($ok) {
        // BLP 2016-05-04 -- 
        // The fname-lname are a unique index 'name' so we will not get duplicates of our users.
        
        $sql = "insert into $this->memberTable (fname, lname, email, visits, visittime) ".
               "values('$this->fname', '$this->lname', '$this->email', '1', now()) ".
               "on duplicate key update visits=visits+1, visittime=now()";

        $this->query($sql);
      } else {
        $this->debug("$this->siteName: $this->self: table $this->memberTable does not exist in the {$this->dbinfo['database']} database");
      }
      
      // BLP 2014-09-16 -- add nomemberpagecnt

      if(!$this->nomemberpagecnt) {
        $this->query("select count(*) from information_schema.tables ".
                     "where (table_schema = '{$this->dbinfo['database']}') and (table_name = 'memberpagecnt')");

        list($ok) = $this->fetchrow('num');

        if($ok) {
          $sql = "insert into memberpagecnt (page, id, ip, agent, count, lasttime) " .
                 "values('$this->requestUri', '$this->id', '$this->ip', '$agent', '1', now()) ".
                 "on duplicate key update count=count+1, ip='$this->ip', agent='$agent', lasttime=now()";

          $this->query($sql);
        } else {
          $this->debug("$this->siteName: $this->self: table memberpagecnt does not exist in the {$this->dbinfo['database']} database");
        }
      }
    }
  }

  /*
   * debug()
   */

  protected function debug($msg) {
    if($this->noErrorLog === true) {
      return;
    }
    error_log($msg);
  }
} // End of Class

//-----------------
// Helper Functions
//-----------------

// Callback to get the user id for SqlError
// NOTE: sites that have members will overload this in their class file. This is a generic version
// that does not understand users so it grabs the ip address and agent only.

if(!function_exists('ErrorGetId')) {
  function ErrorGetId() {
    $id = $_COOKIE['SiteId'];
    if(empty($id)) {
      $id = "IP={$_SERVER['REMOTE_ADDR']}, AGENT={$_SERVER['HTTP_USER_AGENT']}";
    } else {
      $id = "ID=$id, IP={$_SERVER['REMOTE_ADDR']}, AGENT={$_SERVER['HTTP_USER_AGENT']}";
    }
    return $id;
  }
}
