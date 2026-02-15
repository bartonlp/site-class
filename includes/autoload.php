<?php
// NOT WORKING!!!
// Auto load classes for SiteClass

namespace bartonlp\autoload;

// Standard mask for all versions
$mask = E_ALL & ~E_DEPRECATED & ~E_WARNING & ~E_NOTICE;

// E_STRICT was officially deprecated in 8.4.0
// Only use it if we are on a version LOWER than 8.4.0
if (PHP_VERSION_ID < 80400) {
    $mask &= ~E_STRICT;
}

error_reporting($mask);

define("SITELOAD_VERSION", "1.1.4autoload-pdo"); // BLP 2024-10-30 - modify, add findmysitemap().
define("SITECLASS_DIR", __DIR__);

function getSiteloadVersion() {
  return SITELOAD_VERSION;
}

function _callback($class) {
  switch($class) {
    case "SiteClass":
      echo "autoload: ".__DIR__."/$class.class.php<br>";
      require(__DIR__."/$class.class.php");
      break;
    default:
      echo "autoload: ".__DIR__."/database-engines/$class.class.php<br>";
      require(__DIR__."/database-engines/$class.class.php");
      break;
  }
}

if(spl_autoload_register("\bartonlp\autoload\_callback") === false) exit("Can't Autoload");

require(__DIR__."/database-engines/helper-functions.php");

\ErrorClass::setDevelopment(true);

\SiteExceptionHandler::init(); // Initialize the exception handler.

date_default_timezone_set('America/New_York'); // Done here and in dbPdo.class.php constructor.

// BLP 2024-01-31 -  If this is /var/www/html just return and get the info from mysitemap.json.

//if($_SERVER['HTTP_HOST'] == "195.252.232.86") return; 

//vardump("server", $_SERVER);

$mydir = dirname($_SERVER['SCRIPT_FILENAME']);
//echo "script_filename: $mydir<br>";
echo "here<br>";
if($__VERSION_ONLY) {
  return SITELOAD_VERSION;
} else {
  return findsitemap($mydir);
}

// Find the mysitemap.json. $mydir is a global. This is borrowed from siteload.php with
// modification.

function findsitemap($mydir) {
  if(file_exists($mydir . "/mysitemap.json")) {
    // BLP 2023-08-17 - use the stripComments() from the helperfunctions.php

    $ret = json_decode(stripComments(file_get_contents($mydir . "/mysitemap.json")));
    $ret->mysitemap = "$mydir/mysitemap.json";
    return $ret;
    //return json_decode(stripComments(file_get_contents($mydir . "/mysitemap.json")));
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
