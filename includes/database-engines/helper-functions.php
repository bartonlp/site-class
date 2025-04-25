<?php
/* HELPER FUNCTIONS. Well tested and maintained */
// BLP 2023-10-04 - added varexport() functions.

define("HELPER_FUNCTION_VERSION", "1.2.0helper-pdo"); // BLP 2025-04-03 - add logic for decoding bitmap

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

// These two new function can encode and decode things like the site field in the new bots table.
// It is a bitmap of the site domains. See defines.php for the site bitmap.

// Endoce an array using a map.
// @param $ar is the input array of strings to encode.
// @param $map is the bitmap array with 'value'=>bit entries.
// @return $bitmask
// BLP 2025-04-03 - 

function encodeArray(array $ar, array $map): int {
  $bitmask = 0;

  // $ar is an array of strings to encode. For example if $ar is ['abc', 'ghi'] and $map
  // is ['abc'=>1, 'def'=>2, 'ghi'=>4], then the returned $bitmask is binary 0b0101 or hex 0x5.
  
  foreach ($ar as $v) { // Get each string from the input array
    if (isset($map[$v])) { // if the string is in the bitmap array
      $bitmask |= $map[$v]; // then get the bit value of the string and 'or' it into the return mask.
    }
  }
  return $bitmask; // This has a bit set for each of the input array's strings.
}

// Decode bit map
// @param $bitmask is an integer bitmask to be decoded
// @param $map is the decoding array
// @retun $result array with the decoded values.
// BLP 2025-04-03 - 

function decodeSites(int $bitmask, array $map): array {
  $result = []; // start with an empty array

  // The $map is an array with value>bit. For example $map=["abc"=>1, "def"=>2, "ghi"=>4].
  // If we have a $bitmask of 0b0101 or 0x5 we would return 'abc' and 'ghi' in the $result array.
  
  foreach ($map as $site => $bit) { // from the map array 
    if (($bitmask & $bit) === $bit) { // if the $bitmask and $bit equals the $bit
      $result[] = $site; // Then put the $site string value into the result array
    }
  }

  return $result;
}

// Build the Error Constance map.
// map is key=>value.

function buildErrorConstantMap(string $prefix='BOTS_', string $section='user'): array {
  $map = array_filter(
                      get_defined_constants(true)[$section] ?? [],
                      fn($k) => str_starts_with($k, $prefix),
                      ARRAY_FILTER_USE_KEY
                     );
  return $map; // return name=>type
}

// Given the error $type as an integer form functions like error_get_last() etc.
// Get the error constant as a string like 'E_Error' or E_Warning' etc.

function getErrorConstantName(int $type, string $prefix='BOTS_', string $section='user'): string {
  $kvmap = array_filter(
                      get_defined_constants(true)[$section] ?? [],
                      fn($k) => str_starts_with($k, $prefix),
                      ARRAY_FILTER_USE_KEY
                     );

  // $kvmap is name=>type
  // Flip the array so it it type=>name.
  
  $vkmap = array_flip($kvmap);

  return $vkmap[$type] ?? "UNKNOWN ($type)";
}

// Helper for preg_match that lets me look into an array of items.
// @param: $pattern string. Search patteren.
// @param: $inputs array. An array of items to seach through.
// @return: bool.
// @side-affect: $m array if any matches. The array is zero based and if NOT empty $m[0] is the
// first item in the array and $m[1] the second etc.

function preg_match_any(string $pattern, array $inputs, ?array &$m=null): bool {
  foreach ($inputs as $input) {
    if(preg_match($pattern, $input)) {
      echo "$input, true";
      $m[] = true;
    } 
  }
  if(empty($m)) return false;
  return true;
}

/**
 * getBrowserInfo. Try to determin if this agent is a BOT using the User Agent String.
 * @param: string $agent. The User Agent String.
 * @return: array. [$browser, $isBot]. $browser is a name for the browser plus the name of the engine
 *   seperated by a comma. $isBot is a bool indicating if we think this agent is a bot.
 * This helper function does NO database logic.
 **/

function getBrowserInfo(string $agent): array {
  if(empty($agent)) {
    $botbits = BOTS_NOAGENT;

    // Without an agent the rest of this makes no sense.
    // $browser='NO_AGENT', $engin='NO_ENGINE', $botbits=BOTS_NOAGENT, $trackerbits=0, $isBot=true.
    
    return ['NO_AGENT', 'NO_ENGINE', BOTS_NOAGENT, 0, true]; 
  }

  $pattern = "~duckduckgo|googlebot|bingbot|slurp|yandex|baiduspider|telegrambot|facebookexternalhit|".
             "brave|edg/|edge/|firefox|chrome|crios|safari|trident|msie|opera|konqueror~i";

  $isBot = false;
  $browser = 'Unknown'; // If not found in preg_match_all this is the default.

  if(preg_match_all($pattern, $agent, $matches)) {
    $tokens = array_map('strtolower', $matches[0]);
    $last = end($tokens);

    switch ($last) {
      case 'duckduckgo':
        $browser = 'DuckDuckGo Bot'; $isBot = true; break;
      case 'googlebot':
        $browser = 'Googlebot'; $isBot = true; break;
      case 'bingbot':
        $browser = 'Bingbot'; $isBot = true; break;
      case 'slurp':
        $browser = 'Yahoo! Slurp'; $isBot = true; break;
      case 'yandex':
        $browser = 'Yandex Bot'; $isBot = true; break;
      case 'baiduspider':
        $browser = 'Baidu Spider'; $isBot = true; break;
      case 'telegrambot':
        $browser = 'Telegram Bot'; $isBot = true; break;
      case 'facebookexternalhit':
        $browser = 'Facebook Bot'; $isBot = true; break;
      case 'brave':
        $browser = 'Brave'; break;
      case ' edg/':
      case 'edge/':
        $browser = 'MS Edge'; break;
      case 'trident':
      case 'msie':
        $browser = 'Internet Explorer'; break;
      case 'crios':
      case 'chrome':
        $browser = 'Chrome'; break;
      case 'safari':
        $penult = $tokens[count($tokens) - 2] ?? '';
        $browser = in_array($penult, ['chrome', 'crios']) ? 'Chrome' : 'Safari';
        break;
      case 'firefox':
        $browser = 'Firefox'; break;
      case 'opera':
        $browser = 'Opera'; break;
      case 'konqueror':
        $browser = 'Konqueror'; break;
      default:
        $browser = 'Unknown';
        error_log("Database Unrecognized browser: line=". __LINE__ . "\n" . print_r($tokens, true));
    }
  }

  // Detect engine

  if(stripos($agent, 'Gecko') !== false && stripos($agent, 'like Gecko') === false) {
    $engine = 'Gecko';
  } elseif(stripos($agent, 'AppleWebKit') !== false) {
    $engine = 'WebKit';
  } elseif(stripos($agent, 'Chrome') !== false || stripos($agent, 'Brave') !== false) {
    $engine = 'Blink';
  } else {
    $engine = 'Unknown';
  }

  if($x = preg_match("~@|bot|spider|scan|HeadlessChrome|python|java|wget|nutch|perl|libwww|lwp-trivial|curl|PHP/|urllib|".
                     "crawler|GT::WWW|Snoopy|MFC_Tear_Sample|HTTP::Lite|PHPCrawl|URI::Fetch|Zend_Http_Client|".
                     "http client|PECL::HTTP|Go-|python~i", $agent) === 1)
  { 
    // If we got a 1 this is a match

    $isBot = true;
    $botbits |= BOTS_MATCH;
  }

  // BLP 2025-04-21 - Look for iPhone or Android

  if(preg_match("~android|iphone~i", $agent, $m) === 1) {
    if(stripos($agent, 'android') !== false && stripos($agent, 'chrome') !== false) {
      $browser = "{$m[0]},Chrome,Blink";
    } 
    $browser = "{$m[0]},$browser";
  }

  if(preg_match("~\+?https?://~", $agent) === 1) {
    $isBot = true;
    $botbits |= BOTS_GOODBOT;
  }

  if($botbits & (BOTS_GOODBOT | BOTS_NOAGENT | BOTS_MATCH)) {
    $trackerbits = TRACKER_BOT;
  }
  
  return [
          $browser,
          $engine,
          $botAsBits,
          $trackerinfo,
          $isBot,
         ];
}
