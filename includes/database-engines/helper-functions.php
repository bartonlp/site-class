<?php
/* HELPER FUNCTIONS. Well tested and maintained */

define("HELPER_FUNCTION_VERSION", "1.0.0helper");

/**
 * Helper Functions
 */

// Return the helper version

if(!function_exists("getVersion")) {
  function getVersion() {
    return HELPER_FUNCTION_VERSION;
  }
}

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
    echo "<pre class='vardump'>$msg$v</pre>";
  }
} 

// As above but does not escape the lt/gt or do any HTML.

if(!function_exists('vardumpNoEscape')) {
  function vardumpNoEscape() {
    $args = func_get_args();

    if(count($args) > 1 && is_string($args[0])) {
      $msg = array_shift($args);
      $msg = "$msg:\n";
    }
    for($i=0; $i < count($args); ++$i) {
      $v .= print_r($args[$i], true);
    }
    echo "$msg$v\n";
  }
}

// Put a line with escaping

if(!function_exists('put')) {
  function put($msg) {
    $msg = escapeltgt($msg);
    echo "<pre>$msg</pre>\n";
  }
}

// Put a line with no escaping

if(!function_exists('putNoEscape')) {
  function putNoEscape($msg) {
    echo "<pre>$msg</pre>";
  }
}

/**
 * stripSlashesDeep
 * recursively do stripslahes() on an array or string.
 * @param array|string $value either a string or an array of strings/arrays ...
 * @return original $value stripped clean of slashes.
 */

if(!function_exists('stripSlashesDeep')) {
  function stripSlashesDeep($value) {
    $value = is_array($value) ? array_map('stripSlashesDeep', $value) : stripslashes($value); 
    return $value;
  }
}

// There are methods for this in dbMysqli.class.php and dbSqlite.class.php
// Those methods should be used instead of this function!!!
/*
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
*/

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

// Callback to get the user id for SqlError

if(!function_exists('ErrorGetId')) {
  function ErrorGetId() {
    if($_COOKIE['SiteId']) {
      $email = explode(":", $_COOKIE['SiteId'])[1];
    }
    // do we have an id?
    if(empty($email)) {
      // NO email this is the generic version
      $id = "IP={$_SERVER['REMOTE_ADDR']} \nAGENT={$_SERVER['HTTP_USER_AGENT']}";
    } else {
      // This is for members
      $id = "CookieEmail=$email, IP={$_SERVER['REMOTE_ADDR']} \nAGENT={$_SERVER['HTTP_USER_AGENT']}";
    }
    return $id;
  }
}
