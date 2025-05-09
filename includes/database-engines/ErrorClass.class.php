<?php
/* MAINTAINED and WELL TESTED */

define("ERROR_CLASS_VERSION", "4.0.2.1error-pdo"); // BLP 2025-04-29 - removed errLast and added noLastQuery

// Error class.

class ErrorClass {
  private static $noEmail = true;
  private static $development = false;
  private static $noHtml = false;
  private static $errType = null;
  private static $noOutput = false;
  private static $noBacktrace = false; // BLP 2023-06-22
  private static $noLastQuery = false; // BLP 2025-04-29 -
  private static $noErrorId = false; // BLP 2025-04-29 -
  
  static public function setErrorType($bits) {
    self::$errType = $bits;

    // BLP 2023-11-12 - error_reporting is initialy set in the php.ini file.
    // It is set to: E_ALL & ~E_DEPRECATED & ~E_STRICT & ~E_NOTICE & ~E_WARNING
    
    error_reporting($bits);

    // BLP 2023-11-12 - NOTE, I do not set_error_handler() here or anywhere. I have commented out
    // my_errorhandler() above.
  }

  static public function getErrorType() {
    return self::$errType;
  }

  static public function setNoErrorId($b) {
    self::$noErrorId = $b;
  }

  static public function getNoErrorId() {
    return self::$noErrorId;
  }
  
  static public function setNoLastQuery($b) {
    self::$noLastQuery = $b;
  }

  static public function getNoLastQuery() {
    return self::$noLastQuery;
  }
  
  static public function setNoBackTrace($b) {
    self::$noBacktrace = $b;
  }

  static public function getNoBackTrace() {
    return self::$noBacktrace;
  }

  // if we set development also set noEmai!
  
  static public function setDevelopment($b) {
    self::$development = $b;
    self::$noEmail = $b;
  }

  static public function getDevelopment() {
    return self::$development;
  }
  
  static public function setNoEmail($b) {
    self::$noEmail = $b;
  }

  static public function getNoEmail() {
    return self::$noEmail;
  }
  
  static public function setNoHtml($b) {
    self::$noHtml = $b;
  }

  static public function getNoHtml() {
    return self::$noHtml;
  }
  
  static public function setNoOutput($b) {
    return self::$noOutput = $b;
  }

  static public function getNoOutput() {
    return self::$noOutput;
  }

  static public function getVersion() {
    return ERROR_CLASS_VERSION;
  }
  
  public function __toString() {
    return __CLASS__;
  }
}
