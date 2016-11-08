<?php
// This is the site loader for Composer based sites using mysitemap.json
// We first get the vendor/autoload.php
// and then we get the DOCUMENT_ROOT and PHP_SELF
// we combine DOCUMENT_ROOT and the dirname(PHP_SELF) and '/mysitemap.json'
// and return the mysitemap.json file for the site.

// We are in 'vendor/bartonlp/site-class/includes' so we want to go back three directories to load
// autoload.php

require_once(__DIR__ ."/../../../autoload.php");
return json_decode(findsitemap());

function findsitemap() {
  $docroot = $_SERVER['DOCUMENT_ROOT'];

  if(file_exists("mysitemap.json")) {
    return file_get_contents("mysitemap.json");
  } else {
    if($docroot == getcwd()) {
      return null;
    }
    chdir('..');
    // Recurse
    return findsitemap();
  }
}
