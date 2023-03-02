<?php
// WhatIsLoaded class.

namespace whatis;

define("WHATISLOADED_VERSION", "1.0.0whatis");

class WhatIsLoaded {

  public function __construct($site) {
    $this->site = $site; // This is $_site
  }

  public function getinfo() {
    global $S;
    
    if($this->site) {
      $site = SITELOAD_VERSION;
  
      if($S) {
        if(method_exists($S, "getPageTopBottom")) {
          $siteClass = $S->getVersion();
          $database = \Database::getVersion();
          if($S->nodb !== true) {
            $dbMysqli = $S->db->getVersion();
          }
          if($S->noTrack !== true) {
            if($S->nojquery !== true) {
              if($S->getPageHead === true) {
                $__VERSION_ONLY = true;

                $tracker = require("/var/www/bartonlp.com/otherpages/tracker.php");
                $beacon = require("/var/www/bartonlp.com/otherpages/beacon.php");

                if($S->trackerLocationJs) {
                  $javaScript = file_get_contents($S->trackerLocationJs);
                  if(preg_match("~const TRACKERJS_VERSION[ \t]*=[ \t]*[\"'](.*?)[\"']~", $javaScript, $m)) {
                    $javaScript = $m[1];
                  }
                }
              }
            }
          }
        } else {
          $database = $S->getVersion();
          if($S->nodb !== true) {
            $dbMysqli = $S->db->getVersion();
          }
        }
      }
    }
    $whatis = $this->getVersion();
    return (object)["siteload.php"=>$site, "SiteClass.class.php"=>$siteClass,
                    "Database.class.php"=>$database, "dbMysqli.class.php"=>$dbMysqli, "tracker.php"=>$tracker,
                    "beacon.php"=>$beacon, "tracker.js"=>$javaScript, "whatisloaded.class.php"=>$whatis];
  }

  public static function getVersion() {
    return WHATISLOADED_VERSION;
  }
}
