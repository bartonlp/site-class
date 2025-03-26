<?php
// This is the companion file to js/logging.js.
// logging.js uses beacon to send the information to this file
// which does the logging.
// I have created a new table.
/*
CREATE TABLE `interaction` (
`index` int NOT NULL AUTO_INCREMENT,
`id` int DEFAULT NULL,
`ip` varchar(20) DEFAULT NULL,
`site` varchar(100) DEFAULT NULL,
`page` varchar(100) DEFAULT NULL,
`event` varchar(100) DEFAULT NULL,
`time` varchar(100) DEFAULT NULL,
`created` timestamp NULL DEFAULT NULL,
`lasttime` timestamp NULL DEFAULT NULL,
`count` int DEFAULT '1',
PRIMARY KEY (`index`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
*/

$_site = require_once getenv("SITELOADNAME");
$S = new dbPdo($_site);

if($_POST) {
  $event = $_POST['event'] ?? 'unknown';
  $id = $_POST['id'];
  $ip = $_POST['ip'] ?? $_SERVER['REMOTE_ADDR'];
  $site = $_POST['site'] ?? '';
  $page = $_POST['page'] ?? '';
  $ts = $_POST['ts']/1000 ?? time();
  $ts =  date("Y:m:d H:i:s", $ts);

  // Note that $S->isMe() looks at myip and anyone who I have been is considered me. Here I only
  // want to not look at my ip.
  
  if($ip !== MY_IP) {
    $result = file_put_contents("./interaction.log", "Interaction: id=$id, ip=$ip, site=$site, page=$page, event=$event, time=$ts\n", FILE_APPEND);
    if($result === false) {
      // Use error_log as backup.
      
      $err = error_get_last();
      error_log("logging.php Error={$err['message']}: id=$id, ip=$ip, site=$site, page=$page, event=$event, time=$ts, line=". __LINE__); 
    }

    // I should really not need the on duplicate key section or count but who knows.
    
    $S->sql("insert into $S->masterdb.interaction (id, ip, site, page, event, time, created, lasttime) ".
            "values('$id', '$ip', '$site', '$page', '$event', '$ts', now(), now()) ".
            "on duplicate key update count=count+1, lasttime=now()");
  }
  
  http_response_code(204); // No content
  exit();
}

http_response_code(204); // No content