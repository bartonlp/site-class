<?php
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

class dbMysqli extends dbAbstract {
  /**
   * MySqli Database Link Identifier
   * @var resource $db
   */
  
  protected $host, $user, $password, $database;
  private $result; // for select etc. a result set.
  
  static public $lastQuery = null; // for debugging
  static public $lastNonSelectResult = null; // for insert, update etc.
  
  /**
   * Constructor
   * @param string $host host name like "localhost:3306" etc.
   * @param string $user user name for database
   * @param string $password user's password for database
   * @param string $database name of the database
   *
   * as a side effect opens the database, that is connects and selects the database
   */

  public function __construct($host, $user, $password, $database) {
    if(preg_match("/^(.*?):/", $host, $m)) {
      $host = $m[1];
    }
    $this->host = $host;
    $this->user = $user;
    $this->password = $password;
    $this->database = $database;
    $this->opendb();

    // make warning show up as exceptions
    $driver = new mysqli_driver;
    $driver->report_mode = MYSQLI_REPORT_STRICT;
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
    $db = new mysqli($this->host, $this->user, $this->password);
    
    if($db->connect_errno) {
      $this->errno = $db->connect_errno;
      $this->error = $db->connect_error;
      throw new SqlException(__METHOD__ . ": Can't connect to database", $this);
    }
    
    $this->db = $db; // set this right away so if we get an error below $this->db is valid

    if(!@$db->select_db($this->database)) {
      throw new SqlException(__METHOD__ . " Can't select database", $this);
    }
    // BLP 2016-03-16 -- make sure we are la time. 
    $db->query("SET time_zone = 'America/Los_Angeles'");
    
    return $db;
  }

  /**
   * query()
   * Query database table
   * @param string $query SQL statement.
   * @return mixed result-set for select etc, true/false for insert etc.
   * On error calls SqlError() and exits.
   */

  public function query($query) {
    $db = $this->opendb();

    self::$lastQuery = $query; // for debugging
    
    $result = $db->query($query);

    if($result === false) {
      throw(new SqlException($query, $this));
    }
    
    if($result === true) { // did not return a result object 
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
   * @param string|null, if null then $type='both'
   * @param bool|null, if null then false.
   *   if param1, param2=bool then $type='both' and $returnarray=param2
   * @return:
   *   1) if $returnarray is false returns the rows array.
   *   2) if $returnarray is true returns an array('rows'=>$rows, 'numrows'=>$numrows).
   */
  
  public function queryfetch($query, $type=null, $returnarray=null) {
    if(stripos($query, 'select') === false) {
      throw new SqlException($query, $this);
    }

    // queryfetch() can be
    // 1) queryfetch(param1) only 1 param in which case $type is set to
    // 'both'.
    // 2) queryfetch(param1, param2) where param2 is a string like 'assoc', 'num' or
    // 'both'
    // 3) queryfetch(param1, param2) where param2 is a boolian in which case $type is set to
    // 'both' and $returnarray is set to the boolian value of param2.
    // 4) queryfetch(param1, param2, param3) where the param values set the corisponding
    // values.
    
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
    // NOTE: if you have multiple items in an insert the insert_id is for the first one in the
    // group. For example: "insert into test (name) values('one'),('two'),('three')". The id field
    // is auto_increment. insert_id will be 1 if this is done right after the creation of the
    // table. But the last id is really 3. affected_rows is 3 so the last id is:
    // (insert_id + affected_rows -1)

    // $db->info shows:
    // Insert:  Records: 4  Duplicates: 0  Warnings: 0 // insert multipe records one statement
    // Update:  Rows matched: 2  Changed: 2  Warnings: 0 // update id in (100, 101) etc.
    // Insert/upsate: Records: 2 Duplicates: 1 Warnings: 0 // insert 2 records on duplicate key
    // update 1 record. So one straight insert in this case went info id 110, one insert that was a
    // duplicate so we did one (id 100 was already there) update. affected_rows is 3 here.
    // insert_id was 100 -- not sure why, I would have thought 110.
    // If the info says 'Rows matched:' then it is an update.
    // If the info says 'Records:' without any 'Duplicates:' then it is a insert/update
    // It also looks like $db->info is only filled in if $db->affected_rows is greater than one!
    
    // If the 'insert ignore ...' did in fact NOT do an insert then insert_id is zero and there
    // were no affected_rows so we need to test for that and return zero not -1.
    
    if($db->insert_id === 0) return 0;
    
    return ($db->insert_id + $db->affected_rows) -1;
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
  
  public function getDb() {
    return $this->db;
  }

  public function getResult() {
    return $this->result;
  }

  public function getErrorInfo() {
    $error = $this->db->error;
    $errno = $this->db->errno;
    $err = array('errno'=>$errno, 'error'=>$error);
    return $err;
  }
  
  // real_escape_string
  
  public function escape($string) {
    if(get_magic_quotes_runtime()) {
      $string = stripslashes($string);
    }
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
