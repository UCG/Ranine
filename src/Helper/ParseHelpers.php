<?php

declare(strict_types = 1);

namespace Ranine\Helper;

use Ranine\Exception\ParseException;
use Ranine\Iteration\ExtendableIterable;

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
   * If $number is an integer, this function returns $number. If it is a string
   * the method used in parseIntFromString($number) is used to parse $number. If
   * it is neither, the parse operation fails.
   *
   * @param mixed $number
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
   * Attempts to parse $number as an integer.
   *
   * This function casts $number to an integer and checks to ensure it matches
   * the "canonical" representation of that integer formed by casting the
   * integer back to a string. If the match succeeds, the casted version of
   * $number is returned.
   *
   * @return int
   *   Result of parse operation.
   *
   * @throws \Ranine\Exception\ParseException
   *   Thrown if the parse operation fails.
   */
  public static function parseIntFromString(string $number) : int {
    $result = 0;
    if (!static::tryParseInt($number, $result)) {
      throw new ParseException('Could not parse as integer.');
    }
    return $result;
  }

  /**
   * Attempts to parse $range as an inclusive range of integer values.
   *
   * @param string $range
   *   Range, which should be in the form "[start]$divider[end]", where [start]
   *   and [end] are string representations of integers which form the inclusive
   *   lower and upper bounds of the range, respectively.
   * @param string $divider
   *   The string dividing the two halves of the range.
   *
   * @return \Ranine\Iteration\ExtendableIterable
   *   Sorted collection, whose values are the values in the range.
   *
   * @throws \InvalidArgumentException
   *   Thrown if $divider is empty.
   * @throws \Ranine\Exception\ParseException
   *   Thrown if the parsing failed.
   */
  public static function parseIntRange(string $range, string $divider = '-') : ExtendableIterable {
    $result = NULL;
    if (!static::tryParseIntRange($range, $result, $divider)) {
      throw new ParseException('Could not parse integer range.');
    }
    return $result;
  }

  /**
   * Attempts to get the integer endpoints of $range.
   *
   * @param string $range
   *   Range, which should be in the form "[start]$divider[end]", where [start]
   *   and [end] are string representations of integers which form the start and
   *   end of the range, respectively.
   * @param int &start
   *   Start of range.
   * @param int &end
   *   End of range.
   * @param string $divider
   *   The string dividing the two halves of the range.
   *
   * @throws \InvalidArgumentException
   *   Thrown if $divider is empty.
   * @throws \Ranine\Exception\ParseException
   *   Thrown if the parsing failed.
   */
  public static function parseIntRangeEndpoints(string $range, int &$start, int &$end, string $divider = '-') : void {
    $start = 0;
    $end = 0;
    if (!static::tryParseIntRangeEndpoints($range, $start, $end, $divider)) {
      throw new ParseException('Could not parse integer range.');
    }
  }

  /**
   * Attempts to parse $number (a string or integer) as an integer.
   *
   * If $number is an integer, this function sets $result = $number. If it is a
   * string, tryParseIntFromString($number, $result) is used to parse $number.
   * If it is neither, the parse operation fails.
   *
   * @param mixed $number
   * @param int &$result
   *   Result of parse operation (undefined if operation failed).
   *
   * @return bool
   *   Returns TRUE if the parse succeeded; else returns FALSE.
   */
  public static function tryParseInt($number, int &$result) : bool {
    if (is_int($number)) {
      $result = $number;
      return TRUE;
    }
    elseif (is_string($number)) {
      return static::tryParseIntFromString($number, $result);
    }
    else {
      return FALSE;
    }
  }

  /**
   * Attempts to parse $number as an integer.
   *
   * This function casts $number to an integer and checks to ensure it matches
   * the "canonical" representation of that integer formed by casting the
   * integer back to a string. If the match succeeds, $result is set to the
   * casted version of $number and the function returns TRUE. If the match
   * fails, this function returns FALSE.
   *
   * @param int &$result
   *   Result of parse operation (undefined if operation failed).
   *
   * @return bool
   *   Returns TRUE if parse operation succeeds; else returns FALSE.
   */
  public static function tryParseIntFromString(string $number, int &$result) : bool {
    $result = (int) $number;
    return (((string) $result) === $number) ? TRUE : FALSE;
  }

  /**
   * Attempts to parse $range as an inclusive range of integer values.
   *
   * @param string $range
   *   Range, which should be in the form "[start]$divider[end]", where [start]
   *   and [end] are string representations of integers which form the inclusive
   *   lower and upper bounds of the range, respectively.
   * @param \Ranine\Iteration\ExtendableIterable|null &$output
   *   Collection, whose values are sorted from lowest to highest and are the
   *   values in the range, or NULL if the parsing failed.
   * @param string $divider
   *   The string dividing the two halves of the range.
   *
   * @return bool
   *   Returns TRUE if the parse succeeded; else returns FALSE.
   *
   * @throws \InvalidArgumentException
   *   Thrown if $divider is empty.
   */
  public static function tryParseIntRange(string $range, ?ExtendableIterable &$output, string $divider = '-') : bool {
    $output = NULL;
    $start = 0;
    $end = 0;

    if (static::tryParseIntRangeEndpoints($range, $start, $end, $divider)) {
      $output = ExtendableIterable::fromRange($start, $end);
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

  /**
   * Attempts to get the integer endpoints of $range.
   *
   * @param string $range
   *   Range, which should be in the form "[start]$divider[end]", where [start]
   *   and [end] are string representations of integers which form the start and
   *   end of the range, respectively.
   * @param int &start
   *   Start of range.
   * @param int &end
   *   End of range.
   * @param string $divider
   *   The string dividing the two halves of the range.
   *
   * @return bool
   *   Returns TRUE if the parse succeeded; else returns FALSE.
   *
   * @throws \InvalidArgumentException
   *   Thrown if $divider is empty.
   */
  public static function tryParseIntRangeEndpoints(string $range, int &$start, int &$end, string $divider = '-') : bool {
    ThrowHelpers::throwIfEmptyString($divider, 'divider');

    if ($range === '') {
      return FALSE;
    }
    $rangeParts = explode($divider, $range, 2);
    if (!is_array($rangeParts) || count($rangeParts) !== 2) {
      return FALSE;
    }
    /** @var string[] $rangeParts */
    if (!static::tryParseIntFromString($rangeParts[0], $start)) {
      return FALSE;
    }
    if (!static::tryParseIntFromString($rangeParts[1], $end)) {
      return FALSE;
    }
    if ($end < $start) {
      return FALSE;
    }

    return TRUE;
  }

}
