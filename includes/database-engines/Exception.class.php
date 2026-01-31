<?php
// Exception Class.
// Handle all exceptions and errors
// Set the namespace the same as in includes/siteload.php
// NOTE: this means that all non namespace item must have a \ prefix!

namespace bartonlp\siteload;
use SendGrid\Mail\Mail;
use bartonlp\SiteClass\dbPdo;

define("EXCEPTION_VERSION", "2.0.0exception");

class SiteExceptionHandler {
  private static bool $initialized = false;
  
  /*
   * init. Initialize my exception handler
   */
  public static function init(): void {
    if(self::$initialized) {
      return;
    }

    // Set the exception handler and the shutdown handler
    
    set_exception_handler([self::class, 'my_exceptionhandler']);
    register_shutdown_function([self::class, 'shutdownHandler']);

    // Don't do this ever again.
    
    self::$initialized = true;
  }

  /*
   * handle. When the system shuts down, handle any outstanding errors.
   */
  public static function shutdownHandler(): void {
    $error = error_get_last();

    if($error && $error['type'] === E_ERROR) {
      // Handle fatal error gracefully

      error_log("shutdownHandler FATAL:  msg={$error['message']}, file={$error['file']} ,line={$error['line']}");

      if(php_sapi_name() === 'cli') {
        // Terminal output
        echo "FATAL ERROR: {$error['message']} in {$error['file']} on line {$error['line']}\n";
      } else {
        // Browser output
        if(!headers_sent()) {
          http_response_code(500);
          header('Content-Type: text/html; charset=utf-8');
        }

        if(\ErrorClass::getNoHtml() === true) {
          echo "FATAL ERROR: {$error['message']} in {$error['file']} on line {$error['line']}";
        } else {
          echo <<<EOF
<div style="text-align: center; background-color: white; border: 1px solid black; width: 85%; margin: auto auto; padding: 10px;">
<h1 style="color: red">Fatal Error</h1>
<p>{$error['message']}<br>in {$error['file']} on line {$error['line']}</p>
</div>
EOF;
        }
      }
    }
  }

  /*
   * my_exceptionhandler. Handle exceptions
   * @param: Throwable $e. Exception or Error
   * @return: void
   */
  public static function my_exceptionhandler(\Throwable $e): void {
    if (php_sapi_name() !== 'cli' && ob_get_level() > 0) {
      ob_clean();
    }

    $errorType = $e instanceof \Error
                 ? 'Runtime Error'
                 : ($e instanceof \Exception ? 'Exception' : 'Throwable');

    // Get dbPdo::$lastQuery

    $last = $param = null;

    if(\ErrorClass::getNoLastQuery() !== true && class_exists('dbPdo', false)) {
      $last  = dbPdo::$lastQuery ?? null;
      $param = dbPdo::$lastParam ?? null;
    }
    
    // Callback to get the user ID if the callback exists

    $userId = '';

    if(\ErrorClass::getNoErrorId() !== true) {
      if(function_exists('ErrorGetId')) {
        $userId = "User: " . \ErrorGetId();
      }

      if(!$userId) {
        // This is the same default userId that ErrorGetId() would return

        $ip    = $_SERVER['REMOTE_ADDR']    ?? 'unknown';
        $agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';

        $userId = "ip=$ip, agent=$agent";
      }
    }

    if(\ErrorClass::getNoBackTrace() !== true) {
      $stackTrace = self::formatStackTrace($e);
    }

    $paramStr = $param ? "lastParam=$param\n" : null;

    $error = "{$e->getMessage()} in {$e->getFile()} on line {$e->getLine()},".
             "{$stackTrace}lastQuery=$last\n{$paramStr}$userId";

    // $err is for CLI
    
    $err = preg_replace("~\\n~", ", ", $error);
    
    // Should we send an email?

    if(\ErrorClass::getNoEmail() !== true) {
      $s = $GLOBALS["_site"];
      error_log("SENDGRID");
      $email = new Mail();

      $email->setFrom("ErrorMessage@bartonphillips.com");
      $email->setSubject($errorType);
      $email->addTo($s->EMAILADDRESS);

      $contents = preg_replace(["~\"~", "~\\n~"], ['','<br>'], "$error<br>lastQuery: $last<br>$userId");

      $email->addContent("text/plain", $contents); // BLP 2025-02-19 - 

      $email->addContent("text/html", $contents);

      $apiKey = require "/var/www/PASSWORDS/sendgrid-api-key";
      $sendgrid = new \SendGrid($apiKey);

      try {
        $response = $sendgrid->send($email);

        // Add $resp and use it below. I had in error_log $response->statusCode()
        // instead of response and that caused an error.
      
        if(($resp = $response->statusCode()) > 299) {
          error_log("Exception.class: sendgrid error, $resp, response header: " . print_r($response->headers()));
        }
      } catch(\Throwable $mailEx) {
        error_log("Exception.class: email send failed: " . $mailEx->getMessage());
      }
    }

    // Log the raw error info.
    // This error_log should always stay in!! *****************
    error_log("Exception.class $errorType: $err");
    // ********************************************************

    // Are we in development mode?
    
    if(\ErrorClass::getDevelopment() !== true) {
      // Not in development mod. Minimal error message
      $displayError = <<<EOF
<p>The webmaster has been notified of this error and it should be fixed shortly. Please try again in a couple of hours.</p>
EOF;
    }

    // Should we send HTML output?
    
    if(\ErrorClass::getNoHtml() === true) {
      // No HTML
      
      $displayError = "$errorType: $err"; // No HTML means /n only
    } else {
      // Yes send full HTML
      $displayError = <<<EOF
<style>
@font-face {
  font-family: 'FontFace'; /* This my custome name for the font-family. */
  src: url(https://bartonphillips.net/fonts/ibm-plex-otf/IBMPlexSans-Regular.otf) format('opentype');
}
.error_message {
  margin: 2em auto;
  padding: 1em;
  max-width: 90vw;
  width: 100%;
  background: #f8f8f8;
  border: 1px solid #ccc;
  border-radius: 8px;
  font-family: 'FontFace', sans-serif;
  overflow-x: auto;
}
.error_message h1 {
  text-align: center;
  color: red;
  font-size: 2em;
}
.error_message pre {
  white-space: pre-wrap;
  word-break: break-word;
  font-size: 1.5em;
  color: #333;
}
</style>
        
<div class="error_message">
<h1>$errorType</h1>
<pre>
$err
</pre>
</div>
EOF;
    }

    // Should we output this message to the screen?
    
    if(\ErrorClass::getNoOutput() !== true) {
      // Yes, output it.
      
      //************************
      // Don't remove this echo
      if(php_sapi_name() === 'cli') {
        $displayError = $err; // without all the html
      }
      echo $displayError; // on CLI this outputs to the console, on apache it goes to the client screen.
      //***********************
    }
    return;
  }

  /*
   * formatStackTrace. Do a stack trace
   * @param: Throwable $e.
   * @return: string. The full trace formated.
   */
  private static function formatStackTrace(\Throwable $e): string {
    $trace = $e->getTrace();
    $output = '';

    foreach($trace as $index => $frame) {
      $func = $frame['function'] ?? 'unknown_function';
      $class = $frame['class'] ?? '';
      $type = $frame['type'] ?? '';
      $file = $frame['file'] ?? '[internal]';
      $line = $frame['line'] ?? '?';

      $args = '';
      if(!empty($frame['args'])) {
        $argsList = [];
        foreach($frame['args'] as $arg) {
          if(is_object($arg)) {
            $argsList[] = 'Object(' . get_class($arg) . ')';
          } elseif(is_array($arg)) {
            $argsList[] = 'Array(' . count($arg) . ')';
          } elseif(is_string($arg)) {
            $argsList[] = "'" . (strlen($arg) > 20 ? substr($arg, 0, 17) . '...' : $arg) . "'";
          } elseif(is_null($arg)) {
            $argsList[] = 'NULL';
          } elseif(is_bool($arg)) {
            $argsList[] = $arg ? 'true' : 'false';
          } else {
            $argsList[] = (string) $arg;
          }
        }
        $args = implode(', ', $argsList);
      }

      $output .= sprintf("#%d %s%s%s(%s) called at [%s:%s]\n",
                         $index,
                         $class,
                         $type,
                         $func,
                         $args,
                         $file,
                         $line
                        );
    }

    return $output;
  }
}
