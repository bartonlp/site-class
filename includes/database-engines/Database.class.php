<?php
/* Well tested and maintained */
// BLP 2022-06-14 - moved setSiteCookie() from SiteClass to here (SiteClass extends Database).
// BLP 2022-05-26 - now I do a parent::_construct to get everything.
// SiteClass has a new version number.
// Added CheckIfTablesExist().

define("DATABASE_CLASS_VERSION", "2.0.0");

/**
 * Database wrapper class
 */

class Database extends dbAbstract {
  /**
   * BLP 2021-10-28 -- major overhall. Now we pass only an object in, before we passed a complicated 'mixed' in.
   * There is no program that uses anything but $_site from mysitemap.json.
   *
   * constructor
   * @param $s object. $isSiteClass bool.
   * $s should have all of the $this from SiteClass or $_site from mysitemap.json
   * To just pass in the required database options set $s->dbinfo = (object) $ar
   * where $ar is an assocative array with ["host"=>"localhost",...]
   * $isSiteClass is true if this is from SiteClass.
   */

  public function __construct(object $s, ?bool $isSiteClass=null) {
    $this->errorClass = new ErrorClass();
    
    // If this is NOT from SiteClass then add these variable.
    
    if(!$isSiteClass) {
      $s->ip = $_SERVER['REMOTE_ADDR'];
      $s->agent = $_SERVER['HTTP_USER_AGENT'] ?? ''; // BLP 2022-01-28 -- CLI agent is NULL so make it blank ''
      $s->self = htmlentities($_SERVER['PHP_SELF']); // BLP 2021-12-20 -- add htmlentities to protect against hacks.
      $s->requestUri = $_SERVER['REQUEST_URI']; // BLP 2021-12-30 -- change from $this->self
    }
    
    // Do the parent dbAbstract constructor
    
    parent::__construct($s);

    date_default_timezone_set("America/New_York");

    if($this->nodb || !$this->dbinfo) {
      return;
    }

    $db = null;
    $arg = $this->dbinfo;

    // BLP BLP 2022-01-14 -- The Database password is now in /home/barton/database-password
    // on bartonlp.org

    $password = ($this->dbinfo->password) ?? require("/home/barton/database-password");
    
    if(isset($arg->engine) === false) {
      $this->errno = -2;
      $this->error = "'engine' not defined";
      throw new SqlException(__METHOD__, $this);
    }

    $class = "db" . ucfirst(strtolower($arg->engine));
    if(class_exists($class)) {
      $db = @new $class($arg->host, $arg->user, $password, $arg->database, $arg->port);
    } else {
      throw new SqlException(__METHOD__ .": Class Not Found : $class<br>");
    }

    if(is_null($db) || $db === false) {
      throw new SqlException(__METHOD__ . ": Connect failed", $this);
    }
    
    $this->db = $db;

    if($this->dbinfo->user == "barton" || $this->user == "barton") { // make sure its the 'barton' user!
      $this->myIp = $this->CheckIfTablesExist(); // Check if tables exit and get myIp
    }

    // Escapte the agent in case it has something like an apostraphy in it.
    
    $this->agent = $this->escape($this->agent);
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
      error_log("SiteClass $this->siteName: $this->self: setcookie failed ". __LINE__);
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
    return (array_intersect([$ip], $this->myIp)[0] === null) ? false : true;
  }
  
  /**
   * isMe()
   * Check if this access is from ME
   * @return true if $this->ip == $this->myIp else false!
   */

  public function isMe():bool {
    return $this->isMyIp($this->ip);
  }

  /**
   * getVersion()
   * @return string version number
   */

  public function getVersion():string {
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
   * Determines if an agent is a bot or not.
   * @return bool
   * Side effects:
   *  it sets $this->isBot
   *  it sets $this->foundBotAs
   * These side effects are used by checkIfBot():void see below.
   */
  
  public function isBot(string $agent):bool {
    $this->isBot = false;

    if($this->isMe()) {
      return $this->isBot; 
    }

    if(($x = preg_match("~\+*https?://|@|bot|spider|scan|HeadlessChrome|python|java|wget|nutch|perl|libwww|lwp-trivial|curl|PHP/|urllib|".
                        "crawler|GT::WWW|Snoopy|MFC_Tear_Sample|HTTP::Lite|PHPCrawl|URI::Fetch|Zend_Http_Client|".
                        "http client|PECL::HTTP~i", $agent)) === 1) { // 1 means a match
      $this->isBot = true;
      $this->foundBotAs = BOTAS_MATCH; // "preg_match";
      return $this->isBot;
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
        $tmp = ltrim($tmp, ','); // remove the leading comma
        $this->foundBotAs = $tmp; //'bots table' plus $tmp;
        $this->isBot = true; // BOTAS_TABLE plus robot and/or sitemap
      } else {
        $this->foundBotAs = BOTAS_NOT;
        $this->isBot = false;
      }
      //error_log("SiteClass checkIfBot: foundBotAs=$this->foundBotAs, ip=$this->ip, agent=$this->agent, " . __LINE__);
      return $this->isBot;
    }

    // The ip was NOT in the bots table either.

    $this->foundBotAs = BOTAS_NOT; // not a bot
    $this->isBot = false;
    return $this->isBot;
  }
  
  /**
   * checkIfBot() before we do any of the other protected functions in SiteClass.
   * Calls the public isBot().
   * Checks if the user-agent looks like a bot or if the ip is in the bots table
   * or previous tracker records had something other than zero or 0x2000.
   * Set $this->isBot true/false.
   * return nothing.
   * SEE defines.php for the values for TRACKER_BOT, BOTS_SITECLASS
   * $this-isBot is false or there is no entry in the bots table
   */

  protected function checkIfBot():void {
    $this->isBot($this->agent);
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

  /*
   * Check if the required tables are presssent.
   * Returns myIp array.
   */
  
  private function CheckIfTablesExist():array {
    // Do all of the table checks once here.

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

    //error_log("Database user: " . $this->user);
    //error_log("Database dbinfo->user: " . $this->dbinfo->user);
    
    if(!$this->query("select TABLE_NAME from information_schema.tables where (table_schema = '$this->masterdb') and (table_name = 'myip')")) {
      $this->debug("Database $this->siteName: $this->self: table myip does not exist in the $this->masterdb database: ". __LINE__, true);
    }

    $this->query("select myIp from $this->masterdb.myip");

    while($ip = $this->fetchrow('num')[0]) {
      $myIp[] = $ip;
    }
    
    $myIp[] = DO_SERVER; // BLP 2022-04-30 - Add my server.

    //error_log("Database after myIp set, this: " . print_r($this, true));
   
    return $myIp;
  }
  
  public function __toString() {
    return __CLASS__;
  }
}
