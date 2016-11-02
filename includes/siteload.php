<?php
// This is the site loader for Composer based sites using mysitemap.json
// We first get the vendor/autoload.php
// and then we get the DOCUMENT_ROOT and PHP_SELF
// we combine DOCUMENT_ROOT and the dirname(PHP_SELF) and '/mysitemap.json'
// and return the mysitemap.json file for the site.

// We are in 'vendor/bartonlp/site-class/includes' so we want to go back three directories to load
// autoload.php

$dir = __DIR__;
require_once("$dir/../../../autoload.php");
$docroot = $_SERVER['DOCUMENT_ROOT'];
$self = $_SERVER['PHP_SELF'];

// Check that there really is a file and this isn't a "friendly" url

if(file_exists($docroot . $self)) {
  $dir = dirname($self);
}

return json_decode(file_get_contents($docroot . $dir ."/mysitemap.json"));