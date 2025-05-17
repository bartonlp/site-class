<?php
/* MAINTAINED and WELL TESTED. This is the default Database and has received extensive testing */
// BLP 2024-04-20 - set mysql timezone!
// BLP 2025-04-20 - at some point I may add more type hints to this file.

namespace bartonlp\SiteClass;
use bartonlp\SiteClass\UserAgentTools;
use bartonlp\SiteClass\WarningToExceptionHandler;
use \PDO;
use \PDOStatement;

define("PDO_CLASS_VERSION", "1.2.6pdo"); // BLP 2025-04-24 - new sql() method from ChatGpt
                                         // BLP 2025-04-19 - add the trait.
                                         // BLP 2025-04-18 - add create() to allow me to use updateBot3() function when I do new dbPdo(...).
                                         // BLP 2025-04-13 - new version of $robotMap. Added SAPI in constructor
                                         // BLP 2025-05-09 - removed sqlPrepare.

require_once(__DIR__ . "/../defines.php"); // This has the constants for TRACKER, BOTS, BOTS2, and BEACON

/**
 * dbPdo. Bottom of the SiteClass framework
 *
 * Wrapper around PHP standard PDO Database Class.
 * Class hierarchy:
 *   SiteClass extend Database,
 *   Database extends dbPdo,
 *   dbPdo extends PDO from PHP 
 * This class can also be used standalone. 
 *
 * @package SiteClass
 * @author Barton Phillips <barton@bartonphillips.com>
 * @link http://www.bartonphillips.com
 * @copyright Copyright (c) 2025, Barton Phillips
 * @license MIT
 * @see https://github.com/bartonlp/site-class My GitHub repository
 */
class dbPdo extends PDO {
  /**
   * The PDOstatement from the last sql query
   *
   * @var PDOStatement $result
   */
  private PDOStatement $result; // for select etc. a result set.

  /**
   * The last query that was executed
   *
   * @var string $lastQuery
   */
  static public ?string $lastQuery = null; // for debugging

  private bool $Debug = false; // for debugging only
  
  use UserAgentTools; // BLP 2025-04-19 - This is a trait for isMe(), isMyIp(), isBot(), setSiteCookie() and getIp().
                      // Putting it here means these are available to the entire hierarchy.
  use WarningToExceptionHandler; // BLP 2025-04-25 - New trait to fix E_WARNING to Exceptions.
  
  /**
   * Constructor
   *
   * If the dbPdo class is to be run standalone object $s in the constructor
   * must have a dbinfo with host, user, database and optionally port.
   * The password is optional and if not pressent is picked up form my $HOME.
   * As a side effect opens the database, either the sqlite3 or MySql database via PDO
   *
   * @param object $s Has the mysitemap.json info
   * @see https://bartonlp.org/docs/mysitemap.json
   */
  public function __construct(object $s) {
    date_default_timezone_set('America/New_York');

    header("Accept-CH: Sec-Ch-Ua-Platform,Sec-Ch-Ua-Platform-Version,Sec-CH-UA-Full-Version-List,Sec-CH-UA-Arch,Sec-CH-UA-Model"); 

    $mapWarnToException = [
                           "preg_match",
                           "preg_replace",
                           "preg_match_all",
                           "preg_split",
                          ];
                              
    $this->registerWarningHandlers($mapWarnToException);
    
    // BLP 2025-04-13 - CLI has no REMOTE_ADDR or HTTP_USER_AGENT or REQUEST_URI

    if(PHP_SAPI === 'cli') {
      $s->ip = MY_IP;
      $s->agent = "CLI_NO_AGENT";
      $s->requestUri = "CLI_NO_REQUEST_URI";
    } else {
      // Web Based
      
      $s->ip = $s->ip ?? $_SERVER['REMOTE_ADDR'];
      $s->agent = $s->agent ?? $_SERVER['HTTP_USER_AGENT'];
      $s->agent = preg_replace("~'~", "", $s->agent); // BLP 2024-10-29 - remove appostrophies.
      $s->requestUri = $s->requestUri ?? $_SERVER['REQUEST_URI'];
    }
    
    $s->self = $s->self ?? htmlentities($_SERVER['PHP_SELF']);

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

    if($this->Debug === true && $this->ip != MY_IP)
      error_log("dbPdo constructor: ip=$this->ip, site=$this->siteName, page=$this->self, line=". __LINE__);
  } // End of constructor.

  /**
   * Create a class from the caller
   *
   * This is a STATIC function.
   * It can be used like this: $db = Database::create($s)
   * where $s is the $this or the $S or $_site.
   *
   * @param object $s like with the constructor.
   * @return static A new static class
   */
  public static function create(object $s): static {
    return new static($s);
  }
    
  /*
   * Get the version of the dbPdo class
   *
   * @return the version of the pdo class.
   */
  
  public static function getVersion(): string {
    return PDO_CLASS_VERSION;
  }

  /*
   * Get the last database error
   *
   * @returns $this->db-errno from PDO.
   */
  
  public function getDbErrno(): int {
    return $this->errno;
  }

  /*
   * Get the last database error message
   *
   * @returns $this->db->error from PDO
   */
  
  public function getDbError(): string {
    return $this->error;
  }

  /**
   * Execute a Sql statment
   *
   * Execute a single SQL statement with optional prepared parameters.
   * Supports SELECT, INSERT, UPDATE, DELETE (DML), as well as CREATE, DROP, ALTER, TRUNCATE (DDL),
   * and GRANT, REVOKE, SET, USE (DCL). Automatically uses prepare/execute if parameters are supplied.
   *
   * @param string $query  The SQL statement to execute.
   * @param array  $params Optional array of values to bind to the statement (for prepared execution).
   * @return int|bool   - Row count for INSERT, UPDATE, DELETE
   *                    - True for successful DDL/DCL statements
   *                    - Row count for SELECT/SHOW/EXPLAIN (and sets $this->result)
   *                    - 0 for suppressed tracking query failures
   *                    - false only if something unusual occurs
   * @throws Exception  - On SQL preparation or execution failure.
   * @site-effect       - For SELECT/SHOW/EXPLAIN sets $this->result.
   */
  public function sql($query, array $params = []): PDOStatement|int|bool {
    self::$lastQuery = $query;

    // Multi line statements are okay.
    // Extract the command type (first word in SQL)
    
    $firstWord = strtolower(strtok(ltrim($query), " \t\n\r")); // Gets the first word
    
    // Classify SQL command types. The comments are just for documentation.
    // DML Data Manipulation Language: $dml = ['select', 'insert', 'update', 'delete']; 
    // DDL Data Definition Language: $ddl = ['create', 'drop', 'alter', 'truncate'];
    // DCL Data Control Language: $dcl = ['grant', 'revoke', 'set', 'use'];
    
    // Prepared execution path

    try {
      // This array is basically $dml, $ddl and $dcl minus 'select'.
      
      if(in_array($firstWord, ['insert', 'update', 'delete', 'create', 'drop', 'alter',
                               'truncate', 'set', 'grant', 'revoke', 'use'])) {
        if($params) {
          $stmt = $this->prepare($query);
          $stmt->execute($params);
          return in_array($firstWord, ['insert', 'update', 'delete']) ? $stmt->rowCount() : true;
        } else {
          $rows = $this->exec($query);
          return in_array($firstWord, ['insert', 'update', 'delete']) ? $rows : true;
        }
      } else {
        // SELECT, SHOW, DESCRIBE, EXPLAIN, etc. These all return a $result.

        if($params) {
          $stmt = $this->prepare($query);
          $stmt->execute($params);
          $this->result = $stmt;
        } else {
          $this->result = $this->query($query);
        }

        $result = $this->result;

        // Row counting logic
        
        if($this->dbinfo->engine == 'mysql') {
          $numrows = $result->rowCount();
        } elseif($firstWord == 'select') {
          // If engine is not 'mysql' then it is 'sqlite' as those are the only two databases I
          // support. So use count(*) to get the number of lines.
          
          $last = self::$lastQuery; // Get the query back

          // No remove 'select' the 'from ...' and create 'select count(*) from ...
          
          $last = preg_replace("~^(select) .*?(from .*)$~i", "$1 count(*) $2", $last);
          $stm = $this->query($last);
          $numrows = $stm->fetchColumn();
        } else {
          $numrows = 0;
        }

        return $numrows;
      }
    } catch (\Exception $e) {
      // Optional targeted debug logic
      if(str_contains($query, "by+lasttime")) {
        error_log("dbPdo, by+lasttime: ip=$this->ip, site=$this->siteName, page=$this->self, agent=$this->agent, line=". __LINE__);
        return true; // Did not process this query!
      }
      throw $e;
    }
  }

  /**
   * Does a sql query and returns all rows
   *
   * Dose a query and then fetches all the rows
   * NOTE the $query must be a 'select' that returns a result set. It can't be 'insert', 'delete', etc.
   *
   * @param string $query
   * @param string|null Can be 'num', 'assoc', 'obj' or 'both'. If null then $type='both'
   * @param bool|null $returnarray
   *   If param 2 type is null, then $type='both' and $returnarray=param 2.
   * @return array
   *   1) if $returnarray is false returns the rows array.
   *   2) if $returnarray is true returns an array('rows'=>$rows, 'numrows'=>$numrows).
   */
  public function queryfetch($query, $type=null, $returnarray=null) {
    if(stripos($query, 'select') === false) { // Can't be anything but 'select'
      throw new \Exception(__CLASS__ . " ". __LINE__ .": Query must be a select: $query");
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
   * Fetch one row
   *
   * NOTE: if $result is a string then $result is the $type and we use $this->result for result.
   *
   * @param PDOStatement|string|null $result
   *   Identifier returned from previous query, or a string null.
   *   If null then then the second parameter is moved into $result (the type).
   *   This allow $this-fetchrow('num') for example.
   * @param string $type Default 'both'.
   *   It can be assoc=associative array, num=numerical array, obj=object, or both (for num and assoc).
   * @return array|null
   *   When there are no more rows this returns null otherwise
   *   either an assoc or numeric array, or an array with both numeric indices and associative indices.
   * @throws Exception|PDOException On Sql error, $this->result is null or $type is not allowed.
   */
  public function fetchrow(PDOStatement|string|null $result=null, string $type="both"): array|null {
    if(is_string($result)) { // a string like num, assoc, obj or both
      $type = $result;
      $result = $this->result; // was set in sql(...).
    }
    
    if(!$result) {
      throw new \Exception(__METHOD__ .", PDOStatment 'result' is null, line=". __LINE__);
    }

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
        $row = $result->fetch(PDO::FETCH_BOTH); // This is the default
        break;
      default:
        throw new \Exception(__METHOD__. ", invalid type=$type, line=". __LINE__);
    }

    if($row === false) $row = null;
    
    return $row;
  }
  
  /**
   * Get the ID from the last insert
   *
   * WARNING NEVER do multiple inserts with AUTO_INCREMENT without doing this method in between.
   * If we need to do
   * 'insert ... on duplicate key' we better not need the insert id. If we do we should do
   * an insert in a try block and an update in a catch. That way if the insert succeeds we can
   * do the getLastInsertId() after the insert. If the insert fails for a duplicate key we do the
   * update in the catch. And if we need the id we can do a select to get it (somehow).
   * Note if the insert fails because we did a 'insert ignore ...'
   * then last_id is zero and we return zero.
   *
   * @return int Last insert id
   */
  public function getLastInsertId(): int {
    return $this->lastInsertId();
  }

  // BLP 2025-05-07 - I don't think this works!
  /**
   * Get the number of rows
   *
   * @param PDOStatement|null $result.
   * @return int Affected rows or number of rows
   */
  public function getNumRows($result=null): int {
    if(!$result) $result = $this->result;
    if($result === true) {
      return $this->affected_rows;
    } else {
      return $result->num_rows;
    }
  }

  /**
   * Get the last PDOStatement
   *
   * This is the result of the most current query. This can be passed to
   *   fetchrow() as the first parameter.
   *
   * @return PDOStatement
   */
  public function getResult() {
    return $this->result;
  }

  /**
   * Get error information from most recent query
   *
   * @return string
   */
  public function getErrorInfo(): string {
    return ['errno'=>$this->getDbErrno(), 'error'=>$this->getDbError()];
  }
  
  /**
   * Escape a string for PDO
   *
   * @param string $string
   * @return string After apostrophes have been replaced with backslash apostrophes.
   */
  public function escape($string) {
    return str_replace("'", "\\'", $string);
  }

  /**
   * A Deep escape replacement of apostrophies
   *
   * @param string
   * @return string
   */
  public function escapeDeep(string $value): string {
    if(is_array($value)) {
      foreach($value as $k=>$v) {
        $val[$k] = $this->escapeDeep($v);
      }
      return $val;
    } else {
      return $this->escape($value);
    }
  }

  /**
   * Macic toString return the class name
   *
   * @return string Class name
   */
  public function __toString() {
    return __CLASS__;
  }

  /*
   * Get the column names for the last sql query.
   *
   * @param: string $prefix. Default null
   * @return: array. The column names in an array.
   */
  public function getColumnNames(string $prefix=null): array {
    if(!$this->result) return [];
    $cols = [];
    for($i = 0; $i < $this->result->columnCount(); $i++) {
      $meta = $this->result->getColumnMeta($i);
      $cols[] = "$prefix{$meta['name']}";
    }
    return $cols;
  }

  /*
   * Get the column names from the most recient sql select.
   *
   * @param: string $sql The sql statement
   * @param: array $attr
   *    An array ['prefix'=>$prefix, 'hdr'=>$hdr, 'features'=>$features,
   *   'hdrcallback'=>$hdrcallback, 'bodycallback'=>$bodycallback, 'ftrcallback'=>$ftrcallback].
   *   'features' can be a string of attributes for the <table> e.g. "class='myclass' id='myid' border='1'..."
   *   'prefix' if present. $prefix null|string.
   *   'hdr' if present. $hdr null, true, string.
   *   *callback if present. A callback function.
   *   Signitures of callbacks:
   *    hdrcallback(string $col): mixed.
   *    bodycallback(string $con, mixed, $val, array $row): mixed.
   *    ftrcallback(string $col): mixed.
   *
   * @return: string
   *   A fully formed table. If the sql($sql) returns 0 then returns null.
   *   By returning null we can do ?? and something.
   */
  public function maketableFromRows(string $sql, array $attr = null): string|null {
    if(!$this->sql($sql)) return null;

    // Safeguarded options
    $prefix        = $attr['prefix']        ?? '';
    $hdr           = $attr['hdr']           ?? null;
    $ftr           = $attr['ftr']           ?? null;
    $features      = $attr['features']      ?? '';
    $hdrcallback   = $attr['hdrcallback']   ?? null;
    $bodycallback  = $attr['bodycallback']  ?? null;
    $ftrcallback   = $attr['ftrcallback']   ?? null;

    // Collect column names and class names
    $colnames   = [];
    $classnames = [];
    for($i = 0; $i < $this->result->columnCount(); $i++) {
      $meta = $this->result->getColumnMeta($i);
      $colnames[] = $meta['name'];
      $classnames[] = $prefix . $meta['name'];
    }

    // Build header
    if($hdr === true) {
      $hdr = "<thead><tr>";
      foreach($colnames as $col) {
        $val = $hdrcallback ? $hdrcallback($col) : $col;
        $hdr .= "<th>" . htmlspecialchars($val) . "</th>";
      }
      $hdr .= "</tr></thead>\n<tbody>\n";
    } elseif(is_string($hdr)) {
      $hdr = "<thead>$hdr</thead>\n<tbody>\n";
    } else {
      $hdr = "<tbody>\n";
    }

    // Build body
    $lines = '';
    while($row = $this->fetchrow($this->result, 'num')) {
      $lines .= "<tr>";
      foreach($row as $i => $v) {
        $val = $bodycallback ? $bodycallback($colnames[$i], $v, $row) : $v;
        $lines .= "<td class='" . htmlspecialchars($classnames[$i]) . "'>" . htmlspecialchars($val) . "</td>";
      }
      $lines .= "</tr>\n";
    }

    // Build footer
    if($ftr === true) {
      $ftr = "<tfoot><tr>";
      foreach($colnames as $col) {
        $ftr .= "<td></td>"; // or some default cell
      }
      $ftr .= "</tr></tfoot>\n";
    } elseif(is_string($ftr)) {
      $ftr = "<tfoot>$ftr</tfoot>\n";
    } elseif(is_callable($ftrcallback)) {
      $ftr = "<tfoot>" . $ftrcallback($colnames) . "</tfoot>\n";
    } else {
      $ftr = ''; // no footer
    }

    return "<table $features>\n$hdr$lines</tbody>\n$ftr</table>\n";
  }
}
// End of class
  
