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

return json_decode(stripComments(file_get_contents("https://bartonphillips.org/mysitemap.json")));


