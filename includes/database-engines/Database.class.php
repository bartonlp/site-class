<?php
/* Well tested and maintained */
// All of the tracking and counting logic that is in this file.

define("DATABASE_CLASS_VERSION", "4.0.0database"); // BLP 2023-02-24 -
require_once(__DIR__ . "/../defines.php"); // This has the constants for TRACKER, BOTS, BOTS2, and BEACON

/**
 * Database wrapper class
 */

class Database extends dbMysqli {
  /**
   * constructor
   * @param $s object. $isSiteClass bool.
   * $s should have all of the $this from SiteClass or $_site from mysitemap.json
   * To just pass in the required database options set $s->dbinfo = (object) $ar
   * where $ar is an assocative array with ["host"=>"localhost",...]
   * $isSiteClass is true if this is from SiteClass.
   */

  protected $hitCount = 0;

  public function __construct(object $s, ?bool $isSiteClass=null) {
    global $__info; // BLP 2023-01-24 - added for node programs has [0]=ip, [1]=agent. See /examples/node-programs/server.js

    // If we have $s items use them otherwise get the defaults

    $s->ip = $s->ip ?? $_SERVER['REMOTE_ADDR'] ?? "$__info[0]"; // BLP 2023-01-18 - Added for NODE with php view.
    $s->agent = $s->agent ?? $_SERVER['HTTP_USER_AGENT'] ?? "$__info[1]"; // BLP 2022-01-28 -- CLI agent is NULL and $__info[1] wil be null
    $s->self = $s->self ?? htmlentities($_SERVER['PHP_SELF']);
    $s->requestUri = $s->requestUri ?? $_SERVER['REQUEST_URI'];

    // Put all of the $s values into $this.
    
    foreach($s as $k=>$v) {
      $this->$k = $v;
    }
    
    // If no 'dbinfo' (no database) in mysitemap.json set everything so the database is not loaded.
    
    if($this->nodb === true || is_null($this->dbinfo)) {
      $this->count = false;
      $this->noTrack = true; // If nodb then noTrack is true also.
      $this->nodb = true;    // Maybe $this->dbinfo was null
      $this->dbinfo = null;  // Maybe nodb was set
      return; // If we have NO DATABASE just return.
    }

    $db = null;

    // BLP 2023-11-13 - we can pass $this which is esentially $_site with some stuff added.
    
    parent::__construct($this); // dbMysqli constructor.

    // BLP 2023-01-26 - currently there is only ONE viable engine and that is dbMysqli
    
    if($this->noTrack !== false && ($this->dbinfo->user == "barton" || $this->user == "barton")) { // make sure its the 'barton' user!
      $this->myIp = $this->CheckIfTablesExist(); // Check if tables exit and get myIp
    }

    // Escapte the agent in case it has something like an apostraphy in it.
    
    $this->agent = $this->escape($this->agent);
    
    // These all use database 'barton' ($this->masterdb)
    // and are always done regardless of 'count'!
    // If $this->nodb or there is no $this->dbinfo we have made $this->noTrack true and
    // $this->count false
    
    if($this->noTrack !== true) {
      // BLP 2023-10-02 - get all of the $_SERVER info.
      $this->getserver();
      
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
    date_default_timezone_set("America/New_York");
  } // END Construct

  /**
   * setSiteCookie()
   * @return bool true if OK else false
   * BLP 2021-12-20 -- add $secure, $httponly and $samesite as default
   */

  public function setSiteCookie(string $cookie, string $value, int $expire, string $path="/", ?string $thedomain=null,
                                bool $secure=true, bool $httponly=false, string $samesite='Lax'):bool
  {
    $ref = $thedomain ?? "." . $this->siteDomain; // BLP 2021-10-16 -- added dot back to ref.
    
    $options =  array(
                      'expires' => $expire,
                      'path' => $path,
                      'domain' => $ref, // (defaults to $this->siteDomain with leading period.
                      'secure' => $secure,
                      'httponly' => $httponly,    // If true javascript can't be used (defaults to false.
                      'samesite' => $samesite    // None || Lax  || Strict (defaults to Lax)
                     );

    if(!setcookie($cookie, $value, $options)) {
      error_log("Database $this->siteName: $this->self: setcookie failed ". __LINE__);
      return false;
    }

    //error_log("cookie: $cookie, value: $value, options: " . print_r($options, true));
    return true;
  }

  /**
   * isMyIp($ip):bool
   * Given an IP address check if this is me.
   */

  public function isMyIp(string $ip):bool {
    if($this->isMeFalse === true) return false;
    return (!empty(array_intersect([$ip], $this->myIp)));
  }
  
  /**
   * isMe()
   * Check if this access is from ME
   * @return true if $this->ip == $this->myIp else false!
   */

  public function isMe():bool {
    return $this->isMyIp($this->ip);
  }

  public function getClassName() {
    return __CLASS__;
  }

  public function getDb() {
    return $this->db;
  }

  public function setDb($db) {
    $this->db = $db;
  }

  public function getDbError() {
    return $this->db->error;
  }

  public function getDbErrno() {
    return $this->db->errno;
  }
  
  /**
   * getVersion()
   * @return string version number
   * Because there is no $this in the function we can all it on $S->getVersion or Database::getVersion().
   * When $S is SiteClass this is overloaded with the $S of SiteClass.
   */

  public static function getVersion():string {
    return DATABASE_CLASS_VERSION;
  }
  
  /**
   * getIp()
   * Get the ip address
   * @return int ip address
   */

  public function getIp():string {
    return $this->ip;
  }

  /*
   * isBot(string $agent):bool
   * *** This is ONLY called from checkIfBot() in the dbAbstract constructor!
   * *** However, it can be called by applications using $S.
   * Determines if an agent is a bot or not.
   * @return bool
   * Side effects:
   *  it sets $this->isBot
   *  it sets $this->foundBotAs
   * These side effects are used by checkIfBot():void see below.
   */
  
  public function isBot(string $agent):bool {
    $this->isBot = false;
    $this->foundBotAs = ''; // These two will be set in isBot().
    
    if(!empty($agent)) { // BLP 2023-10-27 - Check agent.
      if(($x = preg_match("~\+*https?://|@|bot|spider|scan|HeadlessChrome|python|java|wget|nutch|perl|libwww|lwp-trivial|curl|PHP/|urllib|".
                          "crawler|GT::WWW|Snoopy|MFC_Tear_Sample|HTTP::Lite|PHPCrawl|URI::Fetch|Zend_Http_Client|".
                          "http client|PECL::HTTP~i", $agent)) === 1) { // 1 means a match
        $this->isBot = true;
        $this->foundBotAs = BOTAS_MATCH;
      } elseif($x === false) { // false is error
        // This is an unexplained ERROR
        throw new SqlExceiption(__CLASS__ . " " . __LINE__ . ": preg_match() returned false", $this);
      }
    } else {
      // BLP 2023-10-27 - If the agent is empty then this is a BOT
      $this->foundBotAs = BOTAS_NOAGENT;
      $this->isBot = true;
      /* BLP 2023-10-29 - debug. analysis.php calls isBot() and agent may be empty and isMeFalse is true. */
      //if(!$this->isMe()) error_log("Database isBot(): ip=$this->ip, site=$this->siteName, NO-AGENT, foundBotAs=$this->foundBotAs");
    }

    // BLP 2023-10-27 - at this point isBot may have true and either 'match' or 'no-agent'.
    
    if($this->query("select robots from $this->masterdb.bots where ip='$this->ip'")) { // Is it in the bots table?
      // Yes it is in the bots table.

      $tmp = '';

      // Look at each posible entry in bots. The entries may be for different sites and have
      // different values for $robots. The BOTAS_... are a string while BOTS_... are integers.
      
      while([$robots] = $this->fetchrow('num')) {
        if($robots & BOTS_ROBOTS) {
          $tmp .= "," . BOTAS_ROBOT;
        }
        if($robots & BOTS_SITEMAP) {
          $tmp .= "," . BOTAS_SITEMAP;
        }
        if($robots & BOTS_CRON_ZERO) {
          $tmp .= "," . BOTAS_ZBOT; // BLP 2023-11-04 - found 0x100 in bots so this is a zero (0x100) from bots ttable: 'zbot'
        }
        if($tmp != '') break;
      }
      
      if($tmp != '') {
        if(empty($this->foundBotAs)) {
          $tmp = ltrim($tmp, ','); // remove the leading comma
          $this->foundBotAs = $tmp; // foundBotAs has never been set!
        } else {
          $this->foundBotAs .= $tmp; // foundBotAs could be BOTAS_MATCH or BOTAS_NOAGENT so add $tmp.
        }
        $this->isBot = true; 
      } 
    }

    // isBot may be true because 1) BOTAS_MATCH, 2) BOTAS_NOAGENT or 3) found in bots
    // table. If the preg_match() found the agent, then BOTAS_MATCH. If there was 'no agent', isBot is set
    // and BOTAS_NOAGENT is set.
    
    if(!$this->isBot) {
      // The agent was not found by preg_match() and the ip was NOT in the bots table either.

      $this->foundBotAs = BOTAS_NOT; // not a bot (null)
    }

    return $this->isBot;
  }

  // ********************************************************************************
  // Private and protected methods.
  // Protected methods can be overridden in child classes so most things that would be private
  // should be protected in this base class

  // **************
  // Start Counters
  // **************

  /**
   * BLP 2023-10-02 - new expermental. 
   * getserver()
   * Save all of the sec-ch info from the apache request header into the server table.
   */

  protected function getserver():void {
    $req = apache_request_headers();  // get headers

    // Only save sec-ch headers

    $info = '';
    
    foreach($req as $k=>$v) {
      if(strpos($k, "sec-ch") === false) continue;

      $tmp = preg_replace("~v=\"(.*?)\"~", '$1', $v); // everything except sec-ch-ua and sec-ua-mobile
      
      switch($k) {
        case 'sec-ch-ua':
          $ua = explode(",", $v);

          $tmp = $secname = trim((explode(";", $ua[0]))[0], " \n\r\t\v\x00\""); // Get $ua[0]. It might be 'Not A'.

          If(preg_match("~.*?Not.*?A~", $secname) === 1) {
            $secname = trim((explode(";", $ua[1]))[0], " \n\r\t\v\x00\""); // Get $ua[1]
          }
          $info .= "ua=$v,";
          $this->secname = "$secname,";
          break;
        case 'sec-ch-ua-mobile':
//          if($v == "?0") {
//            $info .= "phone,";
//          } else {
//            $info .= "not-phone,";
//          }
          $info .= "$v,";
          break;
        case 'sec-ch-ua-arch':
          $info .= "arch=$tmp,";
          break;
        case 'sec-ch-ua-platform':
          $info .= "platform=$tmp,";
          break;
        case 'sec-ch-ua-platform-version':
          $info .= "Version=$tmp,";
          break;
        case 'sec-ch-ua-model':
          $info .= "Model=$tmp,";
        default:
          continue;
      }
    }
    $info = rtrim($info, ',');

    if($this->isMe()) return;

    // If we didn't find any return.
    
    if(empty($info)) {
      return; // BLP 2023-10-25 - if empty don't log anything.
    }

    // Check to see if this is iPhone or Android
    
    preg_match("~iPhone|Android~i", $this->agent, $m);

    // BLP 2023-10-25 - NOTE foundBotAs is set by checkIfBot() which is called by the constructor
    // in dbAbstract.
    
    // error log info.

    //error_log("Database getserver() ip=$this->ip, site=$this->siteName, page=$this->self, phone=$m[0], bot=$this->foundBotAs, info: $info");
        
    $this->info = rtrim($info, ',');

    // $m[0] is iPhone or Andriod or null.
    // Add into to the server table.
    
    $this->query("insert into $this->masterdb.server (ip, site, page, bot, info, phone, lasttime) " .
                 "values('$this->ip', '$this->siteName', '$this->self', '$this->foundBotAs', '$info', '$m[0]', now())");
  }

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
   * BLP 2023-10-05 - Added `browser`.
   *
   * tracker()
   * track if java script or not.
   * CREATE TABLE `tracker` (
   *  `id` int NOT NULL AUTO_INCREMENT,
   *  `botAs` varchar(30) DEFAULT NULL,
   *  `site` varchar(25) DEFAULT NULL,
   *  `page` varchar(255) NOT NULL DEFAULT '',
   *  `finger` varchar(50) DEFAULT NULL,
   *  `nogeo` tinyint(1) DEFAULT NULL,
   *  `browser` varchar(50) DEFAULT NULL,
   *  `ip` varchar(40) DEFAULT NULL,
   *  `agent` text,
   *  `referer` varchar(255) DEFAULT '',
   *  `starttime` datetime DEFAULT NULL,
   *  `endtime` datetime DEFAULT NULL,
   *  `difftime` varchar(20) DEFAULT NULL,
   *  `isJavaScript` int DEFAULT '0',
   *  `error` varchar(256) DEFAULT NULL,
   *  `lasttime` datetime DEFAULT NULL,
   *  PRIMARY KEY (`id`),
   *  KEY `site` (`site`),
   *  KEY `ip` (`ip`),
   *  KEY `lasttime` (`lasttime`),
   *  KEY `starttime` (`starttime`)
   * ) ENGINE=MyISAM AUTO_INCREMENT=6708643 DEFAULT CHARSET=utf8mb3;
   */

  protected function tracker():void {
    $agent = $this->agent;
    $secname = $this->secname;

    // BLP 2023-10-05 - This is the `browser` logic. It sets $name.

    $pat2 = "~ Edge/| Edg/|firefox|chrome|crios|safari|trident|msie|opera|konqueror~i";

    if(preg_match_all($pat2, $agent, $m)) {
      $m = array_map('strtolower', $m[0]);
      $mm = $m[count($m)-1];

      switch($mm) {
        case 'opera':
          $name = 'Opera';
          break;
        case ' edg/':
        case ' edge/':
          $name = 'MS-Edge';
          break;
        case 'trident':
        case 'msie':
          $name = 'MsIe';
          break;
        case 'chrome':
          $name = 'Chrome';
          break;
        case 'safari':
          if(($m[count($m)-2] == 'chrome') || ($m[count($m)-2] == 'crios')) {
            $name = 'Chrome';
          } else {
            $name = 'Safari';
          }
          break;
        case 'firefox':
          $name = 'Firefox';
          break;
        case 'konqueror':
          $name = 'Konqueror';
          break;
        default:
          error_log("Database.class.php: not in BROWSER pattern: $mm[0]");
          continue;
      }
    }
    // BLP 2023-10-05 - end `browser` logic.
    
    // BLP 2021-12-28 -- Explanation.
    // Here we set $java (isJavaScript) to TRACKER_BOT or zero.
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

    $agent = $this->escape($agent);

    // BLP 2023-10-05 - added `browser` and $name.

    $name = "$secname$name";

    // BLP 2023-10-19 - here foundBotAs could be BOTAS_ZERO ('zero') and java could be TRACKER_ZERO
    // (0). If that is the case then this is not going to be found in checktracker2.php.
    
    $this->query("insert into $this->masterdb.tracker (botAs, site, page, ip, browser, agent, starttime, isJavaScript, lasttime) ".
                 "values('$this->foundBotAs', '$this->siteName', '$this->self', '$this->ip', '$name', '$agent', now(), $java, now())");

    $this->LAST_ID = $this->getLastInsertId();
    //error_log("Database.class.php Server: site=$this->siteName, LAST_ID=" . $this->getLastInsertId());
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
        error_log("Error: code=" . $e->getCode() . ", Message=" . $e->getMessage() . ", e=|" .print_r($e, true)."|");
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

    $this->hitCount = ($this->fetchrow('num')[0]) ?? 0; // This is the number of REAL (non BOT) accesses and NOT Me.
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

  /**
   * checkIfBot() before we do any of the other protected functions in SiteClass.
   * *** This is ONLY called by the constructor in dbAbstract!
   * Calls the public isBot().
   * Checks if the user-agent looks like a bot or if the ip is in the bots table
   * or previous tracker records had something other than zero or 0x2000.
   * Set $this->isBot true/false.
   * return bool.
   * SEE defines.php for the values for TRACKER_BOT, BOTS_SITECLASS
   * $this-isBot() is false if there is no 'match' or no entry in the bots table
   */

  protected function checkIfBot():bool {
    if($this->isMe()) { // I am never a bot!
      return false; 
    }

    return $this->isBot($this->agent);
  }

  /**
   * updatemyip()
   * This is NOT done if we are not using a database or isMe() is false. That is it is NOT me.
   */

  protected function updatemyip():void {
    if($this->ip == DO_SERVER || $this->isMe() === false) {
      // If it is my DigitalOcean server or it is not ME. If it is my server we don't look at the OR.
      return; // This is not me.
    }

    // BLP 2022-01-16 -- NOTE there are only two places where the ip address is added:
    // bartonphillips.com/register.php and bonnieburch.com/addcookie.com.
    
    $sql = "update $this->masterdb.myip set count=count+1, lasttime=now() where myIp='$this->ip'";

    if(!$this->query($sql)) {
      $this->debug("SiteClass $this->siteName: update of myip failed, ip: $this->ip, " .__LINE__, true); // this should not happen
    }
  }

  private function CheckIfTablesExist():array {
    // Do all of the table checks once here.
    // NOTE: $this->debug() function is declared in dbAbstract.class.php.
    
    if(!$this->query("select TABLE_NAME from information_schema.tables where (table_schema = '$this->masterdb') and (table_name = 'bots')")) {
      $this->debug("Database $this->siteName: $this->self: table bots does not exist in the $this->masterdb database: ". __LINE__, true);
    }
    if(!$this->query("select TABLE_NAME from information_schema.tables where (table_schema = '$this->masterdb') and (table_name = 'bots2')"))  {
      $this->debug("Database $this->siteName: $this->self: table bots2 does not exist in the $this->masterdb database: ". __LINE__, true);
    }
    if(!$this->query("select TABLE_NAME from information_schema.tables where (table_schema = '$this->masterdb') and (table_name = 'tracker')")) {
      $this->debug("Database $this->siteName: $this->self: table tracker does not exist in the $this->masterdb database: ". __LINE__, true);
    }
    if(!$this->query("select TABLE_NAME from information_schema.tables where (table_schema = '$this->masterdb') and (table_name = 'myip')")) {
      $this->debug("Database $this->siteName: $this->self: table myip does not exist in the $this->masterdb database: ". __LINE__, true);
    }
    if(!$this->query("select TABLE_NAME from information_schema.tables where (table_schema = '$this->masterdb') and (table_name = 'counter')")) {
      $this->debug("Database $this->siteName: $this->self: table counter does not exist in the $this->masterdb database: ". __LINE__, true);
    }      
    if(!$this->query("select TABLE_NAME from information_schema.tables where (table_schema = '$this->masterdb') and (table_name = 'counter2')")) {
      $this->debug("Database $this->siteName: $this->self: table bots does not exist in the $this->masterdb database: ". __LINE__, true);
    }
    if(!$this->query("select TABLE_NAME from information_schema.tables where (table_schema = '$this->masterdb') and (table_name = 'daycounts')")) {
      $this->debug("Database $this->siteName: $this->self: table daycounts does not exist in the $this->masterdb database: ". __LINE__, true);
    }
    if(!$this->query("select TABLE_NAME from information_schema.tables where (table_schema = '$this->masterdb') and (table_name = 'logagent')")) {
      $this->debug("Database $this->siteName: $this->self: table logagent does not exist in the $this->masterdb database: " . __LINE__, true);
    }

    // The masterdb must be owned by 'barton'. That is the dbinfo->user must be
    // 'barton'. There is one database where this is not true. The 'test' database has a
    // mysitemap.json file that has dbinfo->user as 'test'. It is in the
    // bartonphillips.com/exmples.js/user-test directory.
    // In general all databases that are going to do anything with counters etc. must have a user
    // of 'barton' and $this->nodb false. The program without 'barton' can NOT do any calls via masterdb!

    if(!$this->query("select TABLE_NAME from information_schema.tables where (table_schema = '$this->masterdb') and (table_name = 'myip')")) {
      $this->debug("Database $this->siteName: $this->self: table myip does not exist in the $this->masterdb database: ". __LINE__, true);
    }

    $this->query("select myIp from $this->masterdb.myip");

    while([$ip] = $this->fetchrow('num')) {
      $myIp[] = $ip;
    }
    
    $myIp[] = DO_SERVER; // BLP 2022-04-30 - Add my server.

    return $myIp;
  }

  public function __toString() {
    return __CLASS__;
  }
}
