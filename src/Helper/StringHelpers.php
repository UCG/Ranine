<?php

declare(strict_types = 1);

namespace Ranine\Helper;

/**
 * Static helper methods to deal with strings.
 *
 * @static
 */
final class StringHelpers {

  public const ASCII_GROUP_SEPARATOR = "0x1D";

  /**
   * ASCII record separator character string.
   */
  public const ASCII_RECORD_SEPARATOR = "0x1E";

  /**
   * ASCII unit separator character string.
   */
  public const ASCII_UNIT_SEPARATOR = "0x1F";

  /**
   * Empty private constructor to ensure no one instantiates this class.
   */
  private function __construct() {
  }

  /**
   * Assembles $items into a single string with $separator between values.
   *
   * There is no terminating, and no loading separator. Separator is escaped
   * from all items using the ASCII ESC character.
   *
   * @param string $separator
   *   Separator.
   * @param string ...$items
   *   Strings to assemble.
   *
   * @throws \InvalidArgumentException
   *   Thrown if $separator is not of unit length.
   */
  public static function assemble(string $separator, string ...$items) {
    if (strlen($separator) !== 1) {
      throw new \InvalidArgumentException('$separator is not of unit length.');
    }

    $output = '';
    $isFirstIteration = TRUE;
    foreach ($items as $item) {
      if (!$isFirstIteration) {
        $output .= $separator;
      }
      $output .= static::escape($item, [$separator], "\e");
      $isFirstIteration = FALSE;
    }

    return $output;
  }

  /**
   * Escapes $str.
   * 
   * Escapes, with $escapeCharacter, all instances of $escapeCharacter and every
   * character in $otherSpecialCharacters.
   *
   * @param string $str
   *   String to escape.
   * @param string[] $otherSpecialCharacters
   *   Special characters to escape; each should be a string of unit length.
   * @param string $escapeCharacter
   *   Single-length escape character.
   *
   * @throws \InvalidArgumentException
   *   Thrown if $escapeCharacter is not of unit length, or if an element in
   *   $otherSpecialCharacters is not an array of length one.
   */
  public static function escape(string $str, array $otherSpecialCharacters, string $escapeCharacter = "\e") {
    if (strlen($escapeCharacter) !== 1) {
      throw new \InvalidArgumentException('$escapeCharacter is not of unit length.');
    }

    // Escape the escape character.
    $intermediateResult = str_replace($escapeCharacter, $escapeCharacter . $escapeCharacter, $str);

    // Escape everything else.
    /** @var string[] */
    $replaceSequences = [];
    foreach ($otherSpecialCharacters as $char) {
      if (!is_string($char) || strlen($char) !== 1) {
        throw new \InvalidArgumentException('$char is not of unit length.');
      }
      $replaceSequences[] = ($escapeCharacter . $char);
    }
    return str_replace($otherSpecialCharacters, $replaceSequences, $intermediateResult);
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
