<?php
// BLP 2014-03-06 -- ajax for tracker.js

$_site = require_once(getenv("SITELOAD")."/siteload.php");
$S = new Database($_site);

$ip = $_SERVER['REMOTE_ADDR'];
$agent = $_SERVER['HTTP_USER_AGENT'];

//error_log("tracker: ". print_r($_REQUEST, true));

// Post an ajax error message

if($_POST['page'] == 'ajaxmsg') {
  $msg = $_POST['msg'];
  // NOTE: $_POST['ipagent'] is a string not a boolian! So === true does NOT work but == true
  // or == 'true' does work.
  $ipagent = ($_POST['ipagent'] == 'true') ? ": $ip, $agent" : '';
  error_log("tracker: AJAXMSG, $S->siteName, '$msg'" . $ipagent);
  echo "AJAXMSG OK";
  exit();
}

$S->query("select count(*) from information_schema.tables ".
          "where (table_schema = '$S->masterdb') and (table_name = 'tracker')");

list($ok) = $S->fetchrow('num');

if($ok != 1) {
  error_log("No tracker in $S->masterdb");
  exit();
}

// start is an ajax call

if($_POST['page'] == 'start') {
  $id = $_POST['id'];
  
  if(!$id) {
    error_log("tracker: $S->siteName: START NO ID, $ip, $agent");
    exit();
  }

  //error_log("tracker: start,    $S->siteName, $id, $ip, $agent");
  
  $S->query("update $S->masterdb.tracker set isJavaScript=isJavaScript|1, lasttime=now() where id='$id'");
  echo "Start OK";
  exit();
}

// load is an ajax call via onload.

if($_POST['page'] == 'load') {
  $id = $_POST['id'];
  
  if(!$id) {
    error_log("tracker: $S->siteName: LOAD NO ID, $ip, $agent");
    exit();
  }

  $S->query("update $S->masterdb.tracker set isJavaScript=isJavaScript|2, lasttime=now() where id='$id'");
  echo "Load OK";
  exit();
}

// Page hide is an ajax call

if($_POST['page'] == 'pagehide') {
  $id = $_POST['id'];

  if(!$id) {
    error_log("tracker: $S->siteName: PAGEHIDE NO ID, $ip, $agent");
    exit();
  }

  $S->query("select isJavaScript from $S->masterdb.tracker where id=$id");
  
  list($js) = $S->fetchrow('num');

  // 4127 is 0x101F or 0x1000 timer, 0x10 noscript, 0xf start|load|script|normal
  // So if js is zero after the &~ then we do not have a (32|64|128) beacon,
  // or (256|512|1024) tracker:beforeunload/unload/pagehide. We should update.
  
  if(($js & ~(4127)) == 0) {
    //error_log("tracker: beforeunload,   $S->siteName, $id, $ip, $agent, $js");
    $S->query("update $S->masterdb.tracker set endtime=now(), difftime=timediff(now(),starttime), ".
              "isJavaScript=isJavaScript|1024, lasttime=now() where id=$id");
  }
  echo "pagehide OK";
  exit();
}

// before unload is an ajax call 

if($_POST['page'] == 'beforeunload') {
  $id = $_POST['id'];

  if(!$id) {
    error_log("tracker: $S->siteName: BEFOREUNLOAD NO ID, $ip, $agent");
    exit();
  }

  $S->query("select isJavaScript from $S->masterdb.tracker where id=$id");
  
  list($js) = $S->fetchrow('num');

  // 4127 is 0x101F or 0x1000 timer, 0x10 noscript, 0xf start|load|script|normal
  // So if js is zero after the &~ then we do not have a (32|64|128) beacon,
  // or (256|512) tracker:beforeunload/unload. We should update.
  
  if(($js & ~(4127)) == 0) {
    //error_log("tracker: beforeunload,   $S->siteName, $id, $ip, $agent, $js");
    $S->query("update $S->masterdb.tracker set endtime=now(), difftime=timediff(now(),starttime), ".
              "isJavaScript=isJavaScript|256, lasttime=now() where id=$id");
  }
  echo "beforeunload OK";
  exit();
}

// unload is an ajax call via onunload

if($_POST['page'] == 'unload') {
  $id = $_POST['id'];

  if(!$id) {
    error_log("tracker: $S->siteName: UNLOAD NO ID, $ip, $agent");
    exit();
  }

  $S->query("select isJavaScript from $S->masterdb.tracker where id=$id");
  
  list($js) = $S->fetchrow('num');

  // 4127 is 0x101F: 0x1000 timer, 0x10 noscript, 0xf start|load|script|normal
  // So if js is zero after the &~ then we do not have a (32|64|128) beacon,
  // or (256|512) tracker:beforeunload/unload. We should update.
  
  if(($js & ~(4127)) == 0) {
    //error_log("tracker: unload,   $S->siteName, $id, $ip, $agent, $js");
    $S->query("update $S->masterdb.tracker set endtime=now(), difftime=timediff(now(),starttime), ".
              "isJavaScript=isJavaScript|512, lasttime=now() where id=$id");
  }
  echo "Unload OK";
  exit();
}

// Via the <img> in the header section set via the head.i.php

if($_GET['page'] == 'script') {
  $id = $_GET['id'];

  if(!$id) {
    error_log("tracker: $S->siteName: SCRIPT NO ID, $ip, $agent");
    exit();
  }

  //error_log("tracker: script, $S->siteName, $id, $ip, $agent");

  try {
    $sql = "select page, agent from $S->masterdb.tracker where id=$id";
    $n = $S->query($sql);

    list($page, $orgagent) = $S->fetchrow('num');

    if($agent != $orgagent) {
      $sql = "insert into $S->masterdb.tracker (site, ip, page, agent, starttime, refid, isJavaScript, lasttime) ".
             "values('$S->siteName', '$ip', '$page', '$agent', now(), '$id', 0x2004, now())";

      $S->query($sql);
    }
  
    $sql = "update $S->masterdb.tracker set isJavaScript=isJavaScript|4, lasttime=now() where id=$id";
    $S->query($sql);
  } catch(Exception $e) {
    error_log(print_r($e, true));
  }
  $img1 = "http://bartonphillips.net/images/blank.png";

  if($S->trackerImg1) {
    $img1 = "http://bartonphillips.net" . $S->trackerImg1;
  }

  $imageType = preg_replace("~^.*\.(.*)$~", "$1", $img1);
  $img = file_get_contents("$img1");
  header("Content-type: image/$imageType");
  echo $img;
  exit();
}

if($_GET['page'] == 'normal') {
  $id = $_GET['id'];
  
  if(!$id) {
    error_log("tracker: $S->siteName: NORMAL NO ID, $ip, $agent");
    exit();
  }

  //error_log("tracker: normal, $S->siteName, $id, $ip, $agent");

  try {
    $sql = "select page, agent from $S->masterdb.tracker where id=$id";
    $S->query($sql);
    list($page, $orgagent) = $S->fetchrow('num');
    if($agent != $orgagent) {
      $sql = "insert into $S->masterdb.tracker (site, ip, page, agent, starttime, refid, isJavaScript, lasttime) ".
             "values('$S->siteName', '$ip', '$page', '$agent', now(), '$id', 0x2008, now())";

      $S->query($sql);
    }

    $sql = "update $S->masterdb.tracker set isJavaScript=isJavaScript|8, lasttime=now() where id=$id";
    $S->query($sql);
  } catch(Exception $e) {
    error_log(print_r($e, true));
  }
  $img2 = "http://bartonphillips.net/images/blank.png";

  if($S->trackerImg2) {
    $img2 = "http://bartonphillips.net" . $S->trackerImg2;
  }

  $imageType = preg_replace("~.*\.(.*)$~", "$1", $img2);
  $img = file_get_contents("$img2");
  header("Content-type: image/$imageType");
  echo $img;
  exit();
}

// Via the <img> in the 'noscript' tag in the banner.i.php

if($_GET['page'] == 'noscript') {
  $id = $_GET['id'];

  if(!$id) {
    error_log("tracker: $S->siteName: NOSCRIPT NO ID, $ip, $agent");
    exit();
  }

  //error_log("tracker: noscript, $S->siteName, $id, $ip, $agent");

  try {
    $sql = "select page, agent from $S->masterdb.tracker where id=$id";
    $S->query($sql);
    list($page, $orgagent) = $S->fetchrow('num');
    if($agent != $orgagent) {
      $sql = "insert into $S->masterdb.tracker (site, ip, page, agent, starttime, refid, isJavaScript, lasttime) ".
             "values('$S->siteName', '$ip', '$page', '$agent', now(), '$id', 0x2010, now())";

      $S->query($sql);
    }

    $sql = "update $S->masterdb.tracker set isJavaScript=isJavaScript|0x10, lasttime=now() where id=$id";
    $S->query($sql);
  } catch(Exception $e) {
    error_log(print_r($e, true));
  }
  $img = file_get_contents("http://bartonphillips.net/images/blank.png");
  header("Content-type: image/png");
  echo $img;
  exit();
}

if($_POST['page'] == 'timer') {  
  $id = $_POST['id'];

  if(!$id) {
    error_log("tracker: $S->siteName: TIMER NO ID, $ip, $agent");
    exit();
  }

  try {
    $sql = "update $S->masterdb.tracker set isJavaScript=isJavaScript|4096, endtime=now(), ".
           "difftime=timediff(now(),starttime), lasttime=now() where id=$id";
    
    $S->query($sql);
  } catch(Exception $e) {
    error_log(print_r($e, true));
  }
  echo "Timer OK";
  exit();
}

// otherwise just go away!

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
