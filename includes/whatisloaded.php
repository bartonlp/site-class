<?php
// This program should be 'require'd. It presents an object with the following property keys:
// site: the siteload version
// siteClass: the SiteClass version
// database: the Database version
// dbMysqli: the dbMysqli version
// tracker: the tracker.php version
// beacon: the beachon.php version
// trackerjs: the javescript tracker.js version

if($_site) {
  $site = SITELOAD_VERSION;
  
  if($S) {
    if(method_exists($S, "getPageTopBottom")) {
      $siteClass = $S->getVersion();
      $database = Database::getVersion();
      if($_site->nodb !== true) {
        $dbMysqli = $S->db->getVersion();
      }
      if($_site->noTrack !== true) { 
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
      if($_site->nodb !== true) {
        $dbMysqli = $S->db->getVersion();
      }
    }
  }
}

$ret = (object)["site"=>$site, "siteClass"=>$siteClass, "database"=>$database, "dbMysqli"=>$dbMysqli, "tracker"=>$tracker, "beacon"=>$beacon, "trackerjs"=>$javaScript];
//echo json_encode($ret);
return $ret;