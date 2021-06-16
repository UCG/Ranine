<?php

declare(strict_types = 1);

namespace Ranine\Helper;

/**
 * Static helper methods and constants to deal with strings.
 *
 * @static
 */
final class StringHelpers {

  /**
   * ASCII group separator character string.
   */
  public const ASCII_GROUP_SEPARATOR = "\x1D";

  /**
   * ASCII record separator character string.
   */
  public const ASCII_RECORD_SEPARATOR = "\x1E";

  /**
   * ASCII unit separator character string.
   */
  public const ASCII_UNIT_SEPARATOR = "\x1F";

  /**
   * Empty private constructor to ensure no one instantiates this class.
   */
  private function __construct() {
  }

  /**
   * Assembles $items into a single string with $separator between values.
   *
   * The separator is escaped from all items using the ASCII ESC character,
   * which is also used to escape instances of itself.
   *
   * @return string
   *   Assembled strings, with neither a leading nor a trailing separator.
   *
   * @throws \InvalidArgumentException
   *   Thrown if $separator is not of unit length.
   */
  public static function assemble(string $separator, string ...$items) : string {
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
   * @param string[] $otherSpecialCharacters
   *   Special characters to escape; each should be a string of unit length.
   * @param string $escapeCharacter
   *   Single-length escape character.
   *
   * @throws \InvalidArgumentException
   *   Thrown if $escapeCharacter is not of unit length, or if an element in
   *   $otherSpecialCharacters is not a string of length one.
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
    foreach ($otherSpecialCharacters as $key => $char) {
      if (!is_string($char) || strlen($char) !== 1) {
        throw new \InvalidArgumentException('An element of $otherSpecialCharacters is not a string of unit length.');
      }
      $replaceSequences[$key] = ($escapeCharacter . $char);
    }
    return str_replace($otherSpecialCharacters, $replaceSequences, $intermediateResult);
  }

  /**
   * Gets $str, or returns $defaultMessage if $str is NULL or empty.
   */
  public static function getValueOrDefault(?string $str, ?string $defaultMessage) : ?string {
    return static::isNullOrEmpty($str) ? $defaultMessage : $str;
  }

  /**
   * If $str is an empty string, converts it to NULL.
   *
   * @return string|null
   *   Returns $str if $str !== ""; otherwise, returns NULL.
   */
  public static function emptyToNull(?string $str) : ?string {
    return ($str === '') ? NULL : $str;
  }

  /**
   * Tells whether $value is a non-empty string.
   */
  public static function isNonEmptyString($value) : bool {
    return (is_string($value) && $value !== '') ? TRUE : FALSE;
  }

  /**
   * Tells whether $str is either NULL or an empty string.
   */
  public static function isNullOrEmpty(?string $str) : bool {
    return ($str === NULL || $str === '') ? TRUE : FALSE;
  }

}
