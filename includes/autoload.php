<?php
// Auto load classes for SiteClass

namespace bartonlp\siteload;

error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT & ~E_WARNING & ~E_NOTICE);

define("SITELOAD_VERSION", "1.1.4autoload-pdo"); // BLP 2024-10-30 - modify, add findmysitemap().
define("SITECLASS_DIR", __DIR__);

function getSiteloadVersion() {
  return SITELOAD_VERSION;
}

function _callback($class) {
  switch($class) {
    case "SiteClass":
      require(__DIR__."/$class.class.php");
      break;
    default:
      require(__DIR__."/database-engines/$class.class.php");
      break;
  }
}

if(spl_autoload_register("\bartonlp\siteload\_callback") === false) exit("Can't Autoload");

require(__DIR__."/database-engines/helper-functions.php");

\ErrorClass::setDevelopment(true);

date_default_timezone_set('America/New_York'); // Done here and in dbPdo.class.php constructor.

// BLP 2024-01-31 -  If this is /var/www/html just return and get the info from mysitemap.json.

if($_SERVER['HTTP_HOST'] == "195.252.232.86") return; 

//vardump("server", $_SERVER);

$mydir = dirname($_SERVER['SCRIPT_FILENAME']);
//echo "script_filename: $mydir<br>";

if($__VERSION_ONLY) {
  return SITELOAD_VERSION;
} else {
  if($_SERVER['HTTP_HOST'] == "bartonphillips.org") {
    if(file_exists("../bartonphillips.org:8000")) $port = ":8000";
    return json_decode(stripComments(file_get_contents("https://bartonphillips.org$port/mysitemap.json")));
  } else {
    return findsitemap(); // BLP 2024-10-30 - use ne findsitmap() function borowed from siteload.php with modifications
  }
}

// Find the mysitemap.json. $mydir is a global. This is borrowed from siteload.php with
// modification.

function findsitemap() {
  global $mydir;

  if(file_exists($mydir . "/mysitemap.json")) {
    // BLP 2023-08-17 - use the stripComments() from the helperfunctions.php

    return json_decode(stripComments(file_get_contents($mydir . "/mysitemap.json")));
  } else {
    // If we didn't find the mysitemap.json then have we reached to docroot? Or have we reached the
    // root. We should actually never reach the root.

    if(($_SERVER['DOCUMENT_ROOT'] ?? $S_SERVER['VIRTUALHOST_DOCUMENT_ROOT']) == $mydir || '/' == $mydir) {
      echo <<<EOF
<h1>NO 'mysitemap.json' Found</h1>
<p>To run {$_SERVER['PHP_SELF']} you must have a 'mysitemap.json' somewhere within the Document Root.</p>
EOF;
      error_log("ERROR: siteload.php. No 'mysitemap.json' found in " . getcwd() . " for file {$_SERVER['PHP_SELF']}. DocRoot: $docroot");
      exit();
    }

    // We are not at the root so do $mydir = dirname($mydir). For example if $mydir is

    $mydir = dirname($mydir);
    //echo "mydir: $mydir<br>";

    // Recurse

    return findsitemap();
  }
}
