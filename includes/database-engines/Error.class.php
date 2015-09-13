<?php
// Contains the my_errorhandler, my_exceptionhandler, Error class.
// set_exception_handler to my_exceptionhandler
// The Error class constructor does set_error_handler to my_errorhandler.
   
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

function my_errorhandler($errno, $errstr, $errfile, $errline, array $errcontext) {
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

  $errmsg = "File=$errfile, Line=$errline, Message=$errstr ";

  $backtrace = debug_backtrace();

  array_shift($backtrace); // get rid of first trace which is this function.

  $btrace = '';
  
  foreach($backtrace as $val) {
    if(isset($val['function'])) {
      $btrace .= "function: {$val['function']} in {$val['file']} on line {$val['line']}\n";
      if(isset($val['args'])) {
        foreach($val['args'] as $arg) {        
          $arg = ($arg === false) ? 'false' : $arg;
          $arg = ($arg === true) ? 'true' : $arg;
          $x = escapeltgt(var_export($arg, true));            
          $btrace .= "          arg: $x\n";
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
  return true;
}

//******************************  
// Set up an exception handler if one not already defined
// Relies on the statics from Error Class to format the output etc.
// Error::$development
// Error::$nohtml
// Error::$noEmailErrs

function my_exceptionhandler(Exception $e) {
  $cl =  get_class($e);

  $error = $e; //->getMessage; // get the full error message

  // If this is a SqlException then the formating etc. was done by the class
  
  if($cl != "SqlException") {
    // NOT SqlException

    // Get Trace information
    
    $traceback = '';

    foreach($e->getTrace() as $v) {
      // $k is a numeric
      $args = '';
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
      $args = rtrim($args, ", ");

      $traceback .= "file: $file<br>line: $line<br>class: $class<br>".
                    "function: $function($args)<br><br>";
    }

    if($traceback) $traceback = "<hr><div style='text-align: left'>Trace back:<br>$traceback</div>";

    $error = <<<EOF
<div style="text-align: center; width: 85%; margin: auto auto; background-color: white; border: 1px solid black; padding: 10px;">
Class: <b>$cl</b><br>Exception: &quot;<b>{$e->getMessage()}</b>&quot;<br>
in file <b>{$e->getFile()}</b><br>on line {$e->getLine()}
$traceback
</div>
EOF;
    
  }

  finalOutput($error, "$cl");
  exit();
}

// Do the final output part of the error/exception.
// $error is the html minus the div with ERROR
// $from is Error or Exception

function finalOutput($error, $from) {
  // For use by Email and database.log
  // Turn the error message into just plane text with LF at end of each line where a BR was.
  // and remove the "ERROR" header and any blank lines.

  $err = preg_replace("/<br>/", "\n", $error);
  $err = htmlspecialchars_decode(preg_replace("/<.*?>/", '', $err));
  $err = preg_replace("/^\s*?\n/m", '', $err);

  // Callback to get the user ID if the callback exists

  $userId = '';
  $agent = $_SERVER['HTTP_USER_AGENT'] . "\n";
  
  if(function_exists('ErrorGetId')) {
    $userId = "User: " . ErrorGetId();
  }

  if($userId) $userId = "$userId\n";

  // Email error information to webmaster
  // During debug set the Error class's $noEmailErrs to ture
  
  if(Error::getNoEmailErrs() !== true) {
    mail(EMAILADDRESS, $from, "{$err}{$userId}",
         "From: ". EMAILFROM, "-f ". EMAILRETURN);
  }

  if(!file_exists(LOGFILE)) {
    // create it and change the permisions
    touch(LOGFILE);
    chmod(LOGFILE, 0666);
  }

  date_default_timezone_set('America/Denver');
  file_put_contents(LOGFILE, date("Y-m-d H:i:s") . "\n$from: {$err}{$userId}{$agent}*****\n",
                    FILE_APPEND);

  if(Error::getDevelopment() !== true) {
    // Minimal error message
    $error = <<<EOF
<p>The webmaster has been notified of this error and it should be fixed shortly. Please try again in
a couple of hours.</p>

EOF;
    $err = " The webmaster has been notified of this error and it should be fixed shortly." .
           " Please try again in a couple of hours.";
  }

  if(Error::getNoHtml() === true) {
    $error = "$from:\n$err\n";
  } else {
    $error = <<<EOF
<div style="text-align: center; background-color: white; border: 1px solid black; width: 85%; margin: auto auto; padding: 10px;">
<h1 style="color: red">$from</h1>
$error
</div>
EOF;
  }

  if(Error::getNoOutput() !== true) {
    echo $error;
  }
}

// Set exception handler

set_exception_handler('my_exceptionhandler');

/****************************************************************
/**
 * Error Class
 */

class Error {
  private static $noEmailErrs = false;
  private static $development = false;
  private static $noHtml = false;
  private static $errType = null;
  private static $noOutput = false;
  private static $instance = null;

  // args can be array|object. noEmailErrs, development, noHtml, noOutput, errType
  
  public static function init($args=null) {
    if(is_null(self::$instance)) {
      self::$instance = new Error($args);
    }
    return self::$instance;
  }

  /**
   *  Private Constructor
   */

  private function __construct($args) {
    if(is_array($args)) {
      $args = (object)$args;
    }
    
    if(isset($args->noEmailErrs)) self::$noEmailErrs = $args->noEmailErrs; //$args['noEmailErrs'];
    if(isset($args->development))self::$development = $args->development;
    if(isset($args->nohtml)) self::$noHtml = $args->noHtml;
    if(isset($args->noOutput)) self::$noOutput = $args->noOutput;
    // ignore E_NOTICE errors by default

    if(!isset($args->errType)) {
      if(is_null(self::$errType)) {
        error_reporting(E_ALL ^ (E_NOTICE | E_WARNING));
        set_error_handler('my_errorhandler', E_ALL & ~(E_NOTICE | E_WARNING));
      } // if self::$errType is set then the handler has already been set
    } else {
      // $args->errType is set
      self::$errType = $args->errType;
      error_reporting($args->errType);
      set_error_handler('my_errorhandler', $args->errType);
    }      
  }

  static public function setNoEmailErrs($b) {
    self::$noEmailErrs = $b;
  }

  static public function getNoEmailErrs() {
    return self::$noEmailErrs;
  }
  
  static public function setErrorType($bits) {
    self::$errType = $bits;
    //echo "Set error handler: $bits<br>\n";
    error_reporting($bits);    
    set_error_handler('my_errorhandler', $bits);
  }

  static public function getErrorType() {
    return self::$errType;
  }
  
  static public function setDevelopment($b) {
    self::$development = $b;
  }

  static public function getDevelopment() {
    return self::$development;
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
  
  public function __toString() {
    return __CLASS__;
  }
}
