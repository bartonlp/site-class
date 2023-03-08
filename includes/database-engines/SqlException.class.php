<?php
/**
 * @package SqlException
 * Extends Exception
 *   message, code, file and line are members of Exception
 * The Error class provides the following properties to control output:
 */
// BLP 2023-01-15 - Reworked the start of SqlError().

define("SQLEXCEPTION_CLASS_VERSION", "2.1.1exception");

class SqlException extends Exception {
  /**
   * Constructor
   * @param string $message: text which tells what went wrong
   * @param object $self: this is the '$this' of the caller.
   */
  
  public function __construct($msg, $self=null) {
    // If the caller was a database class then $this->db should be the database resorce.

    [$message, $code] = $this->SqlError($msg, $self); // private helper method.

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
   * SqlError
   * Private
   * @param string $msg error message (mysql_error($db))
   * @param object $self is the $this where the error occured
   * @return array([html error text], [error number]);
   */

  private function SqlError($msg="NO MESSAGE PROVIDED", $self) {
    // BLP 2023-01-15 - START. Reworked this section
    if(is_null($self)) {
      $Errno = -9999;
      $Error = "No valid \$self->errno or \$self->error.";
    } else {
      //echo "self: " . print_r($self, true). "<br>";
      //$Error = $self->db->error ?? $self->error;
      //$Errno = $self->db->errno ?? $self->errno;
      $Error = $self->getDbError() ?? $self->error;
      $Errno = $self->getDbErrno() ?? $self->errno;
    }
    // BLP 2023-01-15 - END.
    
    if(($size = strlen($msg)) > 500) {
      //error_log("TOO LONG: $msg");
      $msg = "Message Too Long: $size";
    }
    if(($size = strlen($Error)) > 500) $Error = "Error Too Long: $size";
    
    $backtrace = debug_backtrace(); // trace back information

    $caller = $backtrace[1]; // Get caller information

    array_shift($backtrace); // SqlError
    array_shift($backtrace); // SqlException

    $firstcaller = '';

    // Helper callback function
    
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
              $args .= array_deep($arg);
            }
          }
          $args = rtrim($args, ", ");
        }

        if($bk['class']) {
          $classfunc = "<b>{$bk['class']}::{$bk['function']}($args)</b>";
        } else {
          $classfunc = "function <b>{$bk['function']}($args)</b>";
        }
        $firstcaller .= "$classfunc<br> in <b>{$bk['file']}</b><br>".
                        " on line <b>{$bk['line']}</b><br>\n";
      }
    }
    $cwd = getcwd();
    
    $error = <<<EOF
<p>&quot;<i>$msg</i>&quot;<br>
error=&quot;<i>$Error</i>&quot;, \$Errno=&quot;<i>$Errno</i>&quot;<br>
cwd=$cwd<br>
called from <strong>{$caller['file']}</strong><br> on line <strong>{$caller['line']}</strong><br>
EOF;

    if(isset($firstcaller)) {
      $error .= "Back Trace:<br>\n$firstcaller";
    }

    // this is the message and code to pass to Exception.
    
    return array($error, $Errno);
  }

  public static function getVersion() {
    return SQLEXCEPTION_CLASS_VERSION;
  }
} // End SqlException Class
