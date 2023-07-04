<?php
/* MAINTAINED and WELL TESTED */

define("ABSTRACT_CLASS_VERSION", "3.0.1ab"); // BLP 2023-03-07 - remove $arg use $dbinfo. Pass $dbinfo items to dbMysqli

// Abstract database class
// Most of this class is implemented here. This keeps us from having to duplicate this over and
// over again in each higher level class like SiteClass or Database.
// The db engines (dbMysqli.class.php, etc.) have most of these methods implemented.

require_once(__DIR__ . "/../defines.php"); // This has the constants for TRACKER, BOTS, BOTS2, and BEACON

abstract class dbAbstract {
  protected $db;
  
  /*
   * constructor.
   * @param: object $s. This usually has the info from mysitemap.json.
   */
  
  protected function __construct(object $s) {
    global $__info; // BLP 2023-01-24 - added for node programs has [0]=ip, [1]=agent. See /examples/node-programs/server.js

    $this->errorClass = new ErrorClass();

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
    $dbinfo = $this->dbinfo;

    if(isset($dbinfo->engine) === false) {
      $this->errno = -2;
      $this->error = "'engine' not defined";
      throw new SqlException(__METHOD__, $this);
    }

    // BLP 2023-01-26 - currently there is only ONE viable engine and that is dbMysqli
    
    $class = "db" . ucfirst(strtolower($dbinfo->engine));
    
    if(class_exists($class)) {
      $db = new $class($dbinfo);
    } else {
      throw new SqlException(__METHOD__ . ": Class Not Found : $class<br>", $this);
    }

    if(is_null($db) || $db === false) {
      throw new SqlException(__METHOD__ . ": Connect failed", $this);
    }
    
    $this->db = $db;

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
  }

  // Each child class needs to have a __toString() method

  abstract public function __toString() ;
    
  public static function getAbstractName() {
    return __CLASS__;
  }
  
  public static function getVersion() {
    return ABSTRACT_CLASS_VERSION;
  }

  /**
   * getDbName()
   * This is the name of the database, like 'bartonphillips' or 'barton'
   */
  
  public function getDbName():string {
    $database = $this->db->database;
    if($database) {
      return $database;
    }
    return $this->db->db->database;
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
  
  // The following methods either execute or if the method is not defined throw an Exception
  
  public function query($query) {
    if(method_exists($this->db, 'query')) {
      return $this->db->query($query);
    } else {
      throw new Exception(__METHOD__ . " not implemented");
    }
  }
  
  public function fetchrow($result=null, $type="both") {
    if(method_exists($this->db, 'fetchrow')) {
      return $this->db->fetchrow($result, $type);
    } else {
      throw new Exception(__METHOD__ . " not implemented");
    }
  }

  public function finalize($result) {
    if(method_exists($this->db, 'finalize')) {
      return $this->db->finalize($result);
    } else {
      throw new Exception(__METHOD__ . " not implemented");
    }
  }

  public function queryfetch($query, $type=null, $retarray=false) {
    if(method_exists($this->db, 'queryfetch')) {
      return $this->db->queryfetch($query, $type, $retarray);
    } else {
      throw new Exception(__METHOD__ . " not implemented");
    }
  }

  public function getLastInsertId() {
    if(method_exists($this->db, 'getLastInsertId')) {
      return $this->db->getLastInsertId();      
    } else {
      throw new Exception(__METHOD__ . " not implemented");
    }
  }

  public function getResult() {
    if(method_exists($this->db, 'getResult')) {
      return $this->db->getResult();
    } else {
      throw new Exception(__METHOD__ . " not implemented");
    }
  }
    
  public function escape($string) {
    if(method_exists($this->db, 'escape')) {
      return $this->db->escape($string);
    } else {
      throw new Exception(__METHOD__ . " not implemented");
    }
  }
  
  public function escapeDeep($value) {
    if(method_exists($this->db, 'escapeDeep')) {
      return $this->db->escapeDeep($value);
    } else {
      throw new Exception(__METHOD__ . " not implemented");
    }
  }
  
  public function getNumRows($result=null) {
    if(method_exists($this->db, 'getNumRows')) {
      return $this->db->getNumRows($result);
    } else {
      throw new Exception(__METHOD__ . " not implemented");
    }
  }
  
  public function prepare($query) {
    if(method_exists($this->db, 'prepare')) {
      return $this->db->prepare($query);
    } else {
      throw new Exception(__METHOD__ . " not implemented");
    }
  }
  
  public function bindParam($format) {
    if(method_exists($this->db, 'bindParam')) {
      return $this->db->bindParam($format);
    } else {
      throw new Exception(__METHOD__ . " not implemented");
    }
  }
  
  public function bindResults($format) {
    if(method_exists($this->db, 'bindResults')) {
      return $this->db->bindResults($format);
    } else {
      throw new Exception(__METHOD__ . " not implemented");
    }
  }

  public function execute() {
    if(method_exists($this->db, 'execute')) {
      return $this->db->execute();
    } else {
      throw new Exception(__METHOD__ . " not implemented");
    }
  }

  public function getErrorInfo() {
    if(method_exists($this->db, 'getErrorInfo')) {
      return $this->db->getErrorInfo();
    } else {
      throw new Exception(__METHOD__ . " not implemented");
    }
  }

  /*
   * debug()
   * @param $exit. If true throw and exception. Else just output via error_log().
   * If noErrorLog is set in mysitemap.json then don't do error_log()
   */

  protected function debug(string $msg, $exit=false):void {
    if($this->noErrorLog === true) {
      if($exit === true) {
        throw new SqlException($msg, $this);
      }
      return;
    }

    error_log("debug:: $msg");

    if($exit === true) {
      throw new SqlException($msg, $this);
    }
  }

  /*
   * Check if the required tables are presssent.
   * Returns myIp array.
   */
  
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

    //error_log("Database user: " . $this->user);
    //error_log("Database dbinfo->user: " . $this->dbinfo->user);
    
    if(!$this->query("select TABLE_NAME from information_schema.tables where (table_schema = '$this->masterdb') and (table_name = 'myip')")) {
      $this->debug("Database $this->siteName: $this->self: table myip does not exist in the $this->masterdb database: ". __LINE__, true);
    }

    $this->query("select myIp from $this->masterdb.myip");

    while([$ip] = $this->fetchrow('num')) {
      $myIp[] = $ip;
    }
    
    $myIp[] = DO_SERVER; // BLP 2022-04-30 - Add my server.

    //error_log("Database after myIp set, this: " . print_r($this, true));
   
    return $myIp;
  }
}
