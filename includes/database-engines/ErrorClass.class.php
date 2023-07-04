<?php
/* MAINTAINED and WELL TESTED */
// BLP 2022-02-06 -- Add E_DEPRECATED
// BLP 2021-03-15 -- see setDevelopment()

define("ERROR_CLASS_VERSION", "2.0.0error");

// Contains the my_errorhandler, my_exceptionhandler, Error class.
// set_exception_handler to my_exceptionhandler
   
// Error handeler function
// Arguments:
// errno
//     The first parameter, errno , contains the level of the error raised, as an integer. 
// errstr
//     The second parameter, errstr , contains the error message, as a string. 
// errfile
//     The third parameter is optional, errfile , which contains the filename that the error was raised in, as a string. 
// errline
//     The fourth parameter is optional, errline , which contains the line number the error was raised at, as an integer. 
// errcontext
//     The fifth parameter is optional, errcontext , which is an array that points to the active symbol table at the
//     point the error occurred. In other words, errcontext will contain an array of every variable that existed in
//     the scope the error was triggered in. User error handler must not modify error context.  
// If the function returns FALSE then the normal error handler continues.

function my_errorhandler($errno, $errstr, $errfile, $errline) { //, array $errcontext=null) {
  $errortype = array (
                      E_ERROR              => 'Error',
                      E_WARNING            => 'Warning',
                      E_PARSE              => 'Parsing Error',
                      E_NOTICE             => 'Notice',
                      E_CORE_ERROR         => 'Core Error',
                      E_CORE_WARNING       => 'Core Warning',
                      E_COMPILE_ERROR      => 'Compile Error',
                      E_COMPILE_WARNING    => 'Compile Warning',
                      E_USER_ERROR         => 'User Error',
                      E_USER_WARNING       => 'User Warning',
                      E_USER_NOTICE        => 'User Notice',
                      E_STRICT             => 'Runtime Notice',
                      E_RECOVERABLE_ERROR  => 'Catchable Fatal Error'
                     );

  //error_log("Top of my_errorhandler: $errno, {$errortype[$errno]}, $errstr");
  
  $errmsg = "File=$errfile\nLine=$errline\nMessage=$errstr ";

  $backtrace = debug_backtrace();

  array_shift($backtrace); // get rid of first trace which is this function.

  $btrace = '';
  
  foreach($backtrace as $val) {
    if(isset($val['function'])) {
      $btrace .= "function: {$val['function']} in {$val['file']} on line {$val['line']}\n";
      if(isset($val['args'])) {
        foreach($val['args'] as $arg) {        
          if(@get_class((object)$arg) || is_array($arg)) {
            //error_log("CLASS or ARRAY");
            // A class or array
            $x = escapeltgt(print_r($arg, true));
            $btrace .= "          arg: $x\n";
          } else {
            // Not a class or array

            $arg = ($arg === false) ? 'false' : $arg;
            $arg = ($arg === true) ? 'true' : $arg;
            $x = escapeltgt(print_r($arg, true));
            $btrace .= "          arg: $x\n";
          }
        }
      }
    }
  }

  if($btrace) {
    $btrace = "<br>\nBacktrace:\n<pre style='text-align: left'>$btrace</pre>";
  } else {
    $btrace = "\n";
  }
  
  $errmsg .= "$btrace";
  // This may be defined by sites that have members

  finalOutput($errmsg, "{$errortype[$errno]}");
  return true; // DON'T do normal error handeling.
}

//******************************  
// Set up an exception handler if one is not already defined
// Relies on the statics from 'ErrorClass' to format the output etc.
// ErrorClass::$development
// ErrorClass::$nohtml
// ErrorClass::$noEmail

function my_exceptionhandler($e) {
  //error_log("Top of my_exceptionhandler");
  $cl =  get_class($e);

  $error = $e; // get the full error message

  // If this is a SqlException then the formating etc. was done by the class
  
  if($cl != "SqlException") {
    // NOT SqlException

    // Get Trace information
    
    $traceback = '';
    //echo "ONE, cl=$cl<br>";
    foreach($e->getTrace() as $v) {
      //echo "TWO<br>";
      // The key here is a numeric and
      // $v is an assoc array with keys 'file', 'line', 'function', 'class' and 'args'.
      
      $args = ''; // This will hold the $v2 values

      foreach($v as $k=>$v1) {
        // $v is an assoc array 'file, line, ...'
        // most $v1's are strings. 'args' is an array
        switch($k) {
          case 'file':
          case 'line':
          case 'function':
          case 'class':
            $$k = $v1;
            break;
          case 'args':
            foreach($v1 as $v2) {
              //cout("type of v2: " .gettype($v2));
              if(is_object($v2)) {
                $v2 = get_class($v2);
              } elseif(is_array($v2)) {
                $v2 = print_r($v2, true);
              }
              $$k .= "\"$v2\", ";
            }
            break;
        }
      }
      $args = rtrim($args, ", "); // $$k was $args so remove the trailing comma.

      // $$k is $file, $line, etc. So we use the referenced values below.

      //echo "HERE<br>";
      $traceback .= " file: $file<br> line: $line<br> class: $class<br>\n".
                    "function: $function($args)<br><br>";
    }

    if($traceback) {
      $traceback = "Trace back:<br>\n$traceback";
      //echo "Traceback<br>";
    }
    
    $error = <<<EOF
<div style="text-align: center; width: 85%; margin: auto auto; background-color: white; border: 1px solid black; padding: 10px;">
Class: <b>$cl</b><br>\n<b>{$e->getMessage()}</b>
in file <b>{$e->getFile()}</b><br> on line {$e->getLine()}$traceback
</div>
EOF;
  }

  finalOutput($error, $cl);
//  if(ErrorClass::getExitonerror() === true) {
//    exit();
//  }
}

// Do the final output part of the error/exception
// $error is the html minus the div with ERROR
// $from is Error or Exception

function finalOutput($error, $from) {
  //error_log("ErrorClass: finalOutput");
  // For use by Email and database.log
  // Turn the error message into just plane text with LF at end of each line where a BR was.
  // and remove the "ERROR" header and any blank lines.

  $err = html_entity_decode(preg_replace("/<.*?>/", '', $error));
  $err = preg_replace("/^\s*$/", '', $err); // remove blank lines

  // Callback to get the user ID if the callback exists

  $userId = '';
  
  if(function_exists('ErrorGetId')) {
    $userId = "User: " . ErrorGetId();
  }

  if(!$userId) $userId = "agent: ". $_SERVER['HTTP_USER_AGENT'] . "\n";

  // Email error information to webmaster
  // During debug set the Error class's $noEmail to ture
  
  if(ErrorClass::getNoEmail() !== true) {
    $s = $GLOBALS["_site"];

    $recipients = "{\"address\": {\"email\": \"$s->EMAILADDRESS\",\"header_to\": \"$s->EMAILADDRESS\"}}";
    $contents = preg_replace(["~\"~", "~\\n~"], ['','<br>'], "$err<br>$userId");
    
    $post =<<<EOF
{"recipients": [
  $recipients
],
  "content": {
    "from": "SqlException@mail.bartonphillips.com",
    "reply_to": "Barton Phillips<barton@bartonphillips.com>",
    "subject": "$from",
    "text": "View This in HTML Mode",
    "html": "$contents"
  }
}
EOF;

    $apikey = file_get_contents("https://bartonphillips.net/sparkpost_api_key.txt"); //("SPARKPOST_API_KEY");
    
    $options = [
                CURLOPT_URL=>"https://api.sparkpost.com/api/v1/transmissions", //?num_rcpt_errors",
                CURLOPT_HEADER=>0,
                CURLOPT_HTTPHEADER=>[
                                     "Authorization:$apikey",
                                     "Content-Type:application/json"
                                    ],
                CURLOPT_POST=>true,
                CURLOPT_RETURNTRANSFER=>true,
                CURLOPT_POSTFIELDS=>$post
                                   ];
    //error_log("SqlException: options=" . print_r($options, true));
        
    $ch = curl_init();
    curl_setopt_array($ch, $options);

    $result = curl_exec($ch);
    error_log("SqlException: Send To ME. RESULT: $result"); // This should stay!!!
  }

  // Log the raw error info.
  // BLP 2021-03-06 -- New server is in New York
  date_default_timezone_set('America/New_York');

  // This error_log should always stay in!! *****************
  error_log("ErrorClass, finalOutput: $from\n$err\n$userId");
  // ********************************************************
    
  if(ErrorClass::getDevelopment() !== true) {
    // Minimal error message
    $error = <<<EOF
<p>The webmaster has been notified of this error and it should be fixed shortly. Please try again in
a couple of hours.</p>

EOF;
    $err = " The webmaster has been notified of this error and it should be fixed shortly." .
           " Please try again in a couple of hours.";
  }

  if(ErrorClass::getNohtml() === true) {
    $error = "$from: $err";
  } else {
    $error = <<<EOF
<div style="text-align: center; background-color: white; border: 1px solid black; width: 85%; margin: auto auto; padding: 10px;">
<h1 style="color: red">$from</h1>
$error
</div>
EOF;
  }

  if(ErrorClass::getNoOutput() !== true) {
    //************************
    // Don't remove this echo
    echo $error; // BLP 2022-01-28 -- on CLI this outputs to the console, on apache it goes to the client screen.
    //***********************
  }
  return;
}

// Set exception handler

set_exception_handler('my_exceptionhandler');

/****************************************************************
/**
 * Error Class
 * This class only sets/gets the static private variables.
 * The constructor and set them if called with $args.
 * If $args['errType'] is set then that is the value of $errType
 * else it is set as bellow along with the set_error_handler().
 */

class ErrorClass {
  private static $noEmail = false;
  private static $development = false;
  private static $noHtml = false;
  private static $errType = null;
  private static $noOutput = false;
  private static $noBacktrace = false; // BLP 2023-06-22
  private static $errLast = false; // BLP 2023-06-22
  //private static $exitonError = true; // BLP 2023-06-23 -
  
  // BLP 2023-06-23 - Removed constructor because all of the methods are static.
  // The set_error_handler() is done by the php.ini file.
  
  static public function setErrorType($bits) {
    self::$errType = $bits;
    //echo "Set error handler: $bits<br>\n";
    error_reporting($bits);    
    set_error_handler('my_errorhandler', $bits);
  }

  static public function getErrorType() {
    return self::$errType;
  }

  // BLP 2023-06-22 - START
  static public function setNobacktrace($b) {
    self::$noBacktrace = $b;
  }

  static public function getNobacktrace() {
    return self::$noBacktrace;
  }

  static public function setErrlast($b) {
    self::$errLast = $b;
  }

  static public function getErrlast() {
    return self::$errLast;
  }

  /*
  static public function setExitonerror($b) {
    self::$exitonError = $b;
  }

  static public function getExitonerror() {
    return self::$exitonError;
  }
  */
  // BLP 2023-06-22 - END
  
  //BLP 2021-03-15 -- if we set development also set noEmai!
  
  static public function setDevelopment($b) {
    self::$development = $b;
    self::$noEmail = $b;
  }

  static public function getDevelopment() {
    return self::$development;
  }
  
  static public function setNoEmail($b) {
    self::$noEmail = $b;
  }

  static public function getNoEmail() {
    return self::$noEmail;
  }
  
  static public function setNoHtml($b) {
    self::$noHtml = $b;
  }

  static public function getNoHtml() {
    return self::$noHtml;
  }
  
  static public function setNoOutput($b) {
    return self::$noOutput = $b;
  }

  static public function getNoOutput() {
    return self::$noOutput;
  }

  static public function getVersion() {
    return ERROR_CLASS_VERSION;
  }
  
  public function __toString() {
    return __CLASS__;
  }
}
