<?php
/* MAINTAINED and WELL TESTED */

define("ERROR_CLASS_VERSION", "2.0.0error");

// Error class.

class ErrorClass {
  private static $noEmail = false;
  private static $development = false;
  private static $noHtml = false;
  private static $errType = null;
  private static $noOutput = false;
  private static $noBacktrace = false; // BLP 2023-06-22
  private static $errLast = false; // BLP 2023-06-22
  
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

  // BLP 2023-06-22 - START
  static public function setNobacktrace($b) {
    self::$noBacktrace = $b;
  }

  static public function getNobacktrace() {
    return self::$noBacktrace;
  }

  static public function setErrlast($b) {
    self::$errLast = $b;
  }

  static public function getErrlast() {
    return self::$errLast;
  }

  //BLP 2021-03-15 -- if we set development also set noEmai!
  
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
