<?php
// Select the database engine we want to use
/**
 * Database wrapper class
 *
 */

class Database extends dbAbstract {
  /**
   * constructor
   * @param mixed
   *    1) strings: host, user, password, database, engine
   *    2) array: as above
   *    3) object: as above
   */
  
  public function __construct(/* mixed */) {
    $args = func_get_args();
    $n = func_num_args();
    $arg = array();

    if($n == 1) {
      // An array or object
      $a = $args[0];

      if(is_object($a)) {
        foreach($a as $k=>$v) {
          $arg[$k] = $v;
        }
      } elseif(is_array($a)) {
        $arg = $a;
      } else {
        throw(new Exception("Error: argument not array or object: ". print_r($a, true)));
      }
    } elseif($n > 1) {
      // strings
      $keys = array('host', 'user', 'password', 'database', 'engine');
      for($i=0; $i < $n; ++$i) {
        $arg[$keys[$i]] = $args[$i];
      }
    }

    $db = null;

    $err = null;

    /*
    if(isset($arg['errorctrl'])) {
      $err = $arg['errorctrl'];
    }
    */
    $arg = (object)$arg;

    //$this->errorClass = new Error($err);

    if(!isset($arg->engine)) {
      $this->errno = -2;
      $this->error = "'engine' not defined";
      throw(new SqlException(__METHOD__, $this));
    }

    switch($arg->engine) {
      case "mysql":
      case "mysqli":
        $class = "db" . ucfirst(strtolower($arg->engine));
        if(class_exists($class)) {
          $db = @new $class($arg->host, $arg->user, $arg->password, $arg->database);
        } else {
          throw(new SqlException(__METHOD__ .": Class Not Found : $class<br>"));
        }
        break;
      case "sqlite":
        $class = 'dbPdo';
        if(class_exists($class)) {
          $db = @new $class($arg->host, $arg->user, $arg->password, $arg->database, 'sqlite');
        } else {
          throw(new SqlException(__METHOD__ .": Class Not Found : $class<br>"));
        }      
        break;
      case "pgsql":
        $class = 'dbPdo';
        if(class_exists($class)) {
          $db = @new $class($arg->host, $arg->user, $arg->password, $arg->database, 'pgsql');
        } else {
          throw(new SqlException(__METHOD__ .": Class Not Found : $class<br>"));
        }      
        break;
      case "sqlite2":
        // This is native sqlite not via pdo.
        $class = "dbSqlite";
        if(class_exists($class)) {
          $db = @new $class($arg->host, $arg->user, $arg->password, $arg->database, 'pgsql');
        } else {
          throw(new SqlException(__METHOD__ .": Class Not Found : $class<br>"));
        }      
        break;
      default:
        throw(new SqlException(__METHOD__ . ": Engine $arg->engine not valid", $this));
    }
    if(is_null($db) || $db === false) {
      throw(new SqlException(__METHOD__ . ": Connect failed", $this));
    }
    $this->db = $db;
  }
  
  public function __toString() {
    return __CLASS__;
  }
}
