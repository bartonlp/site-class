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

$_site = json_decode(findsitemap());

if(!$_site) {
  echo <<<EOF
<h1>NO 'mysitemap.json' Found</h1>
<p>To run {$_SERVER['PHP_SELF']} you must have a 'mysitemap.json' somewhere within the Document Root.</p>
EOF;
  error_log("ERROR: siteload.php. No 'mysitemap.json' found in " . getcwd() . " for file {$_SERVER['PHP_SELF']}");
exit();
}
return $_site;

function findsitemap() {
  $docroot = $_SERVER['DOCUMENT_ROOT'];

  if(file_exists("mysitemap.json")) {
    return file_get_contents("mysitemap.json");
  } else {
    if($docroot == getcwd() || '/' == getcwd()) {
      return null;
    }
    echo "dir: ".getcwd()."\n";
    chdir('..');
    // Recurse
    return findsitemap();
  }
}
