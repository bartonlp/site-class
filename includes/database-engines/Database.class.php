<?php
/* Well tested and maintained */
// BLP 2022-01-14 -- Now we get the password from /home/barton/database-password
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
    ErrorClass::init(); // We should do this. If already done it just returns.

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

    // BLP BLP 2022-01-14 -- The Database password is now in /home/barton/database-password on
    // bartonlp.com et all (157.245.129.4), bartonphillips.org (HP) and
    // http://bartonphillips.dynnds.org (RPI).

    $password = ($this->dbinfo->password) ?? require("/home/barton/database-password");
    
    if(isset($arg->engine) === false) {
      $this->errno = -2;
      $this->error = "'engine' not defined";
      throw new SqlException(__METHOD__, $this);
    }

    switch($arg->engine) {
      case "mysqli":
        $class = "db" . ucfirst(strtolower($arg->engine));
        if(class_exists($class)) {
          $db = @new $class($arg->host, $arg->user, $password, $arg->database, $arg->port);
        } else {
          throw new SqlException(__METHOD__ .": Class Not Found : $class<br>");
        }
        break;
      case "sqlite3":
        // This is native sqlite not via pdo.
        $class = "dbSqlite";
        if(class_exists($class)) {
          $db = @new $class($arg->host, $arg->user, $password, $arg->database);
        } else {
          throw new SqlException(__METHOD__ .": Class Not Found : $class<br>");
        }      
        break;
      default:
        throw new SqlException(__METHOD__ . ": Engine $arg->engine not valid", $this);
    }
    if(is_null($db) || $db === false) {
      throw new SqlException(__METHOD__ . ": Connect failed", $this);
    }
    $this->db = $db;

    // BLP 2021-10-24 -- Check isSiteClass and if NOT set set the agent and ip

    if($this->isSiteClass !== true) {
      $this->agent = $_SERVER['HTTP_USER_AGENT'] ?? ''; // BLP 2022-01-28 -- if CLI useragent is NULL so make it blank.
      $this->agent = $this->escape($this->agent);
      $this->ip = $_SERVER['REMOTE_ADDR'];
    }
  }
  
  public function __toString() {
    return __CLASS__;
  }
}
