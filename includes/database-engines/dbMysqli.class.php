<?php
/* MAINTAINED and WELL TESTED. This is the default Database and has received extensive testing */
/**
 * dbMysqli Class
 *
 * Wrapper around MySqli Database Class. 
 * @package dbMysqli
 * @author Barton Phillips <barton@bartonphillips.com>
 * @link http://www.bartonphillips.com
 * @copyright Copyright (c) 2010, Barton Phillips
 * @license http://opensource.org/licenses/gpl-3.0.html GPL Version 3
 */

define("MYSQL_CLASS_VERSION", "5.0.0mysqli"); 

/**
 * See http://www.php.net/manual/en/mysqli.overview.php for more information on the Improved API.
 * The mysqli extension allows you to access the functionality provided by MySQL 4.1 and above.
 * More information about the MySQL Database server can be found at http://www.mysql.com/
 * An overview of software available for using MySQL from PHP can be found at Overview
 * Documentation for MySQL can be found at http://dev.mysql.com/doc/.
 * Parts of this documentation included from MySQL manual with permissions of Oracle Corporation.
 */

/**
 * @package Mysqli Database
 * This is the base class for Database. SiteClass extends Database.
 * This class can also be used standalone. $siteInfo must have a dbinfo with host, user, database and optionally port.
 * The password is optional and if not pressent is picked up form my $HOME.
 */

class dbMysqli extends mysqli {
  private $result; // for select etc. a result set.
  static public $lastQuery = null; // for debugging
  static public $lastNonSelectResult = null; // for insert, update etc.

  /**
   * Constructor
   * @param object $dbinfo. Has host, user, database and maybe password.
   * as a side effect opens the database, that is connects the database
   */

  public function __construct(object $siteInfo) {
    set_exception_handler("dbMysqli::my_exceptionhandler"); // Set up the exception handler

    // BLP 2023-10-02 - ask for sec headers
    
    header("Accept-CH: Sec-Ch-Ua-Platform,Sec-Ch-Ua-Platform-Version,Sec-CH-UA-Full-Version-List,Sec-CH-UA-Arch,Sec-CH-UA-Model"); 

    // Extract the items from dbinfo. This is $host, $user and maybe $password and $port.
    
    extract((array)$siteInfo->dbinfo); // Cast the $dbinfo object into an array
      
    // BLP 2023-01-15 - START. For PHP 8 and above.
    $driver = new mysqli_driver();
    $driver->report_mode = MYSQLI_REPORT_OFF;
    // BLP 2023-01-15 - END

    // $password is almost never present, but it can be under some conditions.
    
    $password = $password ?? require("/home/barton/database-password");

    // If we use the 4th param ($database) to the constructor we don't need to do a $db->select_db()!
    // public mysqli::__construct(
    //   string $hostname = ini_get("mysqli.default_host"),
    //   string $username = ini_get("mysqli.default_user"),
    //   string $password = ini_get("mysqli.default_pw"),
    //   string $database = "",
    //   int $port = ini_get("mysqli.default_port"),
    //   string $socket = ini_get("mysqli.default_socket")
    // )

    parent::__construct($host, $user, $password, $database, $port); // $port is usually null.

    // BLP 2021-12-31 -- EST/EDT New York
    $this->query("set time_zone='EST5EDT'");
    $this->database = $database;
  } // End of constructor.

  /*
   * getVersion.
   * @return the version of the mysql class.
   */
  
  public static function getVersion() {
    return MYSQL_CLASS_VERSION;
  }

  /*
   * getDbErrno
   * @returns $this->db-errno from mysqli.
   */
  
  public function getDbErrno() {
    return $this->errno;
  }

  /*
   * getDbError
   * @returns $this->db->error from mysqli
   */
  
  public function getDbError() {
    return $this->error;
  }
  
  /**
   * sql()
   * Query database table
   * BLP 2016-11-20 -- Query is for a SINGLE query ONLY. Don't do multiple querys!
   * mysqli has a multi_query() but I have not written a method for it!
   * @param string $query SQL statement.
   * @return: if $result === true returns the number of affected_rows (delete, insert, etc). Else ruturns num_rows.
   * if $result === false throws SqlException().
   */

  public function sql($query) {
    self::$lastQuery = $query; // for debugging

    $result = $this->query($query);

    // If $result is false then exit
    
    if($result === false) {
      throw new SqlException($query, $this);
    }

    // result is a mixed result-set for select etc, true for insert etc.
    
    if($result === true) { // did not return a result object. NOTE can't be false as we covered that above.
      $numrows = $this->affected_rows;
      self::$lastNonSelectResult = $result; // for debugging
    } else {
      // NOTE: we don't change result for inserts etc. only for selects etc.
      $this->result = $result;
      $numrows = $result->num_rows;
    }

    return $numrows;
  }
  
  /**
   * prepare()
   * mysqli::prepare()
   * used as follows:
   * 1) $username="bob"; $query = "select one, two from test where name=?";
   * 2) $stm = mysqli::prepare($query);
   * 3) $stm->bind_param("s", $username);
   * 4) $stm->execute();
   * 5) $stm->bind_result($one, $two);
   * 6) $stm->fetch();
   * 7) echo "one=$one, two=$two<br>";
   * BLP 2021-12-11 -- NOTE: we do not have a bind_param(), execute(), bind_result() or fetch() functions in this module.
   * You will have to use the native PHP functions with the returned $stm.
   */
  
  public function prepare($query) {
    $stm = $this->prepare($query);
    return $stm;
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
      throw new SqlException($query, $this);
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
    } elseif(get_class($result) != "mysqli_result") { // BLP 2022-01-17 -- use get_class() not get_debug_type() as it is only PHP8
      throw new SqlException("dbMysqli.class.php " .__LINE__. "get_class() is not an 'mysqli_result'", $this); // BLP 2023-06-24 - add $this
    } 

    if(!$result) {
      throw new SqlException(__METHOD__ . ": result is null", $this);
    }

    switch($type) {
      case "assoc": // associative array
        $row = $result->fetch_assoc();
        break;
      case "num":  // numerical array
        $row = $result->fetch_row();
        break;
      case "obj": // object BLP 2021-12-11 -- added
        $row = $result->fetch_object();
        break;
      case "both":
      default:
        $row = $result->fetch_array();
        break;
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
    return $this->insert_id;
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
  
  public function escape($string) {
    return @$this->real_escape_string($string);
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
        $traceback = "Trace back:<br>\n$traceback";
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
      error_log("dbMysqli.class.php, SqlException: Send To ME (".$s->EMAILADDRESS."). RESULT: $result"); // This should stay!!!
    }

    // Log the raw error info.
    // BLP 2021-03-06 -- New server is in New York
    date_default_timezone_set('America/New_York');

    // This error_log should always stay in!! *****************
    error_log("dbMysqli.class.php: $from\n$err\n$userId");
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
      error_log("dbMysqli.class.php Error: $msg");
      return;
    }
  }
}

