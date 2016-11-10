<?php
/* Well tested and maintained */

/**
 * Database wrapper class
 */

class Database extends dbAbstract {
  /**
   * constructor
   * @param mixed
   *    1) strings: host, user, password, database, engine
   *    2) array: can be $dbinfo or $_site with everythin
   *    3) object: as above
   */

  public function __construct(/* mixed */) {
    $args = func_get_args();
    $n = func_num_args();
    $arg = array();

    if($args[0]->isSiteClass) {
      $arg = $args[0]->dbinfo;
    } else {
      if($n == 1) {
        if(!$args[0]->dbinfo) {
          $arg = $args[0];
        } else {
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
        }
      } else {
        // strings
        $keys = array('host', 'user', 'password', 'database', 'engine');
        for($i=0; $i < $n; ++$i) {
          $arg[$keys[$i]] = $args[$i];
        }
        $arg = (object)$arg;
      }

      // Transfer $args to $this
      
      foreach($arg as $k=>$v) {
        $this->$k = $v; 
      }
      $db = null;
      $err = null;
      $arg = $this->dbinfo ? $this->dbinfo : $arg;
    }

    //vardump("dbinfo", $arg);
    //vardump("this", $this);
    //$arg->engine = null;
    
    if(isset($arg->engine) === false) {
      $this->errno = -2;
      $this->error = "'engine' not defined";
      throw(new SqlException(__METHOD__, $this));
    }

    switch($arg->engine) {
      case "mysqli":
        $class = "db" . ucfirst(strtolower($arg->engine));
        if(class_exists($class)) {
          $db = @new $class($arg->host, $arg->user, $arg->password, $arg->database);
        } else {
          throw(new SqlException(__METHOD__ .": Class Not Found : $class<br>"));
        }
        break;
      case "sqlite3":
        // This is native sqlite not via pdo.
        $class = "dbSqlite";
        if(class_exists($class)) {
          $db = @new $class($arg->host, $arg->user, $arg->password, $arg->database);
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
