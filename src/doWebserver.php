<?php
$_site = require_once getenv("SITELOADNAME");
$db = new dbPdo($_site);

// $engine should be only 'mysql' except for testing. It should be 'mysql' almost all the time.

$engine = $db->dbinfo->engine; // Detemin which server we use in JSON
if($engine === 'sqlite') {
  // This should be in error_log.
  error_log("***** This is in 'sqlite' for only test peruses.");
}

header('Content-Type: application/json'); // Make it application/json.

// --- read JSON input ---
$input = json_decode(file_get_contents('php://input'), true);

// --- basic validation ---
if(!is_array($input)) {
  http_response_code(400);
  error_log("doWebServer.php: error=invalid from \$input");
  exit;
}

$type = $input['type']; // Get the $type, 'select' or 'insert'

// --- whitelist tables --- 'logagent' is the only one we really use.
$allowedTables = ['logagent']; // This is the only one currently.

$table = $input['table'];

if(!in_array($table, $allowedTables)) {
  http_response_code(400);
  error_log("doWebServer: error=invalid table.");
  exit;
}

$site = $input['site'];
$ip = $input['ip'];
$agent = $input['agent'];

switch($type) {
  case 'select':
    $where = "where site=? and ip=? and agent=?";

    $query = "SELECT * FROM $table $where ORDER BY lasttime DESC";

    $n = $db->sql($query, [$site, $ip, $agent]);

    if(!$n) {
      error_log("doWebServer.php select: select Error=$n");
      exit;
    }

    $data = [];

    while($row = $db->fetchrow('assoc')) {
      $data[] = $row;
    }

    $count = count($data);

    echo json_encode([
                      'query' => $query,
                      'count' => $count,
                      'params'=> $params,
                      'data'  => $data,
                     ]);
    break;
  case 'insert':
    $params = [$site,
               $ip,
               $agent,
              ];

    if(!$site || !$ip || !$agent) { 
      http_response_code(400);
      error_log("doWebServer.php: error=Error missing fields. site or ip or agent");
      exit;
    }

    switch($engine) {
      case "mysql":
        $query = "INSERT INTO $table (site, ip, agent, count, lasttime)
VALUES (?, ?, ?, 1, NOW())
ON DUPLICATE KEY UPDATE
count = count + 1,
lasttime = NOW()";
        break;
      case "sqlite":
        $query = "CREATE TABLE IF NOT EXISTS $table (`site` varchar(25) NOT NULL DEFAULT '',
`ip` varchar(40) NOT NULL DEFAULT '',
`agent` varchar(254) NOT NULL,
`count` int DEFAULT NULL,
`created` text NOT NULL DEFAULT CURRENT_TIMESTAMP,
`lasttime` text DEFAULT NULL,
PRIMARY KEY (`site`,`ip`,`agent`))";

        $n = $db->sql($query);

        if(!$n) {
          error_log("doWebServer.php: create error");
          exit;
        }

        $query = "insert into $table (site, ip, agent, count, lasttime)
values (?, ?, ?, 1, datetime('now','localtime'))
on conflict(site, ip, agent)
do update set
count = count + 1,
lasttime = datetime('now','localtime')";
        break;
      default:
        error_log("doWebServer.php SWITCH: engine=$engine, type=$type");
        exit;
    }

    $n = $db->sql($query, $params);

    if(!$n) {
      error_log("doWebServer.php: Error insert=$n");
      exit;
    }

    echo json_encode(['query' => $query, 'params' => [$site, $ip, $agent], 'num' => $n,]);
    break;
  default:
    error_log("doWebServer.php SWITCH ERROR: type=$type");
    exit;
}
