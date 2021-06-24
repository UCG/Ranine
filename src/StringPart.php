<?php

declare(strict_types = 1);

namespace Ranine;

use Ranine\Helper\ThrowHelpers;

/**
 * Represents a part of a string, with a defined beginning and end index.
 */
class StringPart {

  /**
   * Backing string.
   */
  private string $backingString;

  /**
   * Exclusive end position of string part.
   *
   * Will be 0 to specify an empty string.
   */
  private int $endPositionExclusive;

  /**
   * Inclusive start position of string part.
   *
   * Will be -1 to specify an empty string.
   */
  private int $startPositionInclusive;

  /**
   * Creates a new string part.
   *
   * The string part is the substring of $backingString spanning the range of
   * intervals [$startPosition, $endPosition], unless $backingString is empty,
   * in which case the string part is also empty.
   *
   * @param string $backingString
   *   Backing string.
   * @param int $startPosition
   *   Start position (inclusive). Should be -1 to specify an empty string.
   * @param int $endPosition
   *   End position (inclusive). Should be -1 to specify an empty string.
   *
   * @throws \InvalidArgumentException
   *   If $startPosition is -1, thrown if $endPosition is not -1.
   * @throws \InvalidArgumentException
   *   If $startPosition is not -1, thrown if either $startPosition or
   *   $endPosition is less than zero.
   * @throws \InvalidArgumentException
   *   Thrown if $startPosition is greater than $endPosition.
   * @throws \InvalidArgumentException
   *   Thrown if $endPosition is greater than or equal to the length of
   *   $backingString.
   */
  public function __construct(string $backingString = '', int $startPosition = -1, int $endPosition = -1) {
    if ($startPosition === -1) {
      if ($endPosition !== -1) {
        throw new \InvalidArgumentException('$endPosition is not negative one, even though $startPosition is negative one.');
      }
    }
    else {
      if ($startPosition < -1) {
        throw new \InvalidArgumentException('$startPosition is less than negative one.');
      }
      if ($endPosition < 0) {
        throw new \InvalidArgumentException('$endPosition is less than zero when $startPosition is not negative one.');
      }
      if ($startPosition > $endPosition) {
        throw new \InvalidArgumentException('$startPosition is greater than $endPosition.');
      }
      if ($endPosition >= strlen($backingString)) {
        throw new \InvalidArgumentException('$endPosition is greater than or equal to the length of $backingString.');
      }
    }

    $this->backingString = $backingString;
    $this->endPositionExclusive = $endPosition + 1;
    $this->startPositionInclusive = $startPosition;
  }

  /**
   * Appends $str to the end of this string part and returns this object.
   *
   * @param string $str
   *   String to append.
   */
  public function append(string $str) : StringPart {
    if ($this->isEmpty() && $str !== '') {
      $this->backingString = $str;
      $this->startPositionInclusive = 0;
      $this->endPositionExclusive = strlen($str);
    }
    elseif ($this->endPositionExclusive === strlen($this->backingString)) {
      $this->backingString .= $str;
      $this->endPositionExclusive += strlen($str);
    }
    else {
      $this->backingString = substr($str, $this->startPositionInclusive, $this->getLength()) . $str;
      $this->startPositionInclusive = 0;
      $this->endPositionExclusive = strlen($this->backingString);
    }

    return $this;
  }

  /**
   * Makes the backing string of minimal length and returns this object.
   *
   * This method makes the backing string of this object into a substring of
   * itself of the smallest possible length.
   */
  public function clean() : StringPart {
    if ($this->isEmpty()) {
      $this->backingString = '';
    }
    else {
      $this->backingString = substr($this->backingString, $this->startPositionInclusive, $this->getLength());
      $this->startPositionInclusive = 0;
      $this->endPositionExclusive = strlen($this->backingString);
    }

    return $this;
  }

  /**
   * Renders this string part empty and returns this object.
   */
  public function clear() : StringPart {
    $this->backingString = '';
    $this->startPositionInclusive = -1;
    $this->endPositionExclusive = 0;

    return $this;
  }

  /**
   * Sets the current object equal to a substring of the current string part.
   *
   * This method changes the current object so that it points to a substring of
   * the current string part, from $startPosition to $endPosition (or the end of
   * the string part).
   *
   * @param int $startPosition
   *   Inclusive start position (relative to the string part, not the backing
   *   string).
   * @param int|null $endPosition
   *   Inclusive end position (relative to the string part, not the backing
   *   string), or 'NULL' to take a substring to the end of the string.
   *
   * @return StringPart
   *   Current object.
   *
   * @throws \InvalidArgumentException
   *   Thrown if $startPosition is greater than $endPosition
   * @throws \InvalidArgumentException
   *   Thrown if $startPosition or $endPosition is greater than or equal to the
   *   length of this string part.
   * @throws \InvalidArgumentException
   *   Thrown if $startPosition or $endPosition is less than zero.
   */
  public function cut(int $startPosition, ?int $endPosition = NULL) : StringPart {
    $absoluteStartPositionInclusive = 0;
    $absoluteEndPositionInclusive = 0;
    $this->validateAndProcessSubstringArguments($startPosition, $endPosition, $absoluteStartPositionInclusive, $absoluteEndPositionInclusive); 
    $this->startPositionInclusive = $absoluteStartPositionInclusive;
    $this->endPositionExclusive = $absoluteEndPositionInclusive;

    return $this;
  }

  /**
   * Gets the string backing this string part.
   */
  public function getBackingString() : string {
    return $this->backingString;
  }

  /**
   * Gets the end position of the string part, relative to the backing string.
   *
   * @return int
   *   Inclusive end position, or -1 if the string is empty.
   */
  public function getEndPosition() : int {
    return $this->endPositionExclusive - 1;
  }

  /**
   * Gets the length of this string part.
   */
  public function getLength() : int {
    if ($this->isEmpty()) {
      return 0;
    }
    else {
      return $this->endPositionExclusive - $this->startPositionInclusive;
    }
  }

  /**
   * Gets the start position of the string part, relative to the backing string.
   *
   * @return int
   *   Inclusive start position, or -1 if the string is empty.
   */
  public function getStartPosition() : int {
    return $this->startPositionInclusive;
  }

  /**
   * Tells whether this string part is empty.
   */
  public function isEmpty() : bool {
    return ($this->startPositionInclusive === -1) ? TRUE : FALSE;
  }

  /**
   * Takes a substring from a start position to (opt) an end position.
   *
   * @param int $startPosition
   *   Inclusive start position (relative to the string part, not the backing
   *   string).
   * @param int|null $endPosition
   *   Inclusive end position (relative to the string part, not the backing
   *   string), or 'NULL' to take a substring to the end of the string.
   *
   * @return StringPart
   *   The resulting new string part (the current object is not modified).
   *
   * @throws \InvalidArgumentException
   *   Thrown if $startPosition is greater than $endPosition
   * @throws \InvalidArgumentException
   *   Thrown if $startPosition or $endPosition is greater than or equal to the
   *   length of this string part.
   * @throws \InvalidArgumentException
   *   Thrown if $startPosition or $endPosition is less than zero.
   */
  public function substring(int $startPosition, ?int $endPosition = NULL) : StringPart {
    $absoluteStartPositionInclusive = 0;
    $absoluteEndPositionInclusive = 0;
    $this->validateAndProcessSubstringArguments($startPosition, $endPosition, $absoluteStartPositionInclusive, $absoluteEndPositionInclusive); 
    return new StringPart($this->backingString, $absoluteStartPositionInclusive, $absoluteEndPositionInclusive);
  }

  /**
   * Validates and processes the given args of the substring()/cut() methods.
   *
   * @param int $startPosition
   *   Inclusive start position (relative to the string part, not the backing
   *   string).
   * @param int|null $endPosition
   *   Inclusive end position (relative to the string part, not the backing
   *   string), or 'NULL' to take a substring to the end of the string.
   * @param int $absoluteStartPositionInclusive
   *   The resulting absolute (relative to the backing string) inclusive start
   *   position.
   * @param int $absoluteEndPositionInclusive
   *   The resulting absolute (relative to the backing string) end position.
   *
   * @throws \InvalidArgumentException
   *   Thrown if $startPosition is greater than $endPosition
   * @throws \InvalidArgumentException
   *   Thrown if $startPosition or $endPosition is greater than or equal to the
   *   length of this string part.
   * @throws \InvalidArgumentException
   *   Thrown if $startPosition or $endPosition is less than zero.
   */
  private function validateAndProcessSubstringArguments(int $startPosition, ?int $endPosition = NULL, int &$absoluteStartPositionInclusive, int &$absoluteEndPositionInclusive) {
    ThrowHelpers::throwIfLessThanZero($startPosition, 'startPosition');
    $absoluteStartPositionInclusive = $startPosition + $this->startPositionInclusive;

    if ($endPosition === NULL) {
      if ($absoluteStartPositionInclusive >= $this->endPositionExclusive) {
        throw new \InvalidArgumentException('$startPosition is greater than the length of this string part.');
      }
      $absoluteEndPositionInclusive = $this->endPositionExclusive - 1;
    }
    else {
      /** @var int $endPosition */
      ThrowHelpers::throwIfLessThanZero($endPosition, 'endPosition');
      if ($startPosition > $endPosition) {
        throw new \InvalidArgumentException('$startPosition is greater than $endPosition.');
      }
      $absoluteEndPositionInclusive = $endPosition + $this->startPositionInclusive;
      if ($absoluteEndPositionInclusive >= $this->endPositionExclusive) {
        throw new \InvalidArgumentException('$endPosition is greater than the length of this string part.');
      }
    }
  }

}
