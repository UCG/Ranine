<?php

declare (strict_types = 1);

namespace Ranine\Helper;

/**
 * Contains methods related to exceptions.
 *
 * @static
 *
 * Does not contain methods dedicated to throwing exceptions -- those are found
 * in @see \Ranine\Helper\ThrowHelpers.
 */
final class ExceptionHelpers {

  /**
   * Empty private constructor to ensure no one instantiates this class.
   */
  private function __construct() {
  }

  /**
   * Chains together the messages for $e and its previous exceptions.
   *
   * @param \Exception $e
   *   Exception for which to chain messages.
   * @param string $separator
   *   Separator to place between exception messages.
   *
   * @return string
   *   Exception messages of $e and its previous exceptions, separated by
   *   $separator.
   */
  public static function getExceptionChainMessages(\Exception $e, string $separator = "\n") : string {
    $messages = $e->getMessage();
    $current = $e;
    while (($current = $current->getPrevious()) !== NULL) {
      $messages .= ($current->getMessage() . $separator);
    }
    return $messages;
  }

}
