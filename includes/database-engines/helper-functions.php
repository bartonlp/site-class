<?php
/* HELPER FUNCTIONS. Well tested and maintained */
// BLP 2025-12-30 - added escapeOnlyScripts()

define("HELPER_FUNCTION_VERSION", "1.2.3helper-pdo"); 

$DEBUG = true;

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
// This now uses PHP_SAPI to determin if we should use <pre> for websites.

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

// As above but does not escape the lt/gt or do any HTML.

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

// BLP 2023-10-04 - varexport makes value readable
// BLP 2024-05-24 -

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

// As above but does not escape the lt/gt or do any HTML.

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

/**
 * Strip comments
 *
 * Used to strip C style comments. For example, my mysitemap.json
 * which is not a real json file because it has comments.
 *
 * @param string $x
 * @return string
 */
function stripComments($x) {
  $pat = '~".*?"(*SKIP)(*FAIL)|(?://[^\n]*)|(?:#[^\n]*)|(?:/\*.*?\*/)~s';
  return preg_replace($pat, "", $x);
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

// Change < and > into "&lt;" and "&gt;" entities

function escapeltgt($value) {
  $value = preg_replace(array("/</", "/>/"), array("&lt;", "&gt;"), $value);
  return $value;
}

// Change <script> etc to &lt; script &gt; etc.
// BLP 2025-12-30 -

function escapeOnlyScripts(string $text):string {
    // 1. Define what we are looking for (The Patterns)
    $search = [
        '/<script/i',       // Case-insensitive <script
        '/<\/script>/i',    // Case-insensitive </script>
        '/<(script.*?)>/i'  // Handles attributes like <script type="text/javascript">
    ];

    // 2. Define what to put in their place (The Replacements)
    $replace = [
        '&lt;script',
        '&lt;/script&gt;',
        '&lt;$1&gt;'        // $1 keeps whatever attributes were inside the brackets
    ];

    // 3. Run it once using the arrays
    return preg_replace($search, $replace, $text);
}

// This is called from dbPdo.class.php at my_exceptionhandler($e)

function ErrorGetId() {
  if($_COOKIE['SiteId']) {
    $siteId = $_COOKIE['SiteId'];
  }

  if(empty($siteId)) {
    // This is the generic version
    $id = "ip={$_SERVER['REMOTE_ADDR']}, agent={$_SERVER['HTTP_USER_AGENT']}";
  } else {
    // This is for members
    $id = "SiteId=$siteId, ip={$_SERVER['REMOTE_ADDR']}, agent={$_SERVER['HTTP_USER_AGENT']}";
  }
  return $id;
}

// BLP 2023-06-21 -
// array_deep()

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
             "brave| edg/|edge/|firefox|chrome|crios|safari|trident|msie|opera|konqueror~i";

  $isBot = false;
  $browser = 'Unknown'; // If not found in preg_match_all this is the default.

  if(preg_match_all($pattern, $agent, $matches)) {
    $tokens = array_map('strtolower', $matches[0]);
    $last = end($tokens); // end() looks at last element

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
        if($DEBUG) error_log("helper-functions Unrecognized browser: agent=$agent, line=". __LINE__);
    }
  }

  // Detect engine

  $engine = "Unknown";
  
  if(stripos($agent, 'Gecko') !== false && stripos($agent, 'like Gecko') === false) {
    $engine = 'Gecko';
  } elseif(stripos($agent, 'AppleWebKit') !== false) {
    $engine = 'WebKit';
  } elseif(stripos($agent, 'Chrome') !== false || stripos($agent, 'Brave') !== false) {
    $engine = 'Blink';
  } else {
    $engine = 'Unknown';
  }

  if(preg_match("~@|bot|spider|scan|HeadlessChrome|python|java|wget|nutch|perl|libwww|lwp-trivial|curl|PHP/|urllib|".
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
      $browser = "{$m[0]},Chrome";
      $engine = "Blink";
    } else { 
      $browser = "{$m[0]},$browser";
    }
  }

  if(preg_match("~\+?https?://~", $agent) === 1) {
    $isBot = true;
    $botbits |= BOTS_GOODBOT;
  }

  // If goodbot, no agent or match is set then add bot to the list.
  
  if($botbits & (BOTS_GOODBOT | BOTS_NOAGENT | BOTS_MATCH)) { // If this is true (not zero)
    $botbits |= BOTS_BOT;
    $trackerbits = TRACKER_BOT;
  }
  
  return [
          $browser,
          $engine,
          $botbits,
          $trackerbits,
          $isBot,
         ];
}

/*
 * xjson_decode. Decodes a json string and throws an exception if there is a problem
 * @param: string $exp
 * @param: bool $assoc. Defalt false
 * @param: int $depth. Defalt 512
 * @param: int $flag. Default JSON_THROW_ON_ERROR
 * @return: array|object
 * On failure throws an exception.
 */

function xjson_decode(string $exp, bool $assoc=false, int $depth=512, int $flag=JSON_THROW_ON_ERROR): mixed {
  return json_decode($exp, $assoc, $depth, $flag);
}

/*
 * xjson_encode. Encodes a PHP value (variable) and throws an exception if there is a problem
 * @param: mixed $exp
 * @param: int $flag. Default JSON_THROW_ON_ERROR
 * @param: int $depth. Defalt 512
 * @return: string
 * On failure throws an exception.
 */

function xjson_encode(mixed $exp, int $flag=JSON_THROW_ON_ERROR, int $depth=512): string {
  return json_encode($exp, $flag, $depth);
}

/*
 * br2nl. Change <br> to \n
 * @param: string $input
 * @return: string
 */

function br2nl(string $input): string {
  return preg_replace('~<br\s*/?>~i', "\n", $input);
}

/*
 *
 */
function addClassesToTableColumns_dom_x(string $desc, array $columns, string $prefix=''):string {
  $map = [];
  if(array_values($columns) === $columns) {
    foreach($columns as $i=>$name) $map[$i+1] = $prefix.$name;
  } else {
    foreach($columns as $i=>$name) $map[$i] = $prefix.$name;
  }

  $html = '<table id="__wrap__">'.$desc.'</table>';
  $dom = new DOMDocument('1.0', 'UTF-8');
  @$dom->loadHTML($html, LIBXML_HTML_NOIMPLIED|LIBXML_HTML_NODEFDTD);
  $xp = new DOMXPath($dom);

  foreach($xp->query('//table[@id="__wrap__"]/tr') as $tr) {
    $col = 1;
    foreach($xp->query('./td|./th', $tr) as $cell) {
      $span = $cell->hasAttribute('colspan') ? max(1,(int)$cell->getAttribute('colspan')) : 1;
      if(isset($map[$col])) {
        $target = $map[$col];               // e.g., 'tracker_id'
        $base = $target;                     // e.g., 'tracker_id' -> 'id' if prefix present
        if($prefix !== '' && str_starts_with($base, $prefix)) {
          $base = substr($base, strlen($prefix)); // 'id'
        }

        // tokenize existing classes
        $existing = preg_split('~\s+~', trim($cell->getAttribute('class')), -1, PREG_SPLIT_NO_EMPTY);
        $existing = $existing ?: [];

        // remove the base class (e.g., 'id') if present
        $filtered = [];
        foreach($existing as $c) if($c !== $base) $filtered[] = $c;

        // add the target class and de-dupe
        $filtered[] = $target;
        $filtered = array_values(array_unique($filtered));

        $cell->setAttribute('class', implode(' ', $filtered));
      }
      $col += $span;
    }
  }

  $table = $xp->query('//table[@id="__wrap__"]')->item(0);
  $out = '';
  foreach($table->childNodes as $child) $out .= $dom->saveHTML($child);
  return $out;
}


function addClassesToTableColumns_dom(string $desc, array $columns, string $prefix=''):string {
  // Build two maps: base (unprefixed) and target (prefixed)
  $baseMap   = []; // [colIndex => 'id' | 'site' | ...]
  $targetMap = []; // [colIndex => 'tracker_id' | 'trk_site' | ...]
  if(array_values($columns) === $columns) {
    foreach($columns as $i=>$name) {
      $baseMap[$i+1]   = $name;
      $targetMap[$i+1] = $prefix.$name;
    }
  } else {
    foreach($columns as $i=>$name) {
      $baseMap[$i]   = $name;
      $targetMap[$i] = $prefix.$name;
    }
  }

  $html = '<table id="__wrap__">'.$desc.'</table>';

  $dom = new DOMDocument('1.0', 'UTF-8');
  @$dom->loadHTML($html, LIBXML_HTML_NOIMPLIED|LIBXML_HTML_NODEFDTD);
  $xp = new DOMXPath($dom);

  foreach($xp->query('//table[@id="__wrap__"]/tr') as $tr) {
    $col = 1;
    foreach($xp->query('./td|./th', $tr) as $cell) {
      $span = $cell->hasAttribute('colspan') ? max(1,(int)$cell->getAttribute('colspan')) : 1;

      if(isset($baseMap[$col])) {
        $base   = $baseMap[$col];     // e.g., 'id'
        $target = $targetMap[$col];   // e.g., 'tracker_id'

        if($base === 'id') {
          // EXACT RULE: for 'id' column, REPLACE classes with the prefixed one
          $cell->setAttribute('class', $target);
        } else {
          // Default behavior: append prefixed class, de-dup + tidy spaces
          $existing = preg_split('~\s+~', trim($cell->getAttribute('class')), -1, PREG_SPLIT_NO_EMPTY) ?: [];
          $existing[] = $target;
          $existing = array_values(array_unique($existing));
          $cell->setAttribute('class', implode(' ', $existing));
        }
      }

      $col += $span; // respect colspan
    }
  }

  // Extract innerHTML of wrapper
  $table = $xp->query('//table[@id="__wrap__"]')->item(0);
  $out = '';
  foreach($table->childNodes as $child) $out .= $dom->saveHTML($child);
  return $out;
}

function addClassesToTableColumns(string $desc, array $columns, string $prefix = ''): string {
  // Convert from list to 1-based map if needed

  $map = [];

  if(array_values($columns) === $columns) {
    foreach($columns as $i => $name) {
      $map[$i + 1] = $prefix . $name;
    }
  } else {
    foreach($columns as $i => $name) {
      $map[$i] = $prefix . $name;
    }
  }
  
  return preg_replace_callback('~<tr>(.*?)</tr>~s', function($matches) use ($map) {
    $tds = explode('</td>', $matches[1]);
    foreach($tds as $i => &$td) {
      $colIndex = $i + 1;
      if(isset($map[$colIndex])) {
        if(preg_match('~<td([^>]*)class=[\'"]([^\'"]+)[\'"]~', $td, $m)) {
          // Append to existing class
          $existing = $m[2];
          $td = preg_replace('~class=[\'"][^\'"]+[\'"]~', "class='$existing {$map[$colIndex]}'", $td);
        } else {
          // Add new class
          $td = preg_replace('~<td(.*?)>~', "<td$1 class='{$map[$colIndex]}'>", $td);
        }
      }
    }
    return '<tr>' . implode('</td>', $tds) . '</tr>';
  }, $desc);
}

function escapeapos($string) {
    return str_replace("'", "\\'", $string);
}

/**
 * A Deep replacement of apostrophies
 *
 * Replaces apostrophies in an array
 * @param string
 * @return string
 */
function escapeaposDeep(string $value): string {
  if(is_array($value)) {
    foreach($value as $k=>$v) {
      $val[$k] = $this->escapeaposDeep($v);
    }
    return $val;
  } else {
    return $this->escapeapos($value);
  }
}

/**
 * Log important information
 *
 * @param string $info The information to log.
 */
function logInfo(string $info) {
  if(!str_contains($info, ':')) {
    $info = "MESSAGE: $info";
  }
  $date = date("[Y-m-d H:i:s]");
  $info = "$date $info\n";
  file_put_contents('/var/www/data/info.log', $info, FILE_APPEND);
}
