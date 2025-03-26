<?php
/* MAINTAINED and WELL TESTED. This is the default Database and has received extensive testing */
/**
 * dbPdo Class
 *
 * Wrapper around PDO Database Class. 
 * @package dbPdo
 * @author Barton Phillips <barton@bartonphillips.com>
 * @link http://www.bartonphillips.com
 * @copyright Copyright (c) 2010, Barton Phillips
 * @license http://opensource.org/licenses/gpl-3.0.html GPL Version 3
 */
// BLP 2024-04-20 - set mysql timezone!

use SendGrid\Mail\Mail; // Use SendGrid for email

define("PDO_CLASS_VERSION", "1.0.16pdo"); // BLP 2025-03-24 - Change foundBotAs to botAs

require_once(__DIR__ . "/../defines.php"); // This has the constants for TRACKER, BOTS, BOTS2, and BEACON

/**
 * @package PDO Database
 * This is the base class for Database. SiteClass extends Database.
 * This class can also be used standalone. $siteInfo must have a dbinfo with host, user, database and optionally port.
 * The password is optional and if not pressent is picked up form my $HOME.
 */

define("DEBUG_CONSTRUCTOR", true); // To disable change to false.

class dbPdo extends PDO {
  private $result; // for select etc. a result set.
  static public $lastQuery = null; // for debugging
  static public $lastNonSelectResult = null; // for insert, update etc.

  /**
   * Constructor
   * @param object $siteInfo. Has the mysitemap.json info
   * as a side effect opens the database, that is connects the database
   */

  public function __construct(object $s) {
    set_exception_handler("dbPdo::my_exceptionhandler"); // Set up the exception handler

    date_default_timezone_set('America/New_York');

    header("Accept-CH: Sec-Ch-Ua-Platform,Sec-Ch-Ua-Platform-Version,Sec-CH-UA-Full-Version-List,Sec-CH-UA-Arch,Sec-CH-UA-Model"); 

    $s->ip = $s->ip ?? $_SERVER['REMOTE_ADDR'];

    $s->agent = $s->agent ?? $_SERVER['HTTP_USER_AGENT'];
    $s->agent = preg_replace("~'~", "", $s->agent); // BLP 2024-10-29 - remove appostrophies.

    $s->self = $s->self ?? htmlentities($_SERVER['PHP_SELF']);
    $s->requestUri = $s->requestUri ?? $_SERVER['REQUEST_URI'];
    
    foreach($s as $k=>$v) {
      $this->$k = $v;
    }

    // Extract the items from dbinfo. This is $host, $user and maybe $password and $port.

    extract((array)$s->dbinfo); // Cast the $dbinfo object into an array
      
    // $password is almost never present, but it can be under some conditions.
    
    $password = $password ?? require("/home/barton/database-password");

    if($engine == "sqlite") {
      parent::__construct("$engine:$database");
    } else {
      parent::__construct("$engine:dbname=$database; host=$host; user=$user; password=$password");
    }
    $this->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $this->sql("set time_zone='America/New_York'"); 
    
    $this->database = $database;
    
    if(DEBUG_CONSTRUCTOR) {
      if($this->ip != MY_IP)
        error_log("dbPdo constructor: ip=$this->ip, site=$this->siteName, page=$this->self, line=". __LINE__);
    }
  } // End of constructor.

  /*
   * getVersion.
   * @return the version of the pdo class.
   */
  
  public static function getVersion() {
    return PDO_CLASS_VERSION;
  }

  /*
   * getDbErrno
   * @returns $this->db-errno from PDO.
   */
  
  public function getDbErrno() {
    return $this->errno;
  }

  /*
   * getDbError
   * @returns $this->db->error from PDO
   */
  
  public function getDbError() {
    return $this->error;
  }

    /**
   * isMyIp($ip):bool
   * Given an IP address check if this is me.
   */

  public function isMyIp(string $ip):bool {
    if($this->isMeFalse === true) return false;
    return (in_array($ip, $this->myIp));
  }
  
  /**
   * isMe()
   * Check if this access is from ME
   * @return true if $this->ip == $this->myIp else false!
   */

  public function isMe():bool {
    return $this->isMyIp($this->ip);
  }

  /*
   * isBot(string $agent):bool
   * Determines if an agent is a bot or not.
   * @return bool
   * Side effects: (for tracker() in Database)
   *  it sets $this->trackerBotInfo
   *  it sets $this->isBot
   *  it sets $this->botAs
   * These side effects are used by checkIfBot():void see below.
   */
  
  public function isBot(?string $agent):bool {
    $this->isBot = false;
    $this->botAs = BOTAS_ZERO; // BOTAS_ZERO is null. This will be the return if nothing was found.
    $this->trackerBotInfo = null; // Set to null at start.
    
    // BLP 2025-01-12 - Make sure it is not ME!

    if($this->isMe()) return false;
    
    if(!empty($agent)) {
      if(($x = preg_match("~@|bot|spider|scan|HeadlessChrome|python|java|wget|nutch|perl|libwww|lwp-trivial|curl|PHP/|urllib|".
                          "crawler|GT::WWW|Snoopy|MFC_Tear_Sample|HTTP::Lite|PHPCrawl|URI::Fetch|Zend_Http_Client|".
                          "http client|PECL::HTTP|Go-|python~i", $agent)) === 1) { // 1 means a match
        $this->isBot = true;
        $this->botAs = BOTAS_MATCH;
      } elseif($x === false) { // false is error
        // This is an unexplained ERROR
        throw new PdoException(__CLASS__ . " " . __LINE__ . ": preg_match() returned false", -300);
      }

      if(($x = preg_match("~\+?https?://~", $agent)) === 1) {
        $this->isBot = true;
        $this->botAs = (empty($this->botAs)) ? BOTAS_GOODBOT : ("$this->botAs," . BOTAS_GOODBOT);
      } elseif($x === false) {
        throw new PdoException(__CLASS__ . " " . __LINE__ . ": preg_match() for +https? false", -301);
      }
    } else {
      $this->botAs = BOTAS_NOAGENT;
      $this->isBot = true;
    }

    // BLP 2025-03-08 - reworked. Use bots not bots2. Combine the bots2 and bots logic. Remove
    // switch() and use if($robots & ...).

    // Look at all of the bots records for this ip.
    
    if($this->sql("select robots from $this->masterdb.bots where ip='$this->ip'")) { // Found some.
      // Get each record

      while([$robots] = $this->fetchrow('num')) {
        $type = null;

        // $robots is an int with the BOTS_... ored in. It must be taken apart with 'ands'.

        if($robots & BOTS_ROBOTS) {
          $type |= TRACKER_ROBOTS;

          // Check it the $tmp string already contains BOTS_ROBOTS if so add nothing. Else add
          // BOTAS_ROBOT and a trailing comma.
          
          $this->botAs .= str_contains($this->botAs, BOTAS_ROBOT) ? null : "," . BOTAS_ROBOT;
        }
        if($robots & BOTS_SITEMAP) {
          $type |= TRACKER_SITEMAP;
          $this->botAs .= str_contains($this->botAs, BOTAS_SITEMAP) ? null : "," . BOTAS_SITEMAP;
        }
        if($robots & BOTS_SITECLASS) {
          $type |= TRACKER_BOT;
          $this->botAs .= str_contains($this->botAs, BOTAS_SITECLASS) ? null : "," . BOTAS_SITECLASS;
        }
        if($robots & BOTS_CRON_ZERO) {
          $this->botAs .= str_contains($this->botAs, BOTAS_ZBOT) ? null : "," . BOTAS_ZBOT; // BLP 2023-11-04 - found 0x100 in bots so this is a zero (0x100) from bots ttable: 'zbot'
        }

        $this->trackerBotInfo |= $type; // used by tracker() in Database.

        // BLP 2025-03-09 - BotAs also used in tracker in Database.
        
        $this->isBot = true;
        
        // Now $this->trackerBotInfo and $this->botAs are set.
      }
    }

    // isBot may be true because 1) BOTAS_MATCH, 2) BOTAS_NOAGENT 3) BOTAS_GOODBOT or 4) found in bots
    // table. If the agent mastched the list in the preg_match at the top, then BOTAS_MATCH.
    // If the agent had an http address for information then BOTAS_GOODBOT is set.
    // If there was 'no agent' then BOTAS_NOAGENT is set.
    // If any of the above happened then isBot is true.
    // If we did not find anything then BATAS_ZERO was set at the start of isBot so
    // $this->botAs == BOTAS_ZERO (null)

    return $this->isBot;
  }

  /**
   * sql()
   * Query database table
   * BLP 2016-11-20 -- Query is for a SINGLE query ONLY. Don't do multiple querys!
   * @param string $query SQL statement.
   * @return: if $result === true returns the number of affected_rows (delete, insert, etc). Else ruturns num_rows.
   * if $result === false throws Exception().
   */

  public function sql($query) {
    self::$lastQuery = $query; // for debugging

    $m = null;
    preg_match("~^(\w+).*$~", $query, $m);
    $m = $m[1];

    //echo "m=$m<br>";

    if($m == 'insert' || $m == 'delete' || $m == 'update') {
      try {
        $numrows = $this->exec($query);
      } catch (Exception $e) {
        throw $e;
      }
    } else { // could be select, create, etc.
      try {
        $result = $this->query($query);
      } catch(Exception $e) {
        if(str_contains($query, "by+lasttime")) {
          error_log("dbPdo.class.php, by+lasttime: ip=$this->ip, site=$this->siteName, page=$this->self, agent=$this->agent");
          return;
        }
        throw $e;
      }

      $this->result = $result;

      if($this->dbinfo->engine == 'mysql') {
        $numrows = $result->rowCount();
      } elseif($m == 'select') {
        $last = self::$lastQuery;
        $last = preg_replace("~^(select) .*?(from .*)$~", "$1 count(*) $2", $last);
        $stm = $this->query($last);
        $numrows = $stm->fetchColumn();
        //echo "numrows=$numrows<br>";
      } else $numrows = 0;
    }
    return $numrows;
  }
  
  /**
   * sqlPrepare()
   * This method works with fully formed queries! That is no :name or =? that need to be prepared!
   * It also seems to work with =|<|>|<=|>=|!=|like. It also seems to work with 'between'.
   * It only worked with ':named' parameters but not with '?' parameters.
   * This method can be used with $this->fetchrow(..).
   *
   * If you have a complicated queries or '?' parameters use the RAW PDO prepare(), bind_params(), execute(), bind_result() and fetch().
   * Used as follows with bound params:
   * 1) $username="bob"; $query = "select one, two from test where name=?";
   * 2) $stm = PDO::prepare($query);
   * 3) $stm->bind_param("s", $username);
   * 4) $stm->execute();
   * 5) $stm->bind_result($one, $two);
   * 6) $stm->fetch();
   * 7) echo "one=$one, two=$two<br>";
   * NOTE: we do not have a bind_param(), execute(), bind_result() or fetch() functions in this module.
   * You will have to use the native PHP functions with the returned $stm.
   * NOTE: As of PHP 8 PDO uses exception as the default: PDO::ERRMODE_EXCEPTION.
   */
  
  public function sqlPrepare(string $query, ?array $values=null) {
    self::$lastQuery = $query; // for debugging
    
    try {
      $stm = $this->prepare($query);
    } catch(Exception $e) {
      throw $e;
    }

    if($values !== null) {
      if(preg_match_all("~between\s*?(:.+?)\s*?\S+?\s*?(:.+?)(?:\s+|$)~", $query, $mm) === false) {
        echo "Error1: " . preg_last_error_msg() . "<br>";
        return false;
      }

      // remove the first element of the array
      
      array_shift($mm);
      
      $mm = array_merge(...$mm); // This fattens the array one layer.
     
      if(preg_match_all("~(?:=|<|>|<=|>=|!=|like).*?(:.+?)(?: |$)~", $query, $m) === false) {
        echo "Error2 " . preg_last_error_msg() . "<br>";
        return false;
      }
      $m = $m[1];

      $m = array_merge($m, $mm); // both arrays should look the same so merge them.

      // Check if we have the same number of named params as values.
      
      if(count($m) != count($values)) {
        echo "Error3 Count error<br>";
        return false;
      }
      for($i=0; $i<count($values); ++$i) {
        $params[$m[$i]] = $values[$i];
      }
    }
    
    $stm->execute($params);

    $this->result = $stm;

    if($this->dbinfo->engine == 'mysql') {
      $numrows = $stm->rowCount();
    } elseif($m == 'select') {
      $last = self::$lastQuery;
      $last = preg_replace("~^(select) .*?(from .*)$~", "$1 count(*) $2", $last);
      $stm = $this->query($last);
      $numrows = $stm->fetchColumn();
    } else {
      $numrows = 0;
    }

    return $numrows;
  }

  /**
   * queryfetch()
   * Dose a query and then fetches the associated rows
   * @param string, the query
   * @param string|null, $type can be 'num', 'assoc', 'obj' or 'both'. If null then $type='both'
   * @param bool|null, if null then false.
   *   if param1, param2=bool then $type='both' and $returnarray=param2
   * @return:
   *   1) if $returnarray is false returns the rows array.
   *   2) if $returnarray is true returns an array('rows'=>$rows, 'numrows'=>$numrows).
   * NOTE the $query must be a 'select' that returns a result set. It can't be 'insert', 'delete', etc.
   */
  
  public function queryfetch($query, $type=null, $returnarray=null) {
    if(stripos($query, 'select') === false) { // Can't be anything but 'select'
      throw new Exception($query, $this);
    }

    // queryfetch() can be
    // 1) queryfetch(param1) only 1 param in which case $type is set to
    // 'both'.
    // 2) queryfetch(param1, param2) where param2 is a string like 'assoc', 'num', 'obj' or 'both'
    // 3) queryfetch(param1, param2) where param2 is a boolian in which case $type is set to
    // 'both' and $returnarray is set to the boolian value of param2.
    // 4) queryfetch(param1, param2, param3) where the param values set the corisponding values.

    if(is_null($type)) {
      $type = 'both';
    } elseif(is_bool($type) && is_null($returnarray)) {
      $returnarray = $type;
      $type = 'both';
    }  
    
    $numrows = $this->query($query);

    while($row = $this->fetchrow($type)) {
      $rows[] = $row;
    }

    return ($returnarray) ? ['rows'=>$rows, 'numrows'=>$numrows] : $rows;
  }

  /**
   * fetchrow()
   * @param resource identifier returned from query.
   * @param string, type of fetch: assoc==associative array, num==numerical array, obj==object, or both (for num and assoc).
   * @return array, either assoc or numeric, or both
   * NOTE: if $result is a string then $result is the $type and we use $this->result for result.
   */
  
  public function fetchrow($result=null, $type="both") {
    if(is_string($result)) { // a string like num, assoc, obj or both
      $type = $result;
      $result = $this->result;
    }
    
    if(!$result) {
      throw new Exception(__METHOD__ . ": result is null");
    }

    try {
      switch($type) {
        case "assoc": // associative array
          $row = $result->fetch(PDO::FETCH_ASSOC);
          break;
        case "num":  // numerical array
          $row = $result->fetch(PDO::FETCH_NUM);
          break;
        case "obj": // object BLP 2021-12-11 -- added
          $row = $result->fetch(PDO::FETCH_OBJ);
          break;
        case "both":
        default:
          $row = $result->fetch(PDO::FETCH_BOTH); // This is the default
          break;
      }
    } catch(Exception $e) {
      throw $e;
    }
    return $row;
  }
  
  /**
   * getLastInsertId()
   * See the comments below. The bottom line is we should NEVER do multiple inserts
   * with a single insert command! You just can't tell what the insert id is. If we need to do
   * and 'insert ... on duplicate key' we better not need the insert id. If we do we should do
   * an insert in a try block and an update in a catch. That way if the insert succeeds we can
   * do the getLastInsertId() after the insert. If the insert fails for a duplicate key we do the
   * update in the catch. And if we need the id we can do a select to get it (somehow).
   * Note if the insert fails because we did a 'insert ignore ...' then last_id is zero and we return
   * zero.
   * @return the last insert id if this is done in the right order! Otherwise who knows.
   */

  public function getLastInsertId() {
    return $this->lastInsertId();
  }
  
  /**
   * getNumRows()
   */

  public function getNumRows($result=null) {
    if(!$result) $result = $this->result;
    if($result === true) {
      return $this->affected_rows;
    } else {
      return $result->num_rows;
    }
  }

  /**
   * getResult()
   * This is the result of the most current query. This can be passed to
   * fetchrow() as the first parameter.
   */
  
  public function getResult() {
    return $this->result;
  }

  /**
   * getErrorInfo()
   * get the error info from the most recent query
   */
  
  public function getErrorInfo() {
    return ['errno'=>$this->getDbErrno(), 'error'=>$this->getDbError()];
  }
  
  // real_escape_string
  // BLP 2024-01-24 - Just escape '.
  
  public function escape($string) {
    return str_replace("'", "\\'", $string);
  }

  public function escapeDeep($value) {
    if(is_array($value)) {
      foreach($value as $k=>$v) {
        $val[$k] = $this->escapeDeep($v);
      }
      return $val;
    } else {
      return $this->escape($value);
    }
  }

  public function __toString() {
    return __CLASS__;
  }

  /*
   * my_exceptionhandler
   * Must be a static
   */

  public static function my_exceptionhandler($e) {
    $from =  get_class($e);

    $error = $e; // get the full error message

    // Remove all html tags.

    $err = html_entity_decode(preg_replace("/<.*?>/", '', $error));
    $err = preg_replace("/^\s*$/", '', $err); // remove blank lines

    // BLP 2024-09-02 - Get dbPdo::$lastQuery

    $last = dbPdo::$lastQuery;
    
    // Callback to get the user ID if the callback exists

    $userId = '';

    if(function_exists('ErrorGetId')) {
      $userId = "User: " . ErrorGetId();
    }

    if(!$userId) $userId = "agent: $this->agent\n";

    /* BLP 2024-07-01 - NEW VERSION using sendgrid */

    if(ErrorClass::getNoEmail() !== true) {
      $s = $GLOBALS["_site"];

      $email = new Mail();

      $email->setFrom("ErrorMessage@bartonphillips.com");
      $email->setSubject($from);
      $email->addTo($s->EMAILADDRESS);

      $contents = preg_replace(["~\"~", "~\\n~"], ['','<br>'], "$err<br>lastQuery: $last<br>$userId");

      $email->addContent("text/plain", $contents); // BLP 2025-02-19 - 

      $email->addContent("text/html", $contents);

      $apiKey = require "/var/www/PASSWORDS/sendgrid-api-key";
      $sendgrid = new \SendGrid($apiKey);

      $response = $sendgrid->send($email);

      // BLP 2024-12-17 - add $resp and use it below. I had in error_log $response->statusCode()
      // instead of response and that caused an error.
      
      if(($resp = $response->statusCode()) > 299) {
        error_log("dbPod.class.php: sendgrid error, $resp, response header: " . print_r($response->headers()));
      }
    }

    /* BLP 2024-07-01 - END NEW VERSION */
    
    // Log the raw error info.
    // This error_log should always stay in!! *****************
    error_log("dbPdo.class.php: $from\n$err\nlastQuery: $last\n$userId");
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

    if(ErrorClass::getNoHtml() === true) {
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
   * debug
   * Displays $msg
   * if $exit is true throw an exception.
   * else error_log and return.
   */
  
  public function debug($msg, $exit=null) {
    if($exit === true) {
      throw new Exception($msg);
    } else {
      error_log("dbPdo.class.php: Error=$msg");
      return;
    }
  }
}

