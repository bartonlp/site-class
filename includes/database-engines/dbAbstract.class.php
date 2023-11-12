<?php
/* MAINTAINED and WELL TESTED */
// BLP 2023-11-12 - Moved my_exceptionhandler into Abstract class.

define("ABSTRACT_CLASS_VERSION", "3.1.0ab"); // BLP 2023-03-07 - remove $arg use $dbinfo. Pass $dbinfo items to dbMysqli

// Abstract database class
// Most of this class is implemented here. This keeps us from having to duplicate this over and
// over again in each higher level class like SiteClass or Database.
// The db engines (dbMysqli.class.php, etc.) have most of these methods implemented.

// BLP 2023-10-24 - moved this require here from several other places. If it is here I don't need
// to require it anywhere else that uses the SiteClass or Database.

require_once(__DIR__ . "/../defines.php"); // This has the constants for TRACKER, BOTS, BOTS2, and BEACON

abstract class dbAbstract {
  protected $db;
  
  /*
   * constructor.
   * @param: object $s. This usually has the info from mysitemap.json.
   */
  
  protected function __construct(object $s) {
    global $__info; // BLP 2023-01-24 - added for node programs has [0]=ip, [1]=agent. See /examples/node-programs/server.js

    header("Accept-CH: Sec-Ch-Ua-Platform,Sec-Ch-Ua-Platform-Version,Sec-CH-UA-Full-Version-List,Sec-CH-UA-Arch,Sec-CH-UA-Model"); // BLP 2023-10-02 - ask for sec headers

    set_exception_handler("dbAbstract::my_exceptionhandler"); // BLP 2023-11-12 - Moved into Abstract class from ErrorClass.
    
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
   * my_exceptionhandler
   * Must be a static
   * BLP 2023-11-12 - moved from ErrorClas.class.php to here.
   */

  public static function my_exceptionhandler($e) {
    $from =  get_class($e);

    $error = $e; // get the full error message

    // If this is a SqlException then the formating etc. was done by the class

    if($from != "SqlException") {
      // NOT SqlException

      // Get Trace information

      $traceback = '';

      foreach($e->getTrace() as $v) {
        // The key here is a numeric and
        // $v is an assoc array with keys 'file', 'line', 'function', 'class' and 'args'.

        $args = ''; // This will hold the $v2 values

        foreach($v as $k=>$v1) {
          // $v is an assoc array 'file, line, ...'
          // most $v1's are strings. 'args' is an array
          switch($k) {
            case 'file':
            case 'line':
            case 'function':
            case 'class':
              $$k = $v1;
              break;
            case 'args':
              foreach($v1 as $v2) {
                //cout("type of v2: " .gettype($v2));
                if(is_object($v2)) {
                  $v2 = get_class($v2);
                } elseif(is_array($v2)) {
                  $v2 = print_r($v2, true);
                }
                $$k .= "\"$v2\", ";
              }
              break;
          }
        }
        $args = rtrim($args, ", "); // $$k was $args so remove the trailing comma.

        // $$k is $file, $line, etc. So we use the referenced values below.

        $traceback .= " file: $file<br> line: $line<br> class: $from<br>\n".
                      "function: $function($args)<br><br>";
      }

      if($traceback) {
        $traceback = "<br>Trace back:<br>\n$traceback";
      }

      $error = <<<EOF
<div style="text-align: center; width: 85%; margin: auto auto; background-color: white; border: 1px solid black; padding: 10px;">
Class: <b>$from</b><br>\n<b>{$e->getMessage()}</b>
in file <b>{$e->getFile()}</b><br> on line {$e->getLine()} $traceback
</div>
EOF;
    }

    // Remove all html tags.

    $err = html_entity_decode(preg_replace("/<.*?>/", '', $error));
    $err = preg_replace("/^\s*$/", '', $err); // remove blank lines

    // Callback to get the user ID if the callback exists

    $userId = '';

    if(function_exists('ErrorGetId')) {
      $userId = "User: " . ErrorGetId();
    }

    if(!$userId) $userId = "agent: ". $_SERVER['HTTP_USER_AGENT'] . "\n";

    // Email error information to webmaster
    // During debug set the Error class's $noEmail to ture

    if(ErrorClass::getNoEmail() !== true) {
      $s = $GLOBALS["_site"];

      $recipients = "{\"address\": {\"email\": \"$s->EMAILADDRESS\",\"header_to\": \"$s->EMAILADDRESS\"}}";
      $contents = preg_replace(["~\"~", "~\\n~"], ['','<br>'], "$err<br>$userId");

      $post =<<<EOF
{"recipients": [
  $recipients
],
  "content": {
    "from": "SqlException@mail.bartonphillips.com",
    "reply_to": "Barton Phillips<barton@bartonphillips.com>",
    "subject": "$from",
    "text": "View This in HTML Mode",
    "html": "$contents"
  }
}
EOF;

      $apikey = file_get_contents("https://bartonphillips.net/sparkpost_api_key.txt"); //("SPARKPOST_API_KEY");

      $options = [
                  CURLOPT_URL=>"https://api.sparkpost.com/api/v1/transmissions", //?num_rcpt_errors",
                  CURLOPT_HEADER=>0,
                  CURLOPT_HTTPHEADER=>[
                                       "Authorization:$apikey",
                                       "Content-Type:application/json"
                                      ],
                  CURLOPT_POST=>true,
                  CURLOPT_RETURNTRANSFER=>true,
                  CURLOPT_POSTFIELDS=>$post
                                     ];
      //error_log("SqlException: options=" . print_r($options, true));

      $ch = curl_init();
      curl_setopt_array($ch, $options);

      $result = curl_exec($ch);
      error_log("dbAbstract.class.php, SqlException: Send To ME (".$s->EMAILADDRESS."). RESULT: $result"); // This should stay!!!
    }

    // Log the raw error info.
    // BLP 2021-03-06 -- New server is in New York
    date_default_timezone_set('America/New_York');

    // This error_log should always stay in!! *****************
    error_log("dbAbstract.class.php: $from\n$err\n$userId");
    // ********************************************************

    if(ErrorClass::getDevelopment() !== true) {
      // Minimal error message
      $error = <<<EOF
<p>The webmaster has been notified of this error and it should be fixed shortly. Please try again in
a couple of hours.</p>

EOF;
      $err = " The webmaster has been notified of this error and it should be fixed shortly." .
      " Please try again in a couple of hours.";
    }

    if(ErrorClass::getNohtml() === true) {
      $error = "$from: $err";
    } else {
      $error = <<<EOF
<div style="text-align: center; background-color: white; border: 1px solid black; width: 85%; margin: auto auto; padding: 10px;">
<h1 style="color: red">$from</h1>
$error
</div>
EOF;
    }

    if(ErrorClass::getNoOutput() !== true) {
      //************************
      // Don't remove this echo
      echo $error; // BLP 2022-01-28 -- on CLI this outputs to the console, on apache it goes to the client screen.
      //***********************
    }
    return;
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

    if(!$this->query("select TABLE_NAME from information_schema.tables where (table_schema = '$this->masterdb') and (table_name = 'myip')")) {
      $this->debug("Database $this->siteName: $this->self: table myip does not exist in the $this->masterdb database: ". __LINE__, true);
    }

    $this->query("select myIp from $this->masterdb.myip");

    while([$ip] = $this->fetchrow('num')) {
      $myIp[] = $ip;
    }
    
    $myIp[] = DO_SERVER; // BLP 2022-04-30 - Add my server.

    //error_log("Database after myIp set, this: " . print_r($this, true));
    //vardump("myIp", $myIp);
    
    return $myIp;
  }
}
