<?php
// WhatIsLoaded.
// We return an array with one numeric and the rest are name=>value pairs.
// Look at the getWhatIsInfo() method for what is returned.

namespace bartonlp\whatisloaded;

define("WHATISLOADED_VERSION", "3.0.2whatisloaded-mysqli");

// Define a class within a closed function. The class is not available from the outside world.

(function() {
  class WhatIsLoaded {
    // make all of the values private
    private $site;
    private $siteClass;
    private $database;
    private $dbMysqli;    
    private $helper;
    private $tracker;
    private $beacon;
    private $javaScript;
    private $dbTables;
    private $ErrorClass;
    private $SqlException;
        
    public function __construct() {
      $__VERSION_ONLY = true; // also used by siteload.php, tracker.php, beacon.php.

      $this->site = require(getenv("SITELOADNAME"));
      //require("/var/www/site-class/includes/autoload.php");
      
      $this->site = SITELOAD_VERSION;

      $this->siteClass = \SiteClass::getVersion();
      $this->siteClass = \SiteClass::getVersion();
      $this->database = \Database::getVersion();
      $this->dbMysqli = \dbMysqli::getVersion();
      $this->helper = HELPER_FUNCTION_VERSION;

      $this->tracker = require(SITECLASS_DIR . "/tracker.php");
      $this->beacon = require(SITECLASS_DIR . "/beacon.php");
      $javaScript = file_get_contents(SITECLASS_DIR . "/tracker.js");
      if(preg_match("~const TRACKERJS_VERSION[ \t]*=[ \t]*[\"'](.*?)[\"']~", $javaScript, $m)) {
        $this->javaScript = $m[1];
      }
      $this->dbTables = \dbTables::getVersion();
      $this->ErrorClass= \ErrorClass::getVersion();
      $this->SqlException = \SqlException::getVersion();
    }

    public function getWhatIsInfo() {
      $whatis = $this->getVersion(); // Get the version
      
      $tbl =<<<EOF
<table id='whatIsLoaded' border='1'>
<tbody>
<tr><td>siteload.php</td><td>$this->site</td></tr>
<tr><td>SiteClass.class.php</td><td>$this->siteClass</td></tr>
<tr><td>Database.class.php</td><td>$this->database</td></tr>
<tr><td>dbMysqli.class.php</td><td>$this->dbMysqli</td></tr>
<tr><td>dbTables.class.php</td><td>$this->dbTables</td></tr>
<tr><td>ErrorClass.class.php</td><td>$this->ErrorClass</td></tr>
<tr><td>SqlException.class.php</td><td>$this->SqlException</td></tr>
<tr><td>whatisloaded.class.php</td><td>$whatis</td></tr>
<tr><td>tracker.php</td><td>$this->tracker</td></tr>
<tr><td>beacon.php</td><td>$this->beacon</td></tr>
<tr><td>helper-functions.php</td><td>$this->helper</td></tr>
</tbody>
</table>
EOF;

      return [$tbl, "siteload.php"=>$this->site, "SiteClass.class.php"=>$this->siteClass,
      "Database.class.php"=>$this->database, "dbMysqli.class.php"=>$this->dbMysqli,
      "dbTables.class.php"=>$this->dbTables, "ErrorClass.class.php"=>$this->ErrorClass,
      "SqlException.class.php"=>$this->SqlException, 
      "whatisloaded.class.php"=>$whatis,
      "tracker.php"=>$this->tracker, "beacon.php"=>$this->beacon,
      "tracker.js"=>$this->javaScript, "helper-functions.php"=>$this->helper];
    }

    public static function getVersion() {
      return WHATISLOADED_VERSION;
    }
  }
})();

// Here we have a simple return of the info.

return (new WhatIsLoaded)->getWhatIsInfo();
