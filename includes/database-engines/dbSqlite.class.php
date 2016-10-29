<?php
/**
 * dbSqlite Class
 *
 * General MySqli Database Class. 
 * @package Database
 * @author Barton Phillips <barton@bartonphillips.com>
 * @version 1.0
 * @link http://www.bartonphillips.com
 * @copyright Copyright (c) 2010, Barton Phillips
 * @license http://opensource.org/licenses/gpl-3.0.html GPL Version 3
 */

/**
 * See http://www.php.net/manual/en/mysqli.overview.php for more information on the Improved API.
 * The mysqli extension allows you to access the functionality provided by MySQL 4.1 and above.
 * More information about the MySQL Database server can be found at » http://www.mysql.com/
 * An overview of software available for using MySQL from PHP can be found at Overview
 * Documentation for MySQL can be found at » http://dev.mysql.com/doc/.
 * Parts of this documentation included from MySQL manual with permissions of Oracle Corporation.
 */

/**
 * @package Database
 */

class dbSqlite extends dbAbstract {
  /**
   * dbSqlite Database Link Identifier
   * @var resource $db
   */
  
  public $db = 0;

  protected $host, $user, $password, $database;
  private $result;
  
  /**
   * Constructor
   * @param string $filename .
   * @param string $user user name for database
   * @param string $password user's password for database
   * @param string $database name of the database
   *
   * as a side effect opens the database, that is connects and selects the database
   */

  public function __construct($filename, $user, $password, $database) {
    $this->host = $filename;
    $this->user = $user;
    $this->password = $password;
    $this->database = $database;
    $this->opendb();
  }
  
  /**
   * Connects and selects the database
   * @return resource MySQL link identifier
   * On Error outputs message and exits.
   */
  
  protected function opendb() {
    // Only do one open
    if($this->db) {
      return $this->db;
    }
    //$db = new SQLiteDatabase($this->database);
    $db = new SQlite3($this->database);
    //$db = @sqlite_open($this->database);

    if($db === false) {
      throw new SqlException(__METHOD__ . ": Can't connect to database: {$db->loastError}", $this);
    }
    
    $this->db = $db; // set this right away so if we get an error below $this->db is valid

    return $db;
  }

  /**
   * getResult()
   */

  public function getResult() {
    return $this->result;
  }
  
  /**
   * query()
   * Query database table
   * @param string $query SQL statement.
   * @param bool retarray default false. If true then returns an array with result, num_rows
   * @return mixed result-set for select etc, true/false for insert etc.
   * On error calls SqlError() and exits.
   */

  public function query($query) {
    $db = $this->opendb();

    if(preg_match("/^select/i", $query)) {
      $result = @$db->query($query);
      if($result === false) {
        throw new SqlException($query, $this);
      }
      $this->result = $result;
    } else {
      $result = @$db->exec($query);
      if($result === false) {
        throw new SqlException($query, $this);
      }
    }
    return;
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
   */
  
  public function prepare($query) {
    $db = $this->opendb();
    $stm = $db->prepare($query);
    return $stm;
  }

  /**
   * queryfetch()
   * Dose a query and then fetches the associated rows
   * Does a fetch_assoc() and places each row array into an array.
   * @param string, the query
   * @return array, the rows
   */
  
  public function queryfetch($query, $returnarray=false) {
    $this->query($query);
    
    if(!$result) {
      throw new SqlException($query, $this);
    }

    while($row = $result->fetchArray(SQLITE3_ASSOC)) {
      $rows[] = $row;
    }
    $numrows = count($rows);
    
    return ($returnarray) ? array($rows, $numrows, result=>$rows, numrows=>$numrows) : $rows;
  }

  /**
   * fetchrow()
   * @param resource identifier returned from query.
   * @param string, type of fetch: assoc==associative array, num==numerical array, or both
   * @return array, either assoc or numeric, or both
   * NOTE: if $result is a string then it is the type and we use $this->result for result.
   */
  
  public function fetchrow($result=null, $type="both") {
    if(is_string($result)) {
      $type = $result;
      $result = $this->result;
    } elseif(!$result) {
      $result = $this->result;
    }

    if($result === false) {
      throw new SqlException(__METHOD__ . ": result is null", $this);
    }
    
    switch($type) {
      case "assoc": // associative array
        $row = $result->fetchArray(SQLITE3_ASSOC);
        break;
      case "num":  // numerical array
        $row = $result->fetchArray(SQLITE3_NUM);
        break;
      case "both":
      default:
        $row = $result->fetchArray(SQLITE3_BOTH);
        break;
    }
    return $row;
  }
  
  /**
   * getLastInsertId()
   *
   */

  public function getLastInsertId() {
    $db = $this->opendb();
    return $db->lastInsertRowid();
  }
  
  /**
   * getNumRows()
   */

  public function getNumRows($result=null) {
    if(!$result) $result = $this->result;
    return $result->numRows();
  }
  
  /**
   * Get the Database Resource Link Identifier
   * @return resource link identifier
   */
  
  public function getDb() {
    return $this->db;
  }

  public function getErrorInfo() {
    $db = $this->opendb();

    $errno = $db->lastErrorCode();
    $error = $db->lastErrorMsg();

    $err = array('errno'=>$errno, 'error'=>$error);
    return $err;
  }
  
  // real_escape_string
  
  public function escape($string) {
    $db = $this->opendb();

    if(get_magic_quotes_runtime()) {
      $string = stripslashes($string);
    }
    return @sqlite_escape_string($string);
  }

  //
  
  public function escapeDeep($value) {
    $db = $this->opendb();

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
    return "Database mysqli Class";
  }
}

/**
 * Helper Functions
 * These my well be defined by a chile class.
 */

/**
 * stripSlashesDeep
 * recursively do stripslahes() on an array or string.
 * Only define if not already defined.
 * @param array|string $value either a string or an array of strings/arrays ...
 * @return original $value stripped clean of slashes.
 */

if(!function_exists('stripSlashesDeep')) {
  function stripSlashesDeep($value) {
    $value = is_array($value) ? array_map('stripSlashesDeep', $value) : stripslashes($value); 
    return $value;
  }
}

// Change < and > into "&lt;" and "&gt;" entities

if(!function_exists('escapeltgt')) {
  function escapeltgt($value) {
    $value = preg_replace(array("/</", "/>/"), array("&lt;", "&gt;"), $value);  
    return $value;
  }
}

// vardump makes value readable

if(!function_exists('vardump')) {
  function vardump($value, $msg=null) {
    if($msg) $msg = "<b>$msg</b>\n";
    echo "<pre>$msg" . (escapeltgt(print_r($value, true))) . "</pre>\n\n";
  }
}
