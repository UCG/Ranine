<?php

declare(strict_types = 1);

namespace Ranine\Helper;

/**
 * Static helper methods to deal with strings.
 *
 * @static
 */
final class StringHelpers {

  /**
   * Empty private constructor to ensure no one instantiates this class.
   */
  private function __construct() {
  }

  /**
   * Get $str, or a default message if $str is NULL or empty.
   *
   * If $str is NULL or empty, returns $defaultMessage; else, returns $str.
   */
  public static function getValueOrDefault(?string $str, ?string $defaultMessage) : ?string {
    return static::isNullOrEmpty($str) ? $defaultMessage : $str;
  }

  /**
   * If $str is an empty string, converts it to 'NULL'.
   *
   * @return string|null
   *   Returns $str if $str !== ""; otherwise, returns 'NULL'.
   */
  public static function emptyToNull(?string $str) : ?string {
    return ($str === '') ? NULL : $str;
  }

  /**
   * Checks if $value is a non-empty string.
   *
   * @return bool
   *   Returns 'TRUE' if $value is a non-empty string, else returns 'FALSE'.
   */
  public static function isNonEmptyString($value) : bool {
    return (is_string($value) && $value !== '') ? TRUE : FALSE;
  }

  /**
   * Checks if $str is either 'NULL' or an empty string.
   *
   * @return bool
   *   'TRUE' if $str is 'NULL' or empty string, else 'FALSE'.
   */
  public static function isNullOrEmpty(?string $str) : bool {
    return ($str === NULL || $str === '') ? TRUE : FALSE;
  }

}
