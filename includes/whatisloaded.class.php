<?php
// WhatIsLoaded class.

namespace whatis;

class WhatIsLoaded {

  public function __construct($site) {
    if($site) {
      $site = SITELOAD_VERSION;
  
      if($S) {
        if(method_exists($S, "getPageTopBottom")) {
          $siteClass = $S->getVersion();
          $database = Database::getVersion();
          if($site->nodb !== true) {
            $dbMysqli = $S->db->getVersion();
          }
          if($site->noTrack !== true) { 
            if($h->nojquery !== true) {
              if($S->getPageHead === true) {
                $__VERSION_ONLY = true;
    
                $tracker = require("/var/www/bartonlp.com/otherpages/tracker.php");
                $beacon = require("/var/www/bartonlp.com/otherpages/beacon.php");
                $javaScript = file_get_contents($h->trackerLocationJs);
                if(preg_match("~const TRACKERJS_VERSION[ \t]*=[ \t]*[\"'](.*?)[\"']~", $javaScript, $m)) {
                  $javaScript = $m[1];
                }
              }
            }
          }
        } else {
          $database = $S->getVersion();
          if($site->nodb !== true) {
            $dbMysqli = $S->db->getVersion();
          }
        }
      }
    }
    $this->info = (object)["site"=>$site, "siteClass"=>$siteClass, "database"=>$database, "dbMysqli"=>$dbMysqli, "tracker"=>$tracker, "beacon"=>$beacon, "trackerjs"=>$javaScript];
  }

  public function getinfo() {
    return $this->info;
  }
}
