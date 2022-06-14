<?php
/* Well tested and maintained */
// BLP 2022-06-14 - moved setSiteCookie() from SiteClass to here (SiteClass extends Database).
// BLP 2022-05-26 - now I do a parent::_construct to get everything.
// SiteClass has a new version number.
// Added CheckIfTablesExist().

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
    ErrorClass::init(); // We should do this. If already done it just returns.

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

    switch($arg->engine) {
      case "mysqli":
        $class = "db" . ucfirst(strtolower($arg->engine));
        if(class_exists($class)) {
          $db = @new $class($arg->host, $arg->user, $password, $arg->database, $arg->port);
        } else {
          throw new SqlException(__METHOD__ .": Class Not Found : $class<br>");
        }
        break;
      case "sqlite3":
        // This is native sqlite not via pdo.
        $class = "dbSqlite";
        if(class_exists($class)) {
          $db = @new $class($arg->host, $arg->user, $password, $arg->database);
        } else {
          throw new SqlException(__METHOD__ .": Class Not Found : $class<br>");
        }      
        break;
      default:
        throw new SqlException(__METHOD__ . ": Engine $arg->engine not valid", $this);
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
    
    //error_log("Database after CheckIfTablesExist this: " . print_r($this, true));
  }

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

    error_log("cookie: $cookie, value: $value, options: " . print_r($options, true));
    return true;
  }

  /*
   * Check it the required tables are presssent.
   * Returns myIp.
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
