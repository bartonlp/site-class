<?php
// BLP 2016-07-28 -- update lasttime in insert and update.
// BLP 2016-02-18 -- This file is a substitute for Sitemap.xml. This file is RewriteRuled in
// .htaccess to read Sitemap.xml and output it. It also writes a record into the bots table

$_site = require_once(getenv("SITELOAD")."/siteload.php");

$db = new Database($_site->dbinfo);

if(!file_exists($_site->path . "/Sitemap.xml")) {
  echo "NO SITEMAP<br>";
  exit();
}

$sitemap = file_get_contents($_site->path."/Sitemap.xml");
echo $sitemap;

$ip = $_SERVER['REMOTE_ADDR'];
$agent = $db->escape($_SERVER['HTTP_USER_AGENT']);

error_log("sitemap: $_site->siteName, $ip, $agent");

$db->query("select count(*) from information_schema.tables ".
           "where (table_schema = '$_site->masterdb') and (table_name = 'bots')");

list($ok) = $db->fetchrow('num');
      
if($ok == 1) {
  try {
    $db->query("insert into $_site->masterdb.bots (ip, agent, count, robots, who, creation_time, lasttime) ".
               "values('$ip', '$agent', 1, 16, '$_site->siteName', now(), now())");
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
      $db->query("update $_site->masterdb.bots set robots=robots | 32, who='$who', count=count+1, lasttime=now() where ip='$ip'");
    } else {
      error_log("sitemap: ".print_r($e, true));
    }
  }
} else {
  error_log("sitemap: $_site->siteName bots does not exist in $_site->masterdb database");
}

$db->query("select count(*) from information_schema.tables ".
           "where (table_schema = '$_site->masterdb') and (table_name = 'bots2')");

list($ok) = $db->fetchrow('num');
      
if($ok == 1) {
  $db->query("insert into $_site->masterdb.bots2 (ip, agent, date, site, which, count, lasttime) ".
             "values('$ip', '$agent', current_date(), '$_site->siteName', 4, 1, now()) ".
             "on duplicate key update count=count+1, lasttime=now()");
} else {
  error_log("sitemap: $_site->siteName bots does not exist in $_site->masterdb database");
}
