<?php
// BLP 2022-01-23 -- add $h and $h equal stdClass. This is for php8.1
// BLP 2022-01-12 -- Reworked error messages so JSON errors are more visable. 
// BLP 2021-03-09 -- Removed via comment logic for $__info to support altorouter.php
// BLP 2021-02-20 -- Added function stripComments($x) to remove comments '/*', '#' and '//' from
// json file.
// BLP 2018-04-20 -- Add $__info so altorouter.php can change things.  
// This is the site loader for Composer based sites using mysitemap.json
// We first get the vendor/autoload.php
// and then we get the DOCUMENT_ROOT and PHP_SELF
// we combine DOCUMENT_ROOT and the dirname(PHP_SELF) and '/mysitemap.json'
// and return the mysitemap.json file for the site.

// Function to search for the mysitemap.json
// We pass the $mydir to the function. This is from 'SCRIPT_FILENAME' which has the absolute path
// to the target with the full DOCUMENT_ROOT plus the directory path from the docroot to the
// target.
// For example DOCUMENT_ROOT + /path/target
// So we take this and if we do not find the files at first we do a $mysite = dirname($mysite) and
// then do a chdir($mysite); This may not be DOCUMENT_ROOT + ... but may the REAL path. For Example
// if we did a symlink to DOCUMENT_ROOT + 'weewx/index.php' and the symlink were
// /extra/weewx then if we did a chdir('..') we would get to /extra which is wrong.
// What we want is /var/www/weewx to /var/wwww.

if(!function_exists("findsitemap")) {
  function findsitemap($mydir) {
    global $docroot;

    if(file_exists($mydir . "/mysitemap.json")) {
      return stripComments(file_get_contents($mydir . "/mysitemap.json"));
    } else {
      // If we didn't find the mysitemap.json then have we reached to docroot? Or have we reached the
      // root. We should actually never reach the root.

      if($docroot == $mydir || '/' == $mydir) {
        echo <<<EOF
<h1>NO 'mysitemap.json' Found</h1>
<p>To run {$_SERVER['PHP_SELF']} you must have a 'mysitemap.json' somewhere within the Document Root.</p>
EOF;
        error_log("ERROR: siteloadOld.php. No 'mysitemap.json' found in " . getcwd() . " for file {$_SERVER['PHP_SELF']}. DocRoot: $docroot");
        exit();
      }

      // We are not at the root so do $mydir = dirname($mydir). For example if $mydir is
      // '/var/www/weewx' it becomes '/var/www'
      //echo "mydir: $mydir\n";
      $mydir = dirname($mydir);
      //chdir($mydir); // This will change the dir to something under the docroot
      // Recurse
      return findsitemap($mydir);
    }
  }
}

// BLP 2021-02-20 -- Added this to remove any comments that may be in my 'mystiemap.json'

if(!function_exists("stripComments")) {
  function stripComments($x) {
    $pat = '~".*?"(*SKIP)(*FAIL)|(?://[^\n]*)|(?:#[^\n]*)|(?:/\*.*?\*/)~s';
    return preg_replace($pat, "", $x);
  }
}

// We are in 'vendor/bartonlp/site-class/includes' so we want to go back three directories to load
// autoload.php

require_once(__DIR__ ."/../../../autoload.php");

$old = error_reporting(E_ALL & ~(E_NOTICE | E_WARNING | E_STRICT));

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
  $docroot = $_SERVER['DOCUMENT_ROOT'] ?? $S_SERVER['VIRTUALHOST_DOCUMENT_ROOT'];
}

$ret = findsitemap($mydir);
$_site = json_decode($ret);

// BLP 2022-01-12 -- If $_site is NULL that means the json_decode() failed.

if(is_null($_site)) {
  echo <<<EOF
<h1>JSON ENCODING ERROR</h1>
<p>Check mysitemap.json for an ilegal construct!</p>
EOF;

  // BLP 2022-01-12 -- try to make error log message more helpful
  
  if(is_string($ret)) {
    error_log("ERROR: siteloadOld.php. Return form findsitemap() is a string. However json_decode() returned NULL. JSON ENCODING ERROR");
  } else {
    error_log("ERROR: siteloadOld.php. Return from findsitemap() is NOT a string. SOMETHING WENT WRONG SOMEWHERE");
  }
  exit();
}
error_reporting($old);

// BLP 2022-01-23 -- Add $h and $b as stdClass for php8.1

$h = new stdClass;
$b = new stdClass;

return $_site;

