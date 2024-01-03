<?php
// Auto load classes

if(!function_exists("_callback")) { // In case we call autoload twice.
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
}

if(spl_autoload_register("_callback") === false) exit("Can't Autoload");

require(__DIR__."/database-engines/helper-functions.php");

ErrorClass::setDevelopment(true);

date_default_timezone_set('America/New_York'); // Done here and in dbPdo.class.php constructor.

define("SITELOAD_VERSION", "1.1.1autoload-pdo"); // BLP 2023-08-11 - add static $mysitemap
define("SITECLASS_DIR", __DIR__);

if($__VERSION_ONLY) {
  return SITELOAD_VERSION;
} else {
  return json_decode(stripComments(file_get_contents(__DIR__ . "/mysitemap.json")));
}

