<?php
/* Well tested and maintained */
// All of the tracking and counting logic that is in this file.
// BLP 2023-12-13 - NOTE: the PDO error for dup key is '23000' not '1063' as in mysqli.

define("DATABASE_CLASS_VERSION", "1.0.13database-pdo"); // BLP 2025-03-07 - move require defines to dbPdo.
                                                        // Moved isMe(), isMyIp() and isBot() to
                                                        // dbPdo.

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

    // BLP 2024-09-05 - moved ip, agent, self and requestUrl to dbPdo and into the nodb section
    // below if we don't instantiate dbPdo.
    
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

        // checkIfBot() must be done before the rest because everyone uses $this->isBot.

        $this->checkIfBot(); // This set $this->isBot. Does a isMe() so I never get set as a bot!

        // Now do all of the rest.

        $this->trackbots();  // both 'bots' and 'bots2'. This also does a isMe() so never get put into the 'bots*' tables.
        $this->tracker();    // This logs Me and everybody else but uses the $this->isBot! 
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
      error_log("Database setSiteCookie: failed, site=$this->siteName, page=$this->self, line=". __LINE__);
      return false;
    }

    return true;
  }

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
  
  /**
   * getIp()
   * Get the ip address
   * @return int ip address
   */

  public function getIp():string {
    return $this->ip;
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
    if(!empty($this->foundBotAs) && $this->dbinfo->engine != "sqlite") {
      $agent = $this->agent;

      try {
        $this->sql("insert into $this->masterdb.bots (ip, agent, count, robots, site, creation_time, lasttime) ".
                     "values('$this->ip', '$agent', 1, " . BOTS_SITECLASS . ", '$this->siteName', now(), now())");
      } catch(Exception $e) {
        if($e->getCode() == 23000) { // duplicate key
          // We need the site info first. This can be one or multiple sites seperated by commas.

          $this->sql("select site from $this->masterdb.bots where ip='$this->ip' and agent='$agent'");

          $who = $this->fetchrow('num')[0]; // get the site which could have multiple sites seperated by commas.

          // Look at who (the haystack) and see if siteName is there. If it is not there this
          // returns false.

          if(strpos($who, $this->siteName) === false) {
            $who .= ", $this->siteName";
          }

          $this->sql("update $this->masterdb.bots set robots=robots | " . BOTS_SITECLASS . ", site='$who', count=count+1, lasttime=now() ".
                       "where ip='$this->ip' and agent='$agent'");
        } else {
          throw new Exception(__CLASS__ . " " . __LINE__ . ":$e");
        }
      }

      // Now do bots2

      $this->sql("insert into $this->masterdb.bots2 (ip, agent, page, date, site, which, count, lasttime) ".
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
    
    // BLP 2021-12-28 -- Explanation.
    // Here we set $java (isJavaScript) to TRACKER_BOT or zero.
    // We then look at isBot and if nothing was found in the bots table and the regex did not
    // match something in the list then isJavaScript will be zero.
    // The visitor was probably a bot and will be added to the bots table as a 0x100 by the cron
    // job checktracker2.php and to the bots2 table. The bot was more than likely curl,
    // wget, python or the like that sets its user-agent to something that would not trigger my
    // regex. Such visitor leave very little footprint.

    $java = $this->isMe() ? TRACKER_ME : TRACKER_ZERO;

    if($this->isBot) {
      $java = TRACKER_BOT;
    }

    $java |= $this->trackerBotInfo; // BLP 2025-01-12 - or in trackerBotInfo from isBot().
                                    // These are the TRACKER_ROBOT or TRACKER_SITEMAP or
                                    // TRACKER_BOT. 
                                    // The values from the bots3 table for this ip. The first two are
                                    // high order bits and TRACKER_BOT is 0x200.
    
    // The primary key is id which is auto incrementing so every time we come here we create a
    // new record.

    $this->sql("insert into $this->masterdb.tracker (botAs, site, page, ip, browser, agent, starttime, isJavaScript, lasttime) ".
                 "values('$this->foundBotAs', '$this->siteName', '$this->self', '$this->ip', '$name', '$agent', now(), $java, now())");

    $this->LAST_ID = $this->getLastInsertId();

    if(DEBUG_TRACKER_BOTINFO === true && $this->isBot) {
      $hexbotinfo = dechex($this->trackerBotInfo);
      $javahex = dechex($java);
      
      error_log("Database tracker: trackerBotInfo=$hexbotinfo, id=$this->LAST_ID, ip=$this->ip, ".
                "site=$this->siteName, page=$this->self, botAs=$this->foundBotAs, java=$javahex, agent=$this->agent, line=".__LINE__);
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
      if($e->getCode() != 23000) {
        error_log("Database counter: Error, code=" . $e->getCode() . ", Message=" . $e->getMessage() . ", e=|" .print_r($e, true)."|");
        throw new Exception(__CLASS__ . " " . __LINE__ . ":$e", $this);
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
        if($e->getCode() == "23000") {
          $this->sql("update logagent set count=count+1, lasttime=datetime('now') ".
                     "where site='$this->siteName' and ip='$this->ip' and agent='$this->agent'");
        } else {
          throw $e;
        }
      }
    }
  }

  // ************
  // End Counters
  // ************

  /**
   * checkIfBot() before we do any of the other protected functions in SiteClass.
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

    if(!$this->sql($sql)) {
      $this->debug("SiteClass $this->siteName: update of myip failed, ip: $this->ip, " .__LINE__, true); // this should not happen
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
      throw new Exception("Can not use CheckIfTablesExist method with sqlite driver");
    }

    // Do all of the table checks once here.
    // We look at the tables and compare them to a list of tables we should have.
    
    $n = $this->sql("show tables from $this->masterdb"); // Request all the tables
    $tbls = [];

    while($tbl = $this->fetchrow('num')[0]) {
      $tbls[] = $tbl;
    }

    $ar = array_diff(['badplayer', 'bots', 'bots2', 'counter', 'counter2', 'myip', 'logagent', 'geo'], $tbls);
    if(!empty($ar)) {
      throw new Exception("Database.class.php: Missing tables -- " . implode(',', $ar));
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
