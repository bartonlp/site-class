<?php
/*
 * Maintained and well tested.
 *
 * @package PdoException
 * Extends Exception
 */

define("PDOEXCEPTION_CLASS_VERSION", "1.0.0exception-pdo");

class PdoException extends Exception {
  /**
   * Constructor
   * @param string $message: text which tells what went wrong
   * @param object $self: this is the '$this' of the caller.
   */

  public function __construct($msg, $self=null) {
    // If the caller was a database class then $this->db should be the database resorce.

    [$message, $code] = $this->PdoError($msg, $self); // private helper method.

    // Do the Exception constructor which has $message and $code as arguments.

    parent::__construct($message, $code);
  }

  /**
   * __toString()
   * @return string $this->message ($e->getMessage)
   */
  
  public function __toString() {
    return $this->message; // message is property of Exception
  }

  /* Private Methods */
  
  /**
   * PdoError
   * Private
   * @param string $msg error message
   * @param object $self is the $this where the error occured
   * @return array([html error text], [error number]);
   */

  private function PdoError($msg="NO MESSAGE PROVIDED", $self) {
    if(is_null($self)) {
      $Errno = -9999;
      $Error = "No valid \$self->errno or \$self->error.";
    } else {
      $Error = $self->error;
      $Errno = $self->errno;
    }
    
    if(($size = strlen($msg)) > 500) {
      //error_log("TOO LONG: $msg");
      $msg = "Message Too Long: $size<br>\nMESSAGE: $msg";
    }
    if(($size = strlen($Error)) > 500) $Error = "Error Too Long: $size";

    $backtrace = debug_backtrace(); // trace back information

    $caller = $backtrace[1]; // Get caller information

    array_shift($backtrace); // PdoError
    array_shift($backtrace); // PdoException

    // BLP 2023-06-22 - START
    
    if(ErrorClass::getNobacktrace() === false) {
      $firstcaller = '';

      $args = '';

      if(count($backtrace)) {
        foreach($backtrace as $bk) {
          $args = '';

          if($bk['args']) {
            foreach($bk['args'] as $arg) {
              if(is_object($arg)) continue;

              if(is_string($arg)) {
                if(strpos($arg, "'")) {
                  $args .= "\"$arg\", ";
                } else {
                  $args .= "'$arg', ";
                }
              } elseif(is_numeric($arg)) {
                $args .= "$arg, ";
              } elseif(is_array($arg)) {
                // see helper-functions.php

                $args .= array_deep($arg);
              }
            }
            $args = rtrim($args, ", ");
          }

          if($bk['class']) {
            $classfunc = "\n<b>{$bk['class']}::{$bk['function']}($args)</b>\n";
          } else {
            $classfunc = "function <b>{$bk['function']}($args)</b>";
          }
          $firstcaller .= "$classfunc<br> in <b>{$bk['file']}</b><br>\n".
                          " on line <b>{$bk['line']}</b><br>\n";
        }
      }
    }

    if(ErrorClass::getErrlast() === true) {
      $cnt = count($backtrace) -1;
      $caller = $backtrace[$cnt];
    } 
    // BLP 2023-06-22 - END
    
    $cwd = getcwd();

    $error = <<<EOF
\n<p>PDO: <i>$msg</i><br>
error=<i>$Error</i>;, \$Errno=<i>$Errno</i><br>
cwd=$cwd<br>
called from <strong>{$caller['file']}</strong><br> on line <strong>{$caller['line']}</strong><br>
EOF;

    if(!empty($firstcaller)) {
      $error .= "\n<br>Back Trace:<br>\n$firstcaller";
    }

    // this is the message and code to pass to Exception.
    
    return array($error, $Errno);
  }

  public static function getVersion() {
    return PDOEXCEPTION_CLASS_VERSION;
  }
} // End PdoException Class
