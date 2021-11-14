<?php
/* Well tested and maintained */
// BLP 2021-11-11 -- For RPI let us use the $this->dbinfo->password if it exists
// BLP 2021-10-28 -- see comment below.
// BLP 2021-10-24 -- Added agent and ip if not isSiteClass.

/**
 * Database wrapper class
 */

class Database extends dbAbstract {
  /**
   * BLP 2021-10-28 -- major overhall. Now we pass only an object in, before we passed a complicated 'mixed' in.
   * There is no program that uses anything but $_site from mysitemap.json.
   *
   * constructor
   * @param $args object.
   * $args should have all of the $this from SiteClass or $_site from mysitemap.json
   * To just pass in the required database options set $args->dbinfo = (object) $ar
   * where $ar is an assocative array with ["host"=>"localhost",...]
   */

  public function __construct(object $args) {
    foreach($args as $k=>$v) {
      $this->$k = $v;
    }

    if($this->nodb) {
      return;
    }

    $db = null;
    $err = null;
    $arg = $this->dbinfo;

    //vardump("dbinfo", $arg);
    //vardump("this", $this);
    //$arg->engine = null;

    // BLP 2021-11-11 --
    
    $password = ($this->dbinfo->password) ?? require("/var/www/bartonphillipsnet/PASSWORDS/database-password");
    
    if(isset($arg->engine) === false) {
      $this->errno = -2;
      $this->error = "'engine' not defined";
      throw(new SqlException(__METHOD__, $this));
    }

    switch($arg->engine) {
      case "mysqli":
        $class = "db" . ucfirst(strtolower($arg->engine));
        if(class_exists($class)) {
          $db = @new $class($arg->host, $arg->user, $password, $arg->database, $arg->port);
        } else {
          throw(new SqlException(__METHOD__ .": Class Not Found : $class<br>"));
        }
        break;
      case "sqlite3":
        // This is native sqlite not via pdo.
        $class = "dbSqlite";
        if(class_exists($class)) {
          $db = @new $class($arg->host, $arg->user, $password, $arg->database);
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

    // BLP 2021-10-24 -- Check isSiteClass and if NOT set set the agent and ip
    
    if(!$this->isSiteClass) {
      $this->agent = $this->escape($_SERVER['HTTP_USER_AGENT']);
      $this->ip = $_SERVER['REMOTE_ADDR'];
    }
  }
  
  public function __toString() {
    return __CLASS__;
  }
}
