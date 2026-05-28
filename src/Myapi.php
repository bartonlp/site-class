<?php
$_site = require_once getenv("SITELOADNAME");
// Because bartonlp.com/otherpages have mysitemap.json dbinfo->database = 'barton' we don't need
// the special $_site->dbinfo->database!

$db = new dbPdo($_site);

header('Content-Type: application/json'); // Make it application/json.

// --- read JSON input ---
$input = json_decode(file_get_contents('php://input'), true);

// --- basic validation ---
if(!is_array($input)) {
  http_response_code(400);
  error_log("Myapi.php error=invalid from \$input:" . print_r($input, true));
  exit;
}

//error_log("Myapi.php input: " . print_r($input, true));

try {
  $sql = $input['sql']; // select or insert
  $params = $input['params'];

  error_log("Myapi.php sql=$sql\nparams: " . print_r($params, true));
  
  $result = $db->sql($sql, $params);

  $allowedTables = ['insert', 'update', 'delete', 'create', 'drop', 'alter', 'truncate', 'set', 'grant', 'revoke', 'use'];
  if(in_array(strstr(haystack: $sql, needle: ' ', before_needle: true), $allowedTables)) {
    echo json_encode($result);
    exit;
  }

  $result = [];
  while($tbl = $db->fetchrow('assoc')) {
    $result[] = $tbl;
  }
  //error_log("Myapi.php get fetchrow result: " . print_r($result, true));
    
  echo json_encode($result);
  exit;
} catch(\Throwable $e) {
  error_log("Myapi.php ERROR: code=500\n{$e->getMessage()}\n{$e->getFile()}\n{$e->getLine()}");
  echo json_encode(["ok, Myapi.php" => false,
                    "error" => "code=500\n{$e->getMessage()}\n{$e->getFile()}\n{$e->getLine()}",
                   ]);
}
