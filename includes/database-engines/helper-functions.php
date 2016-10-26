<?php
// This is included so it has the .sitemap.php information from the file that included it.

/**
 * Helper Functions
 * These my well be defined by a chile class.
 */
// vardump makes value readable

if(!function_exists('vardump')) {
  function vardump() {
    $args = func_get_args();

    if(count($args) > 1 && is_string($args[0])) {
      $msg = array_shift($args);
      $msg = "<b>$msg:</b>\n";
    }
    for($i=0; $i < count($args); ++$i) {
      $v .= escapeltgt(print_r($args[$i], true));
    }
    echo "<pre class='vardump' style='font-size: .7rem; font-family: \"Monospace\"'>$msg$v</pre>";
  }
} 

if(!function_exists('put')) {
  function put($text) {
    echo "<pre>$text</pre>\n";
  }
}

if(!function_exists('put_e')) {
  function put_e($msg) {
    $msg = escapeltgt($msg);
    echo "<pre>$msg</pre>";
  }
}

/*
if(!function_exists('vardump_e')) {
  function vardump_e($value, $msg=null) {
    if($msg) $msg = "<b>$msg</b>\n";
    echo "<pre>$msg" . (escapeltgt(print_r($value, true))) . "</pre>\n\n";
  }
}
*/

/**
 * stripSlashesDeep
 * recursively do stripslahes() on an array or string.
 * Only define if not already defined.
 * @param array|string $value either a string or an array of strings/arrays ...
 * @return original $value stripped clean of slashes.
 */

if(!function_exists('stripSlashesDeep')) {
  function stripSlashesDeep($value) {
    $value = is_array($value) ? array_map('stripSlashesDeep', $value) : stripslashes($value); 
    return $value;
  }
}

// like above but for mysql_real_escape_string()

if(!function_exists('mysqlEscapeDeep')) {
  function mysqlEscapeDeep($db, $value) {
    if(is_array($value)) {
      foreach($value as $k=>$v) {
        $val[$k] = mysqlEscapeDeep($db, $v);
      }
      return $val;
    } else {
      return $db->real_escape_string($value);
    }
  }
}

// This does a deep conversion from an object to an array

if(!function_exists('objectToArrayDeep')) {
  function objectToArrayDeep($obj) {
    if(is_object($obj)) {
      $obj = (array) $obj;
    }
    if(is_array($obj)) {
      $new = array();
      foreach($obj as $key => $val) {
        $new[$key] = objectToArrayDeep($val);
      }
    } else {
      $new = $obj;
    }
    return $new;       
  }
}

// This does a deep conversion from an array to an object

if(!function_exists('arrayToObjectDeep')) {
  function arrayToObjectDeep($array) {
    if(is_array($array)) {
      $array = (object) $array;
    }

    if(is_object($array)) {
      $new = new StdClass;
      foreach($array as $key=>$val) {
        $new->$key = arrayToObjectDeep($val);
      }
    } else {
      $new = $array;
    }
    return $new;
  }
}

// Change < and > into "&lt;" and "&gt;" entities

if(!function_exists('escapeltgt')) {
  function escapeltgt($value) {
    $value = preg_replace(array("/</", "/>/"), array("&lt;", "&gt;"), $value);
    return $value;
  }
}

