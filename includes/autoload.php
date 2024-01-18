<?php
// Auto load classes

error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT & ~E_WARNING & ~E_NOTICE);

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

define("SITELOAD_VERSION", "1.1.3autoload-pdo");
define("SITECLASS_DIR", __DIR__);

if($__VERSION_ONLY) {
  return SITELOAD_VERSION;
} else {
  if($_SERVER['HTTP_HOST'] == "bartonphillips.org") {
    if(file_exists("../bartonphillips.org:8000")) $port = ":8000";
    return json_decode(stripComments(file_get_contents("https://bartonphillips.org$port/mysitemap.json")));
  } else {
    return json_decode(stripComments(file_get_contents(__DIR__ . "/mysitemap.json")));
  }
}

