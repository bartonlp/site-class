<?php
// All sites have a simlink to this file.
// BLP 2014-03-06 -- ajax for tracker.js

$_site = require_once(getenv("SITELOAD")."/siteload.php");
$S = new Database($_site);

$agent = $_SERVER['HTTP_USER_AGENT'];
$ip = $_SERVER['REMOTE_ADDR'];

$data = file_get_contents('php://input');
$data = json_decode($data, true);
$id = $data['id'];

if(!$id) {
  error_log("beacon: $S->siteName: NO ID, $ip, $agent");
  exit();
} else {
  $S->query("select isJavaScript from $S->masterdb.tracker where id=$id");
  list($js) = $S->fetchrow('num');

  // 4127 is 0x101F or 0x1000 timer, 0x10 noscript, 0xf start|load|script|normal
  // So if js is zero after the &~ then we do not have a (32|64|128) beacon,
  // or (256|512|1024) tracker:pagehide/beforeunload/unload. We should update.
  
  if(($js & ~(4127)) == 0) {
    error_log("beacon: $S->siteName: which={$data['which']}: id=$id, js=$js");

    // 'which' can be 1, 2, or 4
    
    $beacon = $data['which'] * 32; // 0x20, 0x40 or 0x80
    $S->query("update $S->masterdb.tracker set endtime=now(), difftime=timediff(now(),starttime), ".
              "isJavaScript=isJavaScript|$beacon, lasttime=now() where id=$id");
  }
  echo "Beacon OK: {$data['which']}";
  exit();
}

echo <<<EOF
<!DOCTYPE html>
<html>
<head>
</head>
<body>
<h1>Go Away!</h1>
</body>
</html>
EOF;
