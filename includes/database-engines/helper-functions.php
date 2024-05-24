<?php
/* HELPER FUNCTIONS. Well tested and maintained */
// BLP 2023-10-04 - added varexport() functions.

define("HELPER_FUNCTION_VERSION", "1.1.2helper-pdo"); // BLP 2023-10-04 - 

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
// BLP 2024-05-24 - This now uses PHP_SAPI to determin if we should use <pre> for websites.

if(!function_exists('vardump')) {
  function vardump() {
    $args = func_get_args();

    if(count($args) > 1 && is_string($args[0])) {
      $msg = array_shift($args);
      //$msg = "<b>$msg:</b>\n";
    }
    for($i=0; $i < count($args); ++$i) {
      $v .= print_r($args[$i], true);
    }
    if(PHP_SAPI === 'cli') {
      echo "$msg:\n$v\n";
    } else {
      echo "<pre class='vardump'><b>$msg:</b><br>" . escapeltgt($v) . "</pre>";
    }
  }
} 

// As above but does not escape the lt/gt or do any HTML.
// BLP 2024-05-24 - left this for backward compatability

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

// BLP 2023-10-04 - varexport makes value readable
// BLP 2024-05-24 -

if(!function_exists('varexport')) {
  function varexport() {
    $args = func_get_args();

    if(count($args) > 1 && is_string($args[0])) {
      $msg = array_shift($args);
      //$msg = "<b>$msg:</b>\n";
    }
    for($i=0; $i < count($args); ++$i) {
      $v .= var_export($args[$i], true);
    }
    if(PHP_SAPI === 'cli') {
      echo "$msg:\n$v\n";
    } else {
      echo "<pre class='vardump'><b>$msg:</b><br>" . escapeltgt($v) . "</pre>";
    }
  }
} 

// BLP 2023-10-04 - As above but does not escape the lt/gt or do any HTML.
// BLP 2024-05-24 - left this for backward compatability

if(!function_exists('varexportNoEscape')) {
  function varexportNoEscape() {
    $args = func_get_args();

    if(count($args) > 1 && is_string($args[0])) {
      $msg = array_shift($args);
      $msg = "$msg:\n";
    }
    for($i=0; $i < count($args); ++$i) {
      $v .= var_export($args[$i], true);
    }
    echo "$msg$v\n";
  }
}

// Strip Comments

if(!function_exists('stripComments')) {
  function stripComments($x) {
    $pat = '~".*?"(*SKIP)(*FAIL)|(?://[^\n]*)|(?:#[^\n]*)|(?:/\*.*?\*/)~s';
    return preg_replace($pat, "", $x);
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
 * BLP 2023-03-03 - 
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

// This is called from dbPdo.class.php at my_exceptionhandler($e)

if(!function_exists('ErrorGetId')) {
  function ErrorGetId() {
    if($_COOKIE['SiteId']) {
      $siteId = $_COOKIE['SiteId'];
    }

    if(empty($siteId)) {
      // This is the generic version
      $id = "IP={$_SERVER['REMOTE_ADDR']} \nAGENT={$_SERVER['HTTP_USER_AGENT']}";
    } else {
      // This is for members
      $id = "SiteId=$siteId, IP={$_SERVER['REMOTE_ADDR']} \nAGENT={$_SERVER['HTTP_USER_AGENT']}";
    }
    return $id;
  }
}

// BLP 2023-06-21 -
// array_deep()

if(!function_exists('array_deep')) {
  // If it does not already exist define it here
  function array_deep($a) {
    if(is_array($a)) {
      $a = array_map('array_deep', $a);
      $ret = "";
      foreach($a as $key=>$val) {
        if(is_numeric($key)) {
          $ret .= "$val, ";
        } else {
          $ret .= "$key=>$val";
        }
      }
      $ret = rtrim($ret, ", ");
      $a = "array($ret)";
    } else {
      if(is_string($a)) {
        if(strpos($a, "'")) {
          $a = "\"$a\"";
        } else {
          $a = "'$a', ";
        }
      } elseif(is_numeric($a)) {
        $a = "$a, ";
      }
    }
    return $a;
  }
}

