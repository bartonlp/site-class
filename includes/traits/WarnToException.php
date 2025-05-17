<?php

namespace bartonlp\SiteClass;

/**
 * Trait: WarningToExceptionHandler
 * Allows selective conversion of PHP warnings to exceptions,
 * based on registered function names that typically emit E_WARNING.
 *
 * BLP 2025-04-18
 */

trait WarningToExceptionHandler {
  protected array $exceptionTriggers = [];
  protected bool $handlerRegistered = false;

  /**
   * Register specific function names whose warnings should become exceptions.
   *
   * @param array $functions List of function name substrings to match against warning messages.
   */
  
  public function registerWarningHandlers(array $functions): void {
    $this->exceptionTriggers = $functions;

    if(!$this->handlerRegistered) {
      set_error_handler([$this, 'warningToExceptionHandler']);
      $this->handlerRegistered = true;
    }
  }

  /**
   * Restore the original PHP error handler.
   */

  public function restoreWarningHandler(): void {
    if($this->handlerRegistered) {
      restore_error_handler();
      $this->handlerRegistered = false;
    }
  }

  /**
   * Internal error handler callback that promotes select warnings to exceptions.
   */

  public function warningToExceptionHandler(int $errno, string $errstr, string $errfile, int $errline): bool {
    if($errno === \E_WARNING) {
      foreach($this->exceptionTriggers as $fn) {
        if(str_contains($errstr, $fn)) {
          throw new \Exception($errstr);
        }
      }
    }
    return false; // Let all other warnings behave normally
  }
}
