<?php
/**
 * @package SqlException
 * Extends Exception
 *   message, code, file and line are members of Exception
 * The Error class provides the following properties to control output:
 */

class SqlException extends Exception {
  /**
   * Constructor
   * @param string $message: text which tells what went wrong
   * @param object $self: this is the '$this' of the caller.
   */
  
  public function __construct($message, $self=null) {
    // If the caller was a database class then $this->db should be the database resorce.
    
    list($html, $errno) = $this->SqlError($message, $self); // private helper method

    parent::__construct($html, $errno);
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
   * @param bool $echo if true we do an echo else return string
   * @return array([html error text], [error number]);
   */

  private function SqlError($msg="NO MESSAGE PROVIDED", $self) {
    $Error = "NO ERROR MESSAGE FOUND";
    $Errno = -1;

    // BLP 2021-12-31 -- remove $errDb
    
    if(is_null($self) || is_null($self->getDb()) || $self->getDb() === 0) {
      if(isset($self->errno) && isset($self->error)) {
        $Errno = $self->errno;
        $Error = $self->error;
      } else {
        $Errno = -9999;
        $Error = "No valid \$self->errno or \$self->error.";
      }
    } else {
      if(method_exists($self, 'getErrorInfo')) {
        $err = $self->getErrorInfo(); // from the database engine, like mysqli etc.
        $Error = $err['error'];
        $Errno = $err['errno'];
      } else {
        throw(new Exception("SqlException ".__LINE__. ": method getErrorInfo missing"));
      }
    }

    if(($size = strlen($msg)) > 500) $msg = "Message Too Long: $size";
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

    return array($error, $Errno);
  }
} // End SqlException Class
