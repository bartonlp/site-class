<?php
// Auto load classes

function callback($class) {
  switch($class) {
    case "SiteClass":
      require("$class.class.php");
      break;
    default:
      require("database-engines/$class.class.php");
      break;
  }
}

if(spl_autoload_register("callback") === false) exit("Can't Autoload");

require("database-engines/helper-functions.php");

ErrorClass::setDevelopment(true);

date_default_timezone_set('America/New_York'); // Done here and in dbPdo.class.php constructor.

return json_decode(stripComments(file_get_contents("https://bartonphillips.org/mysitemap.json")));


