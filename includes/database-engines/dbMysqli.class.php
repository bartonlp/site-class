<?php
/* MAINTAINED and WELL TESTED. This is the default Database and has received extensive testing */
/**
 * Database Class
 *
 * General MySqli Database Class. 
 * @package Database
 * @author Barton Phillips <barton@bartonphillips.com>
 * @version 1.0
 * @link http://www.bartonphillips.com
 * @copyright Copyright (c) 2010, Barton Phillips
 * @license http://opensource.org/licenses/gpl-3.0.html GPL Version 3
 */
// BLP 2023-01-15 - Added '$driver = new mysqli_driver(); $driver->report_mode = MYSQLI_REPORT_OFF;'
// to handal PHP 8.1
// Updated Version to '2.1.0mysqli'

define("MYSQL_CLASS_VERSION", "3.0.0mysqli"); // BLP 2023-03-07 - constructor get $dbinfo object and gets password from file.

/**
 * See http://www.php.net/manual/en/mysqli.overview.php for more information on the Improved API.
 * The mysqli extension allows you to access the functionality provided by MySQL 4.1 and above.
 * More information about the MySQL Database server can be found at http://www.mysql.com/
 * An overview of software available for using MySQL from PHP can be found at Overview
 * Documentation for MySQL can be found at http://dev.mysql.com/doc/.
 * Parts of this documentation included from MySQL manual with permissions of Oracle Corporation.
 */

/**
 * @package Database
 */

class dbMysqli extends dbAbstract {
  protected $db;
  
  /**
   * MySqli Database Link Identifier
   * @var resource $db
   */

  private $result; // for select etc. a result set.
  static public $lastQuery = null; // for debugging
  static public $lastNonSelectResult = null; // for insert, update etc.

  /**
   * Constructor
   * @param object $dbinfo. Has host, user, database and maybe password.
   * as a side effect opens the database, that is connects the database
   */

  public function __construct(object $dbinfo) { 
    // BLP BLP 2022-01-14 -- In almost all cases the Database password is now in
    // /home/barton/database-password on bartonlp.org

    foreach($dbinfo as $k=>$v) {
      $$k = $v;
    }
    
    // BLP 2023-01-15 - START. For PHP 8 and above.
    $driver = new mysqli_driver();
    $driver->report_mode = MYSQLI_REPORT_OFF;
    // BLP 2023-01-15 - END

    $password = $password ?? require("/home/barton/database-password");

    // If we use the 4th param to the constructor we don't need to do a $db->select_db()!
    // public mysqli::__construct(
    //   string $hostname = ini_get("mysqli.default_host"),
    //   string $username = ini_get("mysqli.default_user"),
    //   string $password = ini_get("mysqli.default_pw"),
    //   string $database = "",
    //   int $port = ini_get("mysqli.default_port"),
    //   string $socket = ini_get("mysqli.default_socket")
    // )
    
    $db = new mysqli($host, $user, $password, $database, $port);

    if($db->connect_errno) {
      $this->errno = $db->connect_errno;
      $this->error = $db->connect_error;
      throw new SqlException(__METHOD__ . ": Can't connect to database", $this);
    }

    // BLP 2021-12-31 -- EST/EDT New York
    $db->query("set time_zone='EST5EDT'");
    $this->db = $db;
    $this->db->database = $database;
  }

  public static function getVersion() {
    return MYSQL_CLASS_VERSION;
  }
  
  /**
   * Connects and selects the database
   * @return resource MySQL link identifier
   * On Error outputs message and exits.
   */
  
  private function opendb() {
    // Only do one open

    if($this->db) {
      return $this->db;
    }
    throw new SqlException(__CLASS__ . " " . __LINE__ .": $this->siteName ", $this);
  }

  /**
   * query()
   * Query database table
   * BLP 2016-11-20 -- Query is for a SINGLE query ONLY. Don't do multiple querys!
   *  mysqli has a multi_query() but I have not written a method for it!
   * @param string $query SQL statement.
   * @return: if $result === true returns the number of affected_rows (delete, insert, etc). Else ruturns num_rows.
   * if $result === false calls SqlError() and exits.
   */

  public function query($query) {
    $db = $this->opendb();

    self::$lastQuery = $query; // for debugging

    $result = $db->query($query);

    // If $result is false then exit
    
    if($result === false) {
      //echo "Throw<br>"; // BLP 2023-01-15 - to test PHP 8.1
      throw new SqlException($query, $this);
    }

    // result is a mixed result-set for select etc, true for insert etc.
    
    if($result === true) { // did not return a result object. NOTE can't be false as we covered that above.
      $numrows = $db->affected_rows;
      self::$lastNonSelectResult = $result;
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
    $db = $this->opendb();
    $stm = $db->prepare($query);
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

    return ($returnarray) ? array('rows'=>$rows, 'numrows'=>$numrows) : $rows;
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
      throw new SqlException("dbMysqli.class.php " .__LINE__. "get_class() is not an 'mysqli_result'");
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
    $db = $this->opendb();
    return $db->insert_id;
  }
  
  /**
   * getNumRows()
   */

  public function getNumRows($result=null) {
    if(!$result) $result = $this->result;
    if($result === true) {
      $db = $this->getDb();
      return $db->affected_rows;
    } else {
      return $result->num_rows;
    }
  }
  
  /**
   * Get the Database Resource Link Identifier
   * @return resource link identifier
   */
/*  
  public function getDb():Database {
    return $this->db;
  }
*/

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
    //return ['errno'=>$this->db->errno, 'error'=>$this->db->error];
    return ['errno'=>$this->getDbErrno(), 'error'=>$this->getDbError()];
  }
  
  // real_escape_string
  
  public function escape($string) {
//    if(get_magic_quotes_runtime()) {
//      $string = stripslashes($string);
//    }

    return @$this->db->real_escape_string($string);
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
}
