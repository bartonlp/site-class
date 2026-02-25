<?php
// This file needs /home/barton/site-class/includes/
// It must be with the same file as we use.

namespace bartonlp\SiteClass;

$mydir = dirname($_SERVER['SCRIPT_FILENAME']);

// Standard mask for all versions
$mask = E_ALL & ~E_DEPRECATED & ~E_WARNING & ~E_NOTICE;

// E_STRICT was officially deprecated in 8.4.0
// Only use it if we are on a version LOWER than 8.4.0
if (PHP_VERSION_ID < 80400) {
    $mask &= ~E_STRICT;
}

error_reporting($mask);
define("SITELOAD_VERSION", "1.0.0autoload-pdo");
define("SITECLASS_DIR", __DIR__);
//$myfile = basename(__FILE__);

//if($myfile === 'autoload.php') {
if($mysiteload !== true) {
  // Do autoload
  function _callback($class) {
    $class = ltrim($class, '\\');
    $parts = explode('\\', $class);
    $className = end($parts);
    //error_log("dir: ".__DIR__);
    $base = SITECLASS_DIR . "/"; 

    $paths = [
              $base . $className . ".class.php",
              $base . "database-engines/" . $className . ".class.php",
              $base . "traits/" . $className . ".php",
             ];

    foreach($paths as $file) {
      //echo "file: $file<br>";
      if(file_exists($file)) {
        require $file;
        return;
      }
    }
    echo "Not Done. paths=" . print_r($paths, true) ."<br>";
  }

  if(spl_autoload_register('bartonlp\SiteClass\_callback') === false) exit("Can't Autoload");
}

require(SITECLASS_DIR ."/database-engines/helper-functions.php");

// Here are used 'class_alias' to make our site look like no having \bartonlp\SiteClass\...

class_alias('\bartonlp\SiteClass\SiteClass', 'SiteClass');
class_alias('\bartonlp\SiteClass\Database', 'Database');
class_alias('\bartonlp\SiteClass\dbPdo', 'dbPdo');

// Now add ob_start. It is removed in SiteExceptionHandler.

ob_start();
SiteExceptionHandler::init();

//if(!class_exists("getinfo")) {
class getinfo {
  private $docroot;
  private $mydir;
  private $_site;

  public function __construct() {
    // Now check to see if we have a DOCUMENT_ROOT or VIRTUALHOST_DOCUMENT_ROOT.
    // If we DON't we will use PWD which should be and if SCRIPT_FILENAME is not dot (.)
    // then we add it to PWD.
    // This is for CLI files. For regular PHP via apache we just use the ROOT.

    if(!$_SERVER['DOCUMENT_ROOT'] && !$_SERVER['VIRTUALHOST_DOCUMENT_ROOT']) {
      // This is a CLI program
      // Is SCRIPT_FILENAME an absolute path?

      if(strpos($_SERVER['SCRIPT_FILENAME'], "/") === 0) {
        // First character is a / so absoulte path
        $mydir = dirname($_SERVER['SCRIPT_FILENAME']);
      } else {
        // SCRIPT_FILENAME is NOT an absolute path
        // Use PWD and then look at SCRIPT_FILENAME
        $mydir = $_SERVER['PWD'];
        // If SCRIPT_FILENAME start with a dot (.) then we are in the target dir so do nothing.
        // Else we use the dirname() and append it to mydir.

        if(($x = dirname($_SERVER['SCRIPT_FILENAME'])) != '.') {
          $mydir .= "/$x";
        }
      }
    } else {
      // Normal apache program
      // The SCRIPT_FILENAME is always an absolute path
      $mydir = dirname($_SERVER['SCRIPT_FILENAME']);
      $this->docroot = $_SERVER['DOCUMENT_ROOT'] ?? $S_SERVER['VIRTUALHOST_DOCUMENT_ROOT'];
    }
    $this->mydir = $mydir;

    $this->_site = json_decode($this->findsitemap());

    $this->_site->mysitemap = "$this->mydir/mysitemap.json";

    // Set the siteloadVersion and siteClassDir

    $this->_site->siteloadVersion = SITELOAD_VERSION;
    $this->_site->siteClassDir = SITECLASS_DIR;
  }

  public static function getVersion() {
    return SITELOAD_VERSION;
  }

  private function findsitemap() {
    $mydir = $this->mydir;

    if(file_exists($mydir . "/mysitemap.json")) {
      // BLP 2023-08-17 - use the stripComments() from the helperfunctions.php

      return stripComments(file_get_contents($mydir . "/mysitemap.json"));
    } else {
      // If we didn't find the mysitemap.json then have we reached to docroot? Or have we reached the
      // root. We should actually never reach the root.

      if($this->docroot == $mydir || '/' == $mydir) {
        echo <<<EOF
<h1>NO 'mysitemap.json' Found</h1>
<p>To run {$_SERVER['PHP_SELF']} you must have a 'mysitemap.json' somewhere within the Document Root.</p>
EOF;
        error_log("ERROR: siteload.php. No 'mysitemap.json' found in " . getcwd() . " for file {$_SERVER['PHP_SELF']}. DocRoot: $docroot");
        exit();
      }

      // We are not at the root so do $mydir = dirname($mydir). For example if $mydir is

      $this->mydir = dirname($mydir);

      // Recurse

      return $this->findsitemap();
    }
  }

  // This is the getter for the private $this->_site;

  public function getSite() {
    return $this->_site;
  }
}
//}

$_site = (new \bartonlp\SiteClass\getinfo())->getSite();

// If $_site is NULL that means the json_decode() failed.

if(is_null($_site)) {
  echo <<<EOF
<h1>JSON ENCODING ERROR</h1>
<p>Check mysitemap.json for an ilegal construct!</p>
EOF;

  // BLP 2022-01-12 -- try to make error log message more helpful
  
  if(is_string($ret)) {
    error_log("ERROR: siteload.php. Return form findsitemap() is a string. However json_decode() returned NULL. JSON ENCODING ERROR");
  } else {
    error_log("ERROR: siteload.php. Return from findsitemap() is NOT a string. SOMETHING WENT WRONG SOMEWHERE");
  }
  exit();
}
return $_site;
