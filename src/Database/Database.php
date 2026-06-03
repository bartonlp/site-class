<?php
/* Well tested and maintained */
// All of the tracking and counting logic that is in this file.
// BLP 2023-12-13 - NOTE: the PDO error for dup key is '23000' not '1063' as in mysqli.

namespace bartonlp\SiteClass\Database;

/**
 * @file database/Database.php
 * @package SiteClass
 */
define("DATABASE_CLASS_VERSION", "7.0.5");
/**
 * @file database/Database.php
 * @package SiteClass
 */
define("DEBUG_TRACKER_BOTINFO", false); // Change this to false if you don't want the error

/**
 * Database. Second in the SiteClass framework
 *
 * Database extends dbPdo.
 * dbPdo extends PDO. This is the standard PHP PDO class.
 *
 * @package SiteClass
 * @author Barton Phillips <barton@bartonphillips.com>
 * @link http://www.bartonphillips.com
 * @copyright Copyright (c) 2025, Barton Phillips
 * @license MIT
 * @see https://github.com/bartonlp/site-class My GitHub repository
 */
class Database extends dbPdo {
  protected $hitCount = 0;
  public $nodb;     // If $nodb we have non database. 
  public $noTrack;  // This will stop everything except $logagent
  public $noGeo;    // If $noGeo we do not Google location
  
  /**
   * Database constructor.
   *
   * The object passed in is usually from mysitemap.json, which contains all
   * important configuration settings.
   *
   * @param object $s Configuration object from mysitemap.json
   * @see https://bartonlp.org/docs/mysitemap.json for full details
   */
  public function __construct(object $s) {
    parent::__construct($s); // After the constructor we have our $this.
    
    // If we are doSiteClass is true we will do FULL database.
    
    if($this->doSiteClass === true && $this->dbinfo->engine === "mysql") {
      // If we do have doSiteClass we will do FULL tracking.

      try {
        $this->myIp = $this->CheckIfTablesExist(); // Check if tables exit and get myIp
      } catch(\Throwable $e) {
        throw new \InvalidArgumentException(__CLASS__ . " " . __LINE__ . ": CheckIfTablesExist=" . $e->getMessage());
      }

      $this->isBot($this->agent); // This set $this->isBot, it also does isMe() so I never get set as a bot!
      $this->tracker();    // This logs Me and everybody else but uses the $this->isBot bitmap!
      $this->updatemyip(); // Update myip if it is ME

      // If 'count' is false we don't do these counters

      if($this->count === true) {
        // Get the count for hitCount. The hitCount is always
        // updated (unless the counter table does not exist).

        $this->counter(); // in 'masterdb' database. Does not count Me but always set $this->hitCount.
      }
    } else {
      // NO doSiteClass

      $this->trackerLocationJs = $this->trackerLocation = $this->beaconLocation = " ";
      
      if($this->dbinfo->engine == "mysql" || $this->dbinfo->engine == "sqlite") {
        $this->noGeo = true;
        $this->noTrack = true;
      } else {
        throw new \InvalidArgumentException(__CLASS__ . " " . __LINE__ . ": DATABASE-ENGINE=$this->dbinfo->database, $this->dbinfo->engine");
      }
    }
  } // END Construct

  /**
   * Get class Name
   *
   * @return string
   */
  public function getClassName(): string {
    return __CLASS__;
  }

  /**
   * Get the version of SiteClass
   *
   * @return string The version from the define at the start of SiteClass
   */
  public static function getVersion():string {
    return DATABASE_CLASS_VERSION;
  }
  
  /**
   * getHitCount() Gets the number of times someone visited our page
   *
   * @return int
   */
  public function getHitCount():int {
    return $this->hitCount;
  }

  // ********************************************************************************
  // Private and protected methods.
  // Protected methods can be overridden in child classes so most things that would be private
  // should be protected in this base class

  // ***************
  // Start Tracking
  // ***************

  /**
   * Tracks the current visitor and logs tracking data to the database.
   *
   * This method:
   * - Analyzes the user-agent string to determine browser, engine, bot status, and tracker bits.
   * - Logs the visitor into the `tracker` table with appropriate flags.
   * - Updates the `bots3` table to record bot signature behavior.
   * - Sets internal tracking variables used elsewhere in the request lifecycle.
   * - Optionally logs diagnostic information if `DEBUG_TRACKER_BOTINFO` is enabled.
   *
   * Side effects:
   * - Sets `$this->id` and `$this->LAST_ID` to the ID of the inserted `tracker` record.
   * - Updates `$this->isBot` based on `getBrowserInfo()`.
   * - May emit a line to the PHP error log.
   *
   * @internal
   * @return void
   * @throws \PDOException If the SQL insert or bot update fails
   * @see getBrowserInfo() For parsing the user-agent string
   * @see updateBots3() For bot signature updates
   */
  protected function tracker():void {
    $agent = $this->agent;
    $java = 0;
    
    [$browser, $engine, $botbits, $trackerbits, $this->isBot] = getBrowserInfo($agent); // from helper-functions.php

    // Explanation.
    // Here we set $java (isJavaScript) to TRACKER_BOT or zero.
    // We then look at isBot and if nothing was found in the bots table and the regex did not
    // match something in the list and $isBot from getBrowserInfo() is false
    // then isJavaScript will be zero.
    // The visitor was probably a bot and will be added to the bots table as BOTS_CRON_CHECKTRACKER
    // by the cron job checktracker2.php. The bot was more than likely curl,
    // wget, python or the like that sets its user-agent to something that would not trigger my
    // regex. Such visitor leave very little footprint.

    $java = $this->isMe() ? TRACKER_ME : TRACKER_ZERO;

    if($this->isBot) {
      $java |= TRACKER_BOT;
      $botAsBits |= $botbits | BOTS_SITECLASS; // BLP 2025-04-23 -  
    }

    $java |= $trackerbits;
    
    // The primary key is id which is auto incrementing so every time we come here we create a
    // NEW RECORD.

    $page = basename($this->self); // only the file name.

    // This is the initial insert into tracker. It should happen before anything else.
    // Combine $browser and $engine into $name.

    $name = "$browser,$engine";
    
    $this->sql("insert into $this->masterdb.tracker
(site, page, ip, browser, agent, botAsBits, starttime, isJavaScript) 
values('$this->siteName', '$page', '$this->ip', '$name',
'$agent', $this->botAsBits, now(), $java)");

    // Get the id of this insert.
    
    $this->LAST_ID = $this->id = $this->getLastInsertId(); 

    // Now update the bots3 table. 'site' can be either an integer or a string. This should also be
    // a NEW RECORD even though this method does an insert/update.

    $this->updateBots3($this->ip, $agent, $page, $this->siteName, $this->botAsBits);
    
    if(DEBUG_TRACKER_BOTINFO === true && !$this->isMe()) {
      $hexjava = dechex($java);
      $hexBotAsBits = dechex($this->botAsBits);

      $trueFalse = $this->isBot === true ? "true" : 'false';
      
      logInfo("Database browserInfo: id=$this->id, ip=$this->ip, site=$this->siteName, page=$this->self, ".
              "botAsBits=$hexBotAsBits, java=$hexjava, browser=$browser, engine=$engine, isBot=$trueFalse, ".
              "agent=$this->agent, line=". __LINE__);
    }
  }

  /**
   * Updates and retrieves the real (non-bot, non-me) page hit count.
   *
   * This method:
   * - Increments the `counter` table for the current page and site.
   * - Increments `realcnt` only if the visitor is not a bot and not the site owner (`isMe()`).
   * - Retrieves the updated `realcnt` value and stores it in `$this->hitCount`.
   *
   * Side effects:
   * - Modifies the `counter` table in the database.
   * - Sets `$this->hitCount` to the current real (non-bot) hit count.
   *
   * @internal
   * @return void
   * @throws \PDOException If the SQL insert or select fails
   */
  protected function counter():void {
    if($this->dbinfo->engine === 'sqlite') return;
    
    $site = $this->siteName;
    $filename = $this->self; // get the name of the file
    $realcnt = 0;
    
    if(!$this->isMe()) {
      $realcnt = $this->isBot ? 0 : 1; // $realcnt is the number of NON robots
    }

    $this->sql("insert into $this->masterdb.counter
(site, filename, count) values('$site', '$filename', 1)
on duplicate key update count=count+1, realcnt=realcnt+$realcnt");
        
    // Now retreive the hit count value after it may have been incremented above. NOTE, I am NOT
    // included here.

    $this->sql("select realcnt from $this->masterdb.counter where site='$site' and filename='$filename'");

    $this->hitCount = ($this->fetchrow('num')[0]) ?? 0; // This is the number of REAL (non BOT) accesses and NOT Me.
  }

  /**
   * Logs or updates the agent information for the current visitor.
   *
   * This method:
   * - Records the visitor's site, IP, and user-agent string into the `logagent` table.
   * - Increments a hit counter (`count`) and updates `lasttime` on repeated visits.
   * - Supports both MySQL and SQLite, using conditional SQL syntax and error handling.
   *
   * Behavior notes:
   * - In SQLite, handles duplicate keys by catching the exception and issuing a manual `UPDATE`.
   * - MySQL uses `ON DUPLICATE KEY UPDATE` for efficiency.
   *
   * Side effects:
   * - Writes to the `logagent` table in `$this->masterdb`.
   * - May throw a wrapped exception in case of an unexpected database error.
   *
   * @internal
   * @return void
   * @throws \Exception If SQLite update fails for a reason other than a duplicate key
   * @throws \PDOException If the underlying `sql()` call fails in MySQL
   * @see sql() Framework method used to execute database queries
   */
  protected function logagent():void {
    // Key is 'site', 'ip', 'agent'.
    
    $site = $this->siteName;
    $ip = $this->ip;
    $agent = $this->agent;
    $masterdb = "{$this->masterdb}.";
    
    if($this->dbinfo->engine !== "sqlite") {
      // This is the mysql logic.
      
      $this->sql("insert into {$masterdb}logagent (site, ip, agent, count, created, lasttime)
values('$site', '$ip', '$agent', '1', now(), now())
on duplicate key update count=count+1, lasttime=now()");
    } else {
      // This is the sqlite locic.
      // We always try to write the lobagent

      $this->sql("CREATE TABLE IF NOT EXISTS logagent ".
                 "(`site` varchar(25) NOT NULL DEFAULT '', ".
                 "`ip` varchar(40) NOT NULL DEFAULT '', ".
                 "`agent` varchar(254) NOT NULL, ".
                 "`count` int DEFAULT NULL, ".
                 "`created` text DEFAULT NULL, ".
                 "`lasttime` text DEFAULT NULL, ".
                 "PRIMARY KEY (`site`,`ip`,`agent`))");

      // Now do an sqlite insert

      $this->sql("INSERT INTO logagent (site, ip, agent, count, lasttime) ".
                 "VALUES (?, ?, ?, 1, datetime('now','localtime')) ".
                 "ON CONFLICT(site, ip, agent) ".
                 "DO UPDATE SET ".
                 "count = count + 1, ".
                 "lasttime = datetime('now','localtime')", [$site, $ip, $agent]);

      $masterdb = null; // If we are SQLITE make $masterdb null.
    }

    if($this->count === true) {
      $this->sql("select count from {$masterdb}logagent where site='$site' and ip='$ip' and agent='$agent'");
      $count = $this->fetchrow('num')[0];
      $this->hitCount = $count;
    }
  }
  
  // ************
  // End Trackers
  // ************

  /**
   * Updates the visit count for your personal IP in the `myip` table.
   *
   * This method:
   * - Increments the visit count for `$this->ip` only if the visitor is "me" and not the DO server.
   * - Helps track personal use separate from general site traffic.
   *
   * Side effects:
   * - Issues an `UPDATE` query to the `myip` table in `$this->masterdb`.
   *
   * @internal
   * @return void
   * @throws \Exception If the SQL update fails
   */
  protected function updatemyip():void {
    if($this->ip == DO_SERVER || $this->isMe() === false) {
      // If it is my DigitalOcean server or it is not ME. If it is my server we don't look at the OR.
      return; // This is not me.
    }

    // NOTE there are only two places where the ip address is added:
    // bartonphillips.com/register.php and bonnieburch.com/addcookie.com.
    
    $sql = "update $this->masterdb.myip set count=count+1, lasttime=now() where myIp='$this->ip'";

    if(!$this->sql($sql)) {
      throw new \InvalidArgumentException(__CLASS__. " ". __LINE__. ": site=$this->siteName, update of myip failed, ip: $this->ip"); // this should not happen
    }
  }

  /**
   * Verifies the existence of required tables in the current database.
   *
   * This method:
   * - Checks for the presence of all required tables using `SHOW TABLES`.
   * - Throws an exception if any required table is missing.
   * - Retrieves and returns all IPs from the `myip` table, including the DO server.
   *
   * Only runs on MySQL-compatible engines. SQLite will trigger an exception.
   *
   * Side effects:
   * - Throws if critical schema components are missing.
   *
   * @internal
   * @return array List of known IPs, including `DO_SERVER`
   */
  private function CheckIfTablesExist(): array {
    // Do all of the table checks once here.
    // We look at the tables and compare them to a list of tables we should have.
    
    $this->sql("show tables from $this->masterdb"); // Request all the tables
    $tbls = [];

    while([$tbl] = $this->fetchrow('num')) {
      $tbls[] = $tbl;
    }

    $ar = array_diff(['badplayer', 'bots3', 'counter', 'myip', 'logagent', 'geo', 'interaction'], $tbls);
    if(!empty($ar)) {
      [$err, $errmsg, $errfile, $errline] = error_get_last();
      
      throw new \InvalidArgumentException("Database.php: Missing tables -- $errmsg, $errfile, $errline", $err);
    }
    
    $this->sql("select myIp from $this->masterdb.myip");
        
    while([$ip] = $this->fetchrow('num')) {
      $myIp[] = $ip;
    }
    
    $myIp[] = DO_SERVER; // Add my server.

    return $myIp;
  }

  /**
   * Returns the class name as a string.
   *
   * @return string The name of the class (`Database` or subclass)
   */
  public function __toString(): string {
    return __CLASS__;
  }
}
