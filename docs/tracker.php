<?php
// BLP 2014-03-06 -- ajax for tracker.js

$_site = require_once(getenv("SITELOAD")."/siteload.php");

$dbinfo = $_site->dbinfo;

$S = new Database($dbinfo);

$ip = $_SERVER['REMOTE_ADDR'];
$agent = $_SERVER['HTTP_USER_AGENT'];

// Post an ajax error message

if($_POST['page'] == 'ajaxmsg') {
  $msg = $_POST['msg'];
  // NOTE: $_POST['ipagent'] is a string not a boolian! So === true does NOT work but == true
  // or == 'true' does work.
  $ipagent = ($_POST['ipagent'] == 'true') ? ": $ip, $agent" : '';
  error_log("tracker: AJAXMSG, $_site->siteName, '$msg'" . $ipagent);
  echo "AJAXMSG OK";
  exit();
}

// start is an ajax call

if($_POST['page'] == 'start') {
  $id = $_POST['id'];
  
  if(!$id) {
    error_log("tracker: $_site->siteName: START NO ID, $ip, $agent");
    exit();
  }

  //error_log("tracker: start,    $_site->siteName, $id, $ip, $agent");
  
  $S->query("update $_site->masterdb.tracker set isJavaScript=isJavaScript|1, lasttime=now() where id='$id'");
  echo "Start OK";
  exit();
}

// load is an ajax call via onload.

if($_POST['page'] == 'load') {
  $id = $_POST['id'];
  
  if(!$id) {
    error_log("tracker: $_site->siteName: LOAD NO ID, $ip, $agent");
    exit();
  }

  //error_log("tracker: load, $_site->siteName, $id, $ip, $agent");
  
  $S->query("update $_site->masterdb.tracker set isJavaScript=isJavaScript|2, lasttime=now() where id='$id'");
  echo "Load OK";
  exit();
}

// Page hide is an ajax call

if($_POST['page'] == 'pagehide') {
  $id = $_POST['id'];

  if(!$id) {
    error_log("tracker: $_site->siteName: PAGEHIDE NO ID, $ip, $agent");
    exit();
  }

  $S->query("select isJavaScript from $_site->masterdb.tracker where id=$id");
  
  list($js) = $S->fetchrow('num');

  // 4127 is 0x101F or 0x1000 timer, 0x10 noscript, 0xf start|load|script|normal
  // So if js is zero after the &~ then we do not have a (32|64|128) beacon,
  // or (256|512|1024) tracker:beforeunload/unload/pagehide. We should update.
  
  if(($js & ~(4127)) == 0) {
    //error_log("tracker: beforeunload, $_site->siteName, $id, $ip, $agent, $js");
    $S->query("update $_site->masterdb.tracker set endtime=now(), difftime=timediff(now(),starttime), ".
              "isJavaScript=isJavaScript|1024, lasttime=now() where id=$id");
  }
  echo "pagehide OK";
  exit();
}

// before unload is an ajax call 

if($_POST['page'] == 'beforeunload') {
  $id = $_POST['id'];

  if(!$id) {
    error_log("tracker: $_site->siteName: BEFOREUNLOAD NO ID, $ip, $agent");
    exit();
  }

  $S->query("select isJavaScript from $_site->masterdb.tracker where id=$id");
  
  list($js) = $S->fetchrow('num');

  // 4127 is 0x101F or 0x1000 timer, 0x10 noscript, 0xf start|load|script|normal
  // So if js is zero after the &~ then we do not have a (32|64|128) beacon,
  // or (256|512) tracker:beforeunload/unload. We should update.
  
  if(($js & ~(4127)) == 0) {
    //error_log("tracker: beforeunload, $_site->siteName, $id, $ip, $agent, $js");
    $S->query("update $_site->masterdb.tracker set endtime=now(), difftime=timediff(now(),starttime), ".
              "isJavaScript=isJavaScript|256, lasttime=now() where id=$id");
  }
  echo "beforeunload OK";
  exit();
}

// unload is an ajax call via onunload

if($_POST['page'] == 'unload') {
  $id = $_POST['id'];

  if(!$id) {
    error_log("tracker: $_site->siteName: UNLOAD NO ID, $ip, $agent");
    exit();
  }

  $S->query("select isJavaScript from $_site->masterdb.tracker where id=$id");
  
  list($js) = $S->fetchrow('num');

  // 4127 is 0x101F: 0x1000 timer, 0x10 noscript, 0xf start|load|script|normal
  // So if js is zero after the &~ then we do not have a (32|64|128) beacon,
  // or (256|512) tracker:beforeunload/unload. We should update.
  
  if(($js & ~(4127)) == 0) {
    $S->query("update $_site->masterdb.tracker set endtime=now(), difftime=timediff(now(),starttime), ".
              "isJavaScript=isJavaScript|512, lasttime=now() where id=$id");
  }
  echo "Unload OK";
  exit();
}

// Via the <img> in the header section set via the head.i.php

if($_GET['page'] == 'script') {
  $id = $_GET['id'];

  if(!$id) {
    error_log("tracker: $_site->siteName: SCRIPT NO ID, $ip, $agent");
    exit();
  }

  //error_log("tracker: script, $_site->siteName, $id, $ip, $agent");

  try {
    $sql = "select page, agent from $_site->masterdb.tracker where id=$id";
    $n = $S->query($sql);

    list($page, $orgagent) = $S->fetchrow('num');

    if($agent != $orgagent) {
      $sql = "insert into $_site->masterdb.tracker (site, ip, page, agent, starttime, refid, isJavaScript, lasttime) ".
             "values('$_site->siteName', '$ip', '$page', '$agent', now(), '$id', 0x2004, now())";

      $S->query($sql);
    }
  
    $sql = "update $_site->masterdb.tracker set isJavaScript=isJavaScript|4, lasttime=now() where id=$id";
    $S->query($sql);
  } catch(Exception $e) {
    error_log(print_r($e, true));
  }
  $img1 = "http://bartonphillips.net/images/blank.png";

  if($_site->trackerImg1) {
    $img1 = "http://bartonphillips.net" . $_site->trackerImg1;
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
    error_log("tracker: $_site->siteName: NORMAL NO ID, $ip, $agent");
    exit();
  }

  //error_log("tracker: normal, $_site->siteName, $id, $ip, $agent");

  try {
    $sql = "select page, agent from $_site->masterdb.tracker where id=$id";
    $S->query($sql);
    list($page, $orgagent) = $S->fetchrow('num');
    if($agent != $orgagent) {
      $sql = "insert into $_site->masterdb.tracker (site, ip, page, agent, starttime, refid, isJavaScript, lasttime) ".
             "values('$_site->siteName', '$ip', '$page', '$agent', now(), '$id', 0x2008, now())";

      $S->query($sql);
    }

    $sql = "update $_site->masterdb.tracker set isJavaScript=isJavaScript|8, lasttime=now() where id=$id";
    $S->query($sql);
  } catch(Exception $e) {
    error_log(print_r($e, true));
  }
  $img2 = "http://bartonphillips.net/images/blank.png";

  if($_site->trackerImg2) {
    $img2 = "http://bartonphillips.net" . $_site->trackerImg2;
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
    error_log("tracker: $_site->siteName: NOSCRIPT NO ID, $ip, $agent");
    exit();
  }

  //error_log("tracker: noscript, $_site->siteName, $id, $ip, $agent");

  try {
    $sql = "select page, agent from $_site->masterdb.tracker where id=$id";
    $S->query($sql);
    list($page, $orgagent) = $S->fetchrow('num');
    if($agent != $orgagent) {
      $sql = "insert into $_site->masterdb.tracker (site, ip, page, agent, starttime, refid, isJavaScript, lasttime) ".
             "values('$_site->siteName', '$ip', '$page', '$agent', now(), '$id', 0x2010, now())";

      $S->query($sql);
    }

    $sql = "update $_site->masterdb.tracker set isJavaScript=isJavaScript|0x10, lasttime=now() where id=$id";
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
    error_log("tracker: $_site->siteName: TIMER NO ID, $ip, $agent");
    exit();
  }

  try {
    $sql = "update $_site->masterdb.tracker set isJavaScript=isJavaScript|4096, endtime=now(), ".
           "difftime=timediff(now(),starttime), lasttime=now() where id=$id";
    
    $S->query($sql);
  } catch(Exception $e) {
    error_log(print_r($e, true));
  }
  echo "Timer OK";
  exit();
}

// Capture the fingerprint

if($_POST['page'] == 'fingerprint') {  
  $finger = $_POST['finger'];
  $page = $_POST['pagename'];

  if(!$finger) {
    error_log("tracker: $_site->siteName: TIMER NO finger, $ip, $agent");
    exit();
  }

  //error_log("tracker: finger $_site->siteName, $finger, $page, $ip, $agent");
  
  try {
    $sql = "insert into $_site->masterdb.finger (ip, finger, page, agent, count, created, lasttime) ".
           "values('$ip', '$finger', '$page', '$agent', 1, now(), now()) ".
           "on duplicate key update count=count+1, lasttime=now()";
    
    $S->query($sql);
  } catch(Exception $e) {
    error_log(print_r($e, true));
  }
  echo "Finger OK";
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
