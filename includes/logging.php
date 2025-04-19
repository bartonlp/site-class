<?php
// This is the companion file to js/logging.js.
// logging.js uses beacon to send the information to this file
// which does the logging.
// I have created a new table.
/*
// BLP 2025-03-29 - 
// `event` is a string of name1,name2... These is the $event value. It is concatinated onto the
// value if there is a value. These events should only happen once per $id, so you could have
// scroll,click,...

 CREATE TABLE `interaction` (
  `index` int NOT NULL AUTO_INCREMENT,
  `id` int DEFAULT NULL,
  `ip` varchar(20) DEFAULT NULL,
  `site` varchar(100) DEFAULT NULL,
  `page` varchar(100) DEFAULT NULL,
  `event` varchar(256) DEFAULT NULL,
  `time` varchar(100) DEFAULT NULL,
  `created` timestamp NULL DEFAULT NULL,
  `lasttime` timestamp NULL DEFAULT NULL,
  `count` int DEFAULT '1',
  PRIMARY KEY (`index`),
  UNIQUE KEY `id_ip_site_page` (`id`,`ip`,`site`,`page`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
*/

$_site = require_once getenv("SITELOADNAME");
$_site->noTrack = true;
$S = new dbPdo($_site);

if($_POST) {
  $event = $_POST['event'] ?? 'unknown';
  $id = $_POST['id'];
  $ip = $_POST['ip'] ?? $_SERVER['REMOTE_ADDR'];
  $site = $_POST['site'] ?? '';
  $page = $_POST['page'] ?? '';
  $agent = $_POST['agent'] ?? '';
  $ts = $_POST['ts']/1000 ?? time();
  $ts =  date("Y-m-d H:i:s", $ts);

  // Note that $S->isMe() looks at myip and anyone who I have been is considered me. Here I only
  // want to not look at my ip.
  
  if($ip !== MY_IP) {
    $result = file_put_contents("./interaction.log", "[$ts] Interaction: id=$id, ip=$ip, site=$site, page=$page, event=$event\n", FILE_APPEND);
    if($result === false) {
      // Use error_log as backup.
      
      $err = error_get_last();
      error_log("logging.php Error={$err['message']}: id=$id, ip=$ip, site=$site, page=$page, event=$event, time=$ts, line=". __LINE__); 
    }

    // BLP 2025-03-29 - We now have a primary key `index` and a unique key `id`. `count` defaults
    // to one. I am using concat_ws(',', event, '$event'). It pust a comma in front of $event if it
    // is not null. Again, the events should only happen once per $id because of the JavaScript
    // (logging.js).

    $S->sql("insert into $S->masterdb.interaction (id, ip, site, page, event, time, created, lasttime) ".
            "values('$id', '$ip', '$site', '$page', '$event', '$ts', now(), now()) ".
            "on duplicate key update event=concat_ws(',', event, '$event'), count=count+1, lasttime=now()");

    // BLP 2025-04-18 - Use New logic to create a $db for updateBots3();

    $_site->nojquery = true;

    $db = Database::create($_site);
    $db->updateBots3($ip, $agent, $page, $site, BOTS_HAS_INTERACTION);
    
    $S->sql("update $S->masterdb.tracker set botAsBits=botAsBits|". BOTS_HAS_INTERACTION . ", count=count+1 where id=$id");
  }
  
  http_response_code(204); // No content
  exit();
}

http_response_code(204); // No content