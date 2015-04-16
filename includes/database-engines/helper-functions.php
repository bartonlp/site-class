<?php
// This is included so it has the .sitemap.php information from the file that included it.

/**
 * Helper Functions
 * These may well be defined by a chile class.
 */

if(!function_exists('vardump')) {  
  function vardump($msg=null) {
    $args = func_get_args();
    if(is_string($msg)) {
      array_shift($args); // remove msg from the args array
      $msg= "<b>$msg</b>\n";
    } else unset($msg);
  
    echo "<pre>$msg";var_dump($args);echo "</pre>";
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

if(!function_exists('vardump_e')) {
  function vardump_e($value, $msg=null) {
    if($msg) $msg = "<b>$msg</b>\n";
    echo "<pre>$msg" . (escapeltgt(print_r($value, true))) . "</pre>\n\n";
  }
}

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

// Change < and > into "&lt;" and "&gt;" entities

if(!function_exists('escapeltgt')) {
  function escapeltgt($value) {
    $value = preg_replace(array("/</", "/>/"), array("&lt;", "&gt;"), $value);  
    return $value;
  }
}

// varprint makes value readable via print_r

if(!function_exists('varprint')) {
  function varprint($value, $msg=null) {
    if($msg) $msg = "<b>$msg</b>\n";
    echo "<pre>$msg" . (escapeltgt(print_r($value, true))) . "</pre>\n\n";
  }
}

if(!function_exists('arrayToObjectDeep')) {
  function arrayToObjectDeep($array) {
    if(!is_array($array)) {
      return $array;
    }

    $object = new stdClass();

    if(is_array($array) && count($array) > 0) {
      foreach($array as $name=>$value) {
        $name = strtolower(trim($name));
        if(!empty($name)) {
          $object->$name = arrayToObject($value);
        }
      }
      return $object;
    } else {
      return FALSE;
    }
  }
}
