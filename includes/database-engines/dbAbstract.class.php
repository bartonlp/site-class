<?php
// Abstract database class
// Most of this class is implemented here. This keeps us from having to duplicate this over and
// over again in each higher level class like SiteClass (site.class.hasdb.php)
// or Database (database.class.php). The db engines (dbMysqli.class.php, etc.) have most of
// these methods implemented and override these methods.
// 

abstract class dbAbstract {
  protected $db;

  // Each child class needs to have a __toString() method
  
  abstract public function __toString();

  public function getDbName() {
    return $this->db->database;
  }

  public function getDb() {
    return $this->db;
  }

  public function setDb($db) {
    $this->db = $this->databaseClass = $db;
  }
  
  // The following methods either execute or if the method is not defined throw an Exception

  public function query($query) {
    if(method_exists($this->db, 'query')) {
      return $this->db->query($query);
    } else {
      throw new Exception(__METHOD__ . " not implemented");
    }
  }
  
  public function fetchrow($result=null, $type="both") {
    if(method_exists($this->db, 'fetchrow')) {
      return $this->db->fetchrow($result, $type);
    } else {
      throw new Exception(__METHOD__ . " not implemented");
    }
  }

  public function queryfetch($query, $retarray=false) {
    if(method_exists($this->db, 'queryfetch')) {
      return $this->db->queryfetch($query, $retarray);
    } else {
      throw new Exception(__METHOD__ . " not implemented");
    }
  }

  public function getLastInsertId() {
    if(method_exists($this->db, 'getLastInsertId')) {
      return $this->db->getLastInsertId();      
    } else {
      throw new Exception(__METHOD__ . " not implemented");
    }
  }

  public function getResult() {
    if(method_exists($this->db, 'getResult')) {
      return $this->db->getResult();
    } else {
      throw new Exception(__METHOD__ . " not implemented");
    }
  }
    
  public function escape($string) {
    if(method_exists($this->db, 'escape')) {
      return $this->db->escape($string);
    } else {
      throw new Exception(__METHOD__ . " not implemented");
    }
  }
  
  public function escapeDeep($value) {
    if(method_exists($this->db, 'escapeDeep')) {
      return $this->db->escapeDeep($value);
    } else {
      throw new Exception(__METHOD__ . " not implemented");
    }
  }
  
  public function getNumRows($result=null) {
    if(method_exists($this->db, 'getNumRows')) {
      return $this->db->getNumRows($result);
    } else {
      throw new Exception(__METHOD__ . " not implemented");
    }
  }
  
  public function prepare($query) {
    if(method_exists($this->db, 'prepare')) {
      return $this->db->prepare($query);
    } else {
      throw new Exception(__METHOD__ . " not implemented");
    }
  }
  
  public function bindParam($format) {
    if(method_exists($this->db, 'bindParam')) {
      return $this->db->bindParam($format);
    } else {
      throw new Exception(__METHOD__ . " not implemented");
    }
  }
  
  public function bindResults($format) {
    if(method_exists($this->db, 'bindResults')) {
      return $this->db->bindResults($format);
    } else {
      throw new Exception(__METHOD__ . " not implemented");
    }
  }

  public function execute() {
    if(method_exists($this->db, 'execute')) {
      return $this->db->execute();
    } else {
      throw new Exception(__METHOD__ . " not implemented");
    }
  }

  public function getErrorInfo() {
    if(method_exists($this->db, 'getErrorInfo')) {
      return $this->db->getErrorInfo();
    } else {
      throw new Exception(__METHOD__ . " not implemented");
    }
  }
}
