<?php
/* Well tested and maintained */
// All of the tracking and counting logic that is in this file.
// BLP 2023-12-13 - NOTE: the PDO error for dup key is '23000' not '1063' as in mysqli.

define("DATABASE_CLASS_VERSION", "1.2.1database-pdo"); // BLP 2025-04-19 - moved setSiteCookie() to traits.
                                                       // BLP 2025-04-12 - remove trackbots()
                                                       // add it to tracker().

define("DEBUG_TRACKER_BOTINFO", false); // Change this to false if you don't want the error

/**
 * Database wrapper class
 */

class Database extends dbPdo {
  /**
   * constructor
   * @param $s object.
   * $s should have all of the $this from SiteClass or $_site from mysitemap.json
   * To just pass in the required database options set $s->dbinfo = (object) $ar
   * where $ar is an assocative array with ["host"=>"localhost",...]
   */

  protected $hitCount = 0;

  public function __construct(object $s) {
    // If no 'dbinfo' (no database) in mysitemap.json set everything so the database is not loaded.

    if($s->nodb === true || is_null($s->dbinfo)) {
      // Use the $s values or defaults
      
      $s->ip = $s->ip ?? $_SERVER['REMOTE_ADDR'];
      $s->agent = $s->agent ?? $_SERVER['HTTP_USER_AGENT'];
      $s->self = $s->self ?? htmlentities($_SERVER['PHP_SELF']);
      $s->requestUri = $s->requestUri ?? $_SERVER['REQUEST_URI'];

      // Because $s->nodb or $s->dbinfo is null, set up the rest of these
      
      $s->count = false;
      $s->noTrack = true; // If nodb then noTrack is true also.
      $s->nodb = true;    // Maybe $this->dbinfo was null
      $s->dbinfo = null;  // Maybe nodb was set

      // Put all of the $s values into $this.
    
      foreach($s as $k=>$v) {
        $this->$k = $v;
      }
    
      return; // If we have NO DATABASE just return.
    }

    // We pass $s which is esentially $_site with some stuff added.
    
    parent::__construct($s); // dbPdo constructor.

    // If the user is not 'barton' then noTrack should be set.
    
    if($this->dbinfo->engine != "sqlite" && $this->dbinfo->user == "barton") { // make sure its the 'barton' user!
      $this->myIp = $this->CheckIfTablesExist(); // Check if tables exit and get myIp
    } else {
      $this->myIp = ['195.252.232.86'];
    }

    // These all use database 'barton' ($this->masterdb)
    // and are always done regardless of 'count'!
    // If $this->nodb or there is no $this->dbinfo we have made $this->noTrack true and
    // $this->count false

    if($this->dbinfo->engine == "sqlite") {
      if($this->noTrack !== true) {
        $this->logagent();
      }
    } else {
      if($this->noTrack !== true) {
        $this->logagent();   // This logs Me and everybody else! This is done regardless of $this->isBot or $this->isMe().

        $this->isBot($this->agent); // This set $this->isBot, it also does isMe() so I never get set as a bot!

        // Now do all of the rest.

        $this->tracker();    // This logs Me and everybody else but uses the $this->isBot bitmap! 
        $this->updatemyip(); // Update myip if it is ME

        // If 'count' is false we don't do these counters

        if($this->count) {
          // Get the count for hitCount. The hitCount is always
          // updated (unless the counter table does not exist).

          $this->counter(); // in 'masterdb' database. Does not count Me but always set $this->hitCount.
        }
      }
    }
  } // END Construct

  public function getClassName() {
    return __CLASS__;
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
  
  // updateBots3 helper function used by tracker.php, beacon.php, robots-sitemap.php etc.
  // update the bots3 table
  // @param $ip, the ip address (key)
  // @param $agent, the agent (key)
  // @param $page, the page (key)
  // @param $site, string/integer value for the site
  // @param $botAsBits, the bitmap value of BOTS_...

  public function updateBots3($ip, $agent, $page, $site, $botAsBits) {
    if($this->isMe()) return; // BLP 2025-04-12 - Can not be me!
    
    // $site can be either a string or a bitmapped integer.
    
    if(gettype($site) === 'string') {
      $siteBit = BOTS_SITEBITMAP[$site] | 0; // get bitmap. Supports NO_SITE.
    } else {
      $siteBit = $site;
    }

    try {
      $this->sql("insert into $this->masterdb.bots3 (ip, agent, page, count, robots, site, created) ".
                 "values('$ip', '$agent', '$page', 1, $botAsBits, $siteBit, now()) ".
                 "on duplicate key update robots = robots|$botAsBits, site=site|$siteBit, count=count+1");
    } catch(Exception $e) {
      $err = $e->getCode();
      $errmsg = $e->getMessage();
      error_log("Database updateBots3: ip=$ip, agent=$agent, page=$page, robots=$botAsBits, err=$err, errmsg=$errmsg, line=". __LINE__);
      throw new Exception(__CLASS__ . " " . __LINE__ . ": $errmsg", $err);
    }
  }

  // ********************************************************************************
  // Private and protected methods.
  // Protected methods can be overridden in child classes so most things that would be private
  // should be protected in this base class

  // ***************
  // Start Tracking
  // ***************

  /**
   * tracker()
CREATE TABLE `tracker` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `botAsBits` int DEFAULT '0',
  `site` varchar(25) DEFAULT NULL,
  `page` varchar(255) NOT NULL DEFAULT '',
  `finger` varchar(50) DEFAULT NULL,
  `nogeo` tinyint(1) DEFAULT NULL,
  `browser` varchar(50) DEFAULT NULL,
  `ip` varchar(40) DEFAULT NULL,
  `count` int DEFAULT '1',
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
) ENGINE=MyISAM AUTO_INCREMENT=7626952 DEFAULT CHARSET=utf8mb3;
  */

  protected function tracker():void {
    $agent = $this->agent;

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
          error_log("Database tracker: Error, BROWSER pattern: $mm[0], line=".__LINE__);
          continue;
      }
    }
    // BLP 2023-10-05 - end `browser` logic.

    // Look for iPhone or Android

    if(preg_match("~android|iphone~i", $agent, $m) === 1) {
      $name = "{$m[0]},$name";
    }
    
    // BLP 2021-12-28 -- Explanation.
    // Here we set $java (isJavaScript) to TRACKER_BOT or zero.
    // We then look at isBot and if nothing was found in the bots table and the regex did not
    // match something in the list then isJavaScript will be zero.
    // The visitor was probably a bot and will be added to the bots table as BOTS_CRON_CHECKTRACKER
    // by the cron job checktracker2.php and to the bots2 table. The bot was more than likely curl,
    // wget, python or the like that sets its user-agent to something that would not trigger my
    // regex. Such visitor leave very little footprint.

    $java = $this->isMe() ? TRACKER_ME : TRACKER_ZERO;

    if($this->isBot) {
      $java = TRACKER_BOT;
    }

    $java |= $this->trackerBotInfo; // BLP 2025-01-12 - or in trackerBotInfo from isBot().
                                    // These are the TRACKER_ROBOT or TRACKER_SITEMAP or
                                    // TRACKER_BOT. 
                                    // The values from the bots table for this ip. The first two are
                                    // high order bits and TRACKER_BOT is 0x200.
    
    // The primary key is id which is auto incrementing so every time we come here we create a
    // new record.

    $page = basename($this->self); // only the file name.

    $this->sql("insert into $this->masterdb.tracker ".
               "(site, page, ip, browser, agent, botAsBits, starttime, isJavaScript) ".
               "values('$this->siteName', '$page', '$this->ip', '$name', ".
               "'$agent', $this->botAsBits, now(), $java)");

    $this->LAST_ID = $this->getLastInsertId();

    // Now update the bots3 table. 'site' can be either an integer or a string.
    
    $this->updateBots3($this->ip, $agent, $page, $this->siteName, $this->botAsBits);
    
    // BLP 2025-04-11 - in the constructor we do trackbots() first and then tracker(). The bots3
    // information should be added in trackbots().
    
    if(DEBUG_TRACKER_BOTINFO === true && $this->isBot) {
      $hexbotinfo = dechex($this->trackerBotInfo);
      $javahex = dechex($java);

      // BLP 2025-04-03 - botAs is a bitmap.

      $hexBotAsBits = dechex($this->botAs);
      error_log("Database tracker trackerBotInfo=$hexbotinfo: id=$this->LAST_ID, ip=$this->ip, ".
                "site=$this->siteName, page=$this->self, botAsBits=$hexBotAsBits, java=$javahex, agent=$this->agent, line=".__LINE__);
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

    try {
      $this->sql("insert into $this->masterdb.counter (site, filename, count, lasttime) values('$this->siteName', '$filename', 1, now())");
    } catch(Exception $e) {
      if(($err = $e->getCode()) != 23000) {
        $errmsg =$e->getMessage();
        error_log("Database counter: Error, code=" . $e->getCode() . ", Message=" . $e->getMessage() . ", e=|" .print_r($e, true)."|");
        throw new Exception(__CLASS__ . " " . __LINE__ . ": $errmsg", $err);
      }
    }

    // Is it me?
    
    if(!$this->isMe()) { // No it is NOT me.
      // realcnt is ONLY NON BOTS

      $realcnt = $this->isBot ? 0 : 1;

      // count is total of ALL hits that are NOT ME!

      $sql = "update $this->masterdb.counter set count=count+1, realcnt=realcnt+$realcnt, lasttime=now() ".
             "where site='$this->siteName' and filename='$filename'";

      $this->sql($sql);
    }

    // Now retreive the hit count value after it may have been incremented above. NOTE, I am NOT
    // included here.

    $sql = "select realcnt from $this->masterdb.counter where site='$this->siteName' and filename='$filename'";

    $this->sql($sql);

    $this->hitCount = ($this->fetchrow('num')[0]) ?? 0; // This is the number of REAL (non BOT) accesses and NOT Me.
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

    if($this->dbinfo->engine != "sqlite") {
      $sql = "insert into $this->masterdb.logagent (site, ip, agent, count, created, lasttime) " .
             "values('$this->siteName', '$this->ip', '$this->agent', '1', now(), now()) ".
             "on duplicate key update count=count+1, lasttime=now()";

      $this->sql($sql);
    } else {
      $sql = "insert into logagent (site, ip, agent, count, created, lasttime) " .
             "values('$this->siteName', '$this->ip', '$this->agent', '1', datetime('now'), datetime('now'))";
      try {
        $this->sql($sql);
      } catch(Exception $e) {
        if(($err = $e->getCode()) == "23000") {
          $this->sql("update logagent set count=count+1, lasttime=datetime('now') ".
                     "where site='$this->siteName' and ip='$this->ip' and agent='$this->agent'");
        } else {
          $errmsg = $e->getMessage();
          throw new Exception(__CLASS__ . " " . __LINE__ . ": $errmsg", $err);
        }
      }
    }
  }

  // ************
  // End Counters
  // ************

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

    if(!$this->sql($sql)) {
      throw new Exception(__CLASS__. " ". __LINE__. ": site=$this->siteName, update of myip failed, ip: $this->ip"); // this should not happen
    }
  }

  /*
   * CheckIfTablesExist()
   * Uses 'show table;' and array_deff() to determin if the table we need are there.
   * @return: myIp
   */
  
  private function CheckIfTablesExist():array {
    // If we are NOT using the sqlite driver we can do a show.
    
    if($this->dbinfo->engine == "sqlite") {
      throw new Exception(__CLASS__ . " " . __LINE__ . ": $errmsg", $err);
    }

    // Do all of the table checks once here.
    // We look at the tables and compare them to a list of tables we should have.
    
    $n = $this->sql("show tables from $this->masterdb"); // Request all the tables
    $tbls = [];

    while($tbl = $this->fetchrow('num')[0]) {
      $tbls[] = $tbl;
    }

    $ar = array_diff(['badplayer', 'bots3', 'counter', 'myip', 'logagent', 'geo', 'interaction'], $tbls);
    if(!empty($ar)) {
      [$err, $errmsg, $errfile, $errline] = error_get_last();
      
      throw new Exception("Database.class.php: Missing tables -- $errmsg, $errfile, $errline", $err);
    }
    
    $this->sql("select myIp from $this->masterdb.myip");
        
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
