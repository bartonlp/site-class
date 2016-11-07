<?php
// BLP 2014-09-14 -- The .htaccess file has: ReWriteRule ^robots.txt$ robots.php [L,NC]
// This file reads the rotbots.txt file and outputs it and then gets the user agent string and
// saves it in the bots table.

$_site = require_once(getenv("SITELOAD")."/siteload.php");
$S = new Database($_site);

$robots = file_get_contents($_site->path."/robots.txt");
echo $robots;

$ip = $_SERVER['REMOTE_ADDR'];
$agent = $S->escape($_SERVER['HTTP_USER_AGENT']);

$S->query("select count(*) from information_schema.tables ".
           "where (table_schema = '$S->masterdb') and (table_name = 'bots')");

list($ok) = $S->fetchrow('num');
      
if($ok == 1) {
  try {
    //error_log("robots: $S->siteName, $ip, $agent");

    $S->query("insert into $S->masterdb.bots (ip, agent, count, robots, who, creation_time, lasttime) ".
               "values('$ip', '$agent', 1, 1, '$S->siteName', now(), now())");
  }  catch(Exception $e) {
    if($e->getCode() == 1062) { // duplicate key
      $S->query("select who from $S->masterdb.bots where ip='$ip'");

      list($who) = $S->fetchrow('num');

      if(!$who) {
        $who = $S->siteName;
      }
      if(strpos($who, $S->siteName) === false) {
        $who .= ", $S->siteName";
      }
      $S->query("update $S->masterdb.bots set robots=robots | 2, who='$who', count=count+1, lasttime=now() ".
                 "where ip='$ip'");
    } else {
      error_log("robots: ".print_r($e, true));
    }
  }
} else {
  error_log("robots: $S->siteName bots does not exist in $S->masterdb database");
}

$S->query("select count(*) from information_schema.tables ".
           "where (table_schema = '$S->masterdb') and (table_name = 'bots2')");

list($ok) = $S->fetchrow('num');

if($ok) {
  $S->query("insert into $S->masterdb.bots2 (ip, agent, date, site, which, count, lasttime) ".
             "values('$ip', '$agent', current_date(), '$S->siteName', 1, 1, now()) ".
             "on duplicate key update count=count+1, lasttime=now()");
} else {
  error_log("robots: $S->siteName bots2 does not exist in $S->masterdb database");
}
