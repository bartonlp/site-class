<?php
// This is the site loader for Composer based sites using mysitemap.json
// We first get the vendor/autoload.php
// and then we get the DOCUMENT_ROOT and PHP_SELF
// we combine DOCUMENT_ROOT and the dirname(PHP_SELF) and '/mysitemap.json'
// and return the mysitemap.json file for the site.

require_once(getenv("HOME") . "/vendor/autoload.php");
$docroot = $_SERVER['DOCUMENT_ROOT'];
$dir = dirname($_SERVER['PHP_SELF']);
return json_decode(file_get_contents($docroot . $dir ."/mysitemap.json"));
