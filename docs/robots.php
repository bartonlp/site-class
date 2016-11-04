<?php
// BLP 2014-09-14 -- The .htaccess file has: ReWriteRule ^robots.txt$ robots.php [L,NC]
// This file reads the rotbots.txt file and outputs it and then gets the user agent string and
// saves it in the bots table.

$_site = require_once(getenv("SITELOAD")."/siteload.php");

$db = new Database($_site->dbinfo);

$robots = file_get_contents($_site->path."/robots.txt");
echo $robots;

$ip = $_SERVER['REMOTE_ADDR'];
$agent = $db->escape($_SERVER['HTTP_USER_AGENT']);

$db->query("select count(*) from information_schema.tables ".
           "where (table_schema = '$_site->masterdb') and (table_name = 'bots')");

list($ok) = $db->fetchrow('num');
      
if($ok == 1) {
  try {
    //error_log("robots: $_site->siteName, $ip, $agent");

    $db->query("insert into $_site->masterdb.bots (ip, agent, count, robots, who, creation_time, lasttime) ".
               "values('$ip', '$agent', 1, 1, '$_site->siteName', now(), now())");
  }  catch(Exception $e) {
    if($e->getCode() == 1062) { // duplicate key
      $db->query("select who from $_site->masterdb.bots where ip='$ip'");

      list($who) = $db->fetchrow('num');

      if(!$who) {
        $who = $_site->siteName;
      }
      if(strpos($who, $_site->siteName) === false) {
        $who .= ", $_site->siteName";
      }
      $db->query("update $_site->masterdb.bots set robots=robots | 2, who='$who', count=count+1, lasttime=now() ".
                 "where ip='$ip'");
    } else {
      error_log("robots: ".print_r($e, true));
    }
  }
} else {
  error_log("robots: $_site->siteName bots does not exist in $_site->masterdb database");
}

$db->query("select count(*) from information_schema.tables ".
           "where (table_schema = '$_site->masterdb') and (table_name = 'bots2')");

list($ok) = $db->fetchrow('num');

if($ok) {
  $db->query("insert into $_site->masterdb.bots2 (ip, agent, date, site, which, count, lasttime) ".
             "values('$ip', '$agent', current_date(), '$_site->siteName', 1, 1, now()) ".
             "on duplicate key update count=count+1, lasttime=now()");
} else {
  error_log("robots: $_site->siteName bots2 does not exist in $_site->masterdb database");
}
