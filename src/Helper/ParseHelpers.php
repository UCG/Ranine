<?php

declare(strict_types = 1);

namespace Ranine\Helper;

use Ranine\Exception\ParseException;

/**
 * Static helper methods to deal with parsing.
 *
 * @static
 */
final class ParseHelpers {

  /**
   * Empty private constructor to ensure no one instantiates this class.
   */
  private function __construct() {
  }

  /**
   * Attempts to parse $number (a string or integer) as an integer.
   *
   * If $number is an integer, this function sets $result = $number. If it is a
   * string, this function casts $number to an integer and checks to ensure it
   * matches the "canonical" representation of that integer formed by casting
   * the integer back to a string. If the match succeeds, $result is set to the
   * casted version of $number. If $number is not a string nor an integer, or
   * if the match fails, this function throws an exception.
   *
   * @param mixed $number
   *   Number to parse.
   *
   * @return int
   *   Result of parse operation.
   *
   * @throws \Ranine\Exception\ParseException
   *   Thrown if the parse operation fails.
   */
  public static function parseInt($number) : int {
    $result = 0;
    if (!static::tryParseInt($number, $result)) {
      throw new ParseException('Could not parse as integer.');
    }
    return $result;
  }

  /**
   * Attempts to parse $number (a string or integer) as an integer.
   *
   * If $number is an integer, this function sets $result = $number. If it is a
   * string, this function casts $number to an integer and checks to ensure it
   * matches the "canonical" representation of that integer formed by casting
   * the integer back to a string. If the match succeeds, $result is set to the
   * casted version of $number. If $number is not a string nor an integer, or
   * if the match fails, this function returns 'FALSE'.
   *
   * @param mixed $number
   *   Number to parse.
   * @param int &$result
   *   Result of parse operation (undefined if operation failed).
   *
   * @return bool
   *   Returns 'TRUE' if the parse succeeded; else returns 'FALSE'.
   */
  public static function tryParseInt($number, int &$result) : bool {
    if (is_int($number)) {
      return $number;
    }
    else if (is_string($number)) {
      $result = (int) $number;
      if (((string) $result) === $number) {
        return TRUE;
      }
    }

    return FALSE;
  }

}