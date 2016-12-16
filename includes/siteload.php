<?php
// This is the site loader for Composer based sites using mysitemap.json
// We first get the vendor/autoload.php
// and then we get the DOCUMENT_ROOT and PHP_SELF
// we combine DOCUMENT_ROOT and the dirname(PHP_SELF) and '/mysitemap.json'
// and return the mysitemap.json file for the site.

// We are in 'vendor/bartonlp/site-class/includes' so we want to go back three directories to load
// autoload.php

require_once(__DIR__ ."/../../../autoload.php");

$mydir = dirname($_SERVER['SCRIPT_FILENAME']);
chdir($mydir);

$_site = json_decode(findsitemap($mydir));

if(!$_site) {
  echo <<<EOF
<h1>NO 'mysitemap.json' Found</h1>
<p>To run {$_SERVER['PHP_SELF']} you must have a 'mysitemap.json' somewhere within the Document Root.</p>
EOF;
  error_log("ERROR: siteload.php. No 'mysitemap.json' found in " . getcwd() . " for file {$_SERVER['PHP_SELF']}");
exit();
}
return $_site;

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

function findsitemap($mydir) {
  if($_SERVER['VIRTUALHOST_DOCUMENT_ROOT']) {
    $docroot = $_SERVER['VIRTUALHOST_DOCUMENT_ROOT'];
  } else {
    $docroot = $_SERVER['DOCUMENT_ROOT'];
  }

  if(file_exists("mysitemap.json")) {
    return file_get_contents("mysitemap.json");
  } else {
    // If we didn't find the mysitemap.json then have we reached to docroot? Or have we reached the
    // root. We should actually never reach the root.
    
    if($docroot == $mydir || '/' == getcwd()) {
      return null;
    }
    // We are not at the root so do $mydir = dirname($mydir). For example if $mydir is
    // '/var/www/weewx' it becomes '/var/www'
    
    $mydir = dirname($mydir);
    chdir($mydir); // This will change the dir to something under the docroot
    // Recurse
    return findsitemap($mydir);
  }
}
