<?php
// WhatIsLoaded class.
// We return an array with one numeric and the rest are name=>value pairs.
// Look at the getWhatIsInfo() method for what is returned.

namespace bartonlp\whatisloaded;

define("WHATISLOADED_VERSION", "1.0.2whatis-pdo"); // BLP 2024-01-12 - Pdo back to Sql

(function() {
  class WhatIsLoaded {
    // make all of the values private
    private $site;
    private $siteClass;
    private $database;
    private $dbPdo;
    private $helper;
    private $tracker;
    private $beacon;
    private $javaScript;
    private $dbTables;
    private $ErrorClass;
        
    public function __construct() {
      // BLP 2024-01-20 - both siteload.php and autoload.php have 'namespace bartonlp\siteload'.
      $this->site = \bartonlp\siteload\getSiteloadVersion();

      $this->siteClass = \SiteClass::getVersion();
      $this->database = \Database::getVersion();
      $this->dbPdo = \dbPdo::getVersion();
      $this->helper = HELPER_FUNCTION_VERSION;

      $__VERSION_ONLY = true;
      
      $this->tracker = require(SITECLASS_DIR . "/tracker.php"); // With $__VERSION_ONLY only returns the version
      $this->beacon = require(SITECLASS_DIR . "/beacon.php");   // same here
      $javaScript = file_get_contents(SITECLASS_DIR . "/tracker.js");
      if(preg_match("~const TRACKERJS_VERSION[ \t]*=[ \t]*[\"'](.*?)[\"']~", $javaScript, $m)) {
        $this->javaScript = $m[1];
      }
      $this->dbTables = \dbTables::getVersion();
      $this->ErrorClass= \ErrorClass::getVersion();
    }

    public function getWhatIsInfo() {
      $whatis = $this->getVersion(); // Get the version
      
      $tbl =<<<EOF
<table id='whatIsLoaded' border='1'>
<tbody>
<tr><td>siteload.php</td><td>$this->site</td></tr>
<tr><td>SiteClass.class.php</td><td>$this->siteClass</td></tr>
<tr><td>Database.class.php</td><td>$this->database</td></tr>
<tr><td>dbPdo.class.php</td><td>$this->dbPdo</td></tr>
<tr><td>dbTables.class.php</td><td>$this->dbTables</td></tr>
<tr><td>ErrorClass.class.php</td><td>$this->ErrorClass</td></tr>
<tr><td>whatisloaded.class.php</td><td>$whatis</td></tr>
<tr><td>tracker.php</td><td>$this->tracker</td></tr>
<tr><td>beacon.php</td><td>$this->beacon</td></tr>
<tr><td>helper-functions.php</td><td>$this->helper</td></tr>
</tbody>
</table>
EOF;

      return [$tbl, "siteload.php"=>$this->site, "SiteClass.class.php"=>$this->siteClass,
      "Database.class.php"=>$this->database,
      "dbPdo.class.php"=>$this->dbPdo,
      "dbTables.class.php"=>$this->dbTables, "ErrorClass.class.php"=>$this->ErrorClass,
      "whatisloaded.class.php"=>$whatis,
      "tracker.php"=>$this->tracker, "beacon.php"=>$this->beacon,
      "tracker.js"=>$this->javaScript, "helper-functions.php"=>$this->helper];
    }

    public static function getVersion() {
      return WHATISLOADED_VERSION;
    }
  }
})();

return (new WhatIsLoaded)->getWhatIsInfo();
