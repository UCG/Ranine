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
   * @param string $backingString
   *   Backing string.
   * @param int $startPositionInclusive
   *   Start position (inclusive). Should be -1 to specify an empty string.
   * @param int $endPositionExclusive
   *   End position (exclusive). Should be 0 to specify an empty string.
   */
  protected function __construct(string $backingString = '', int $startPositionInclusive = -1, int $endPositionExclusive = 0) {
    $this->backingString = $backingString;
    $this->endPositionExclusive = $endPositionExclusive + 1;
    $this->startPositionInclusive = $startPositionInclusive;
  }

  /**
   * Gets the string representation of this string part.
   */
  public function __toString() : string {
    if ($this->isEmpty()) {
      return '';
    }
    elseif ($this->startPositionInclusive !== 0 || strlen($this->backingString) !== $this->endPositionExclusive) {
      return substr($this->backingString, $this->startPositionInclusive, $this->getLength());
    }
    else {
      return $this->backingString;
    }
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
      $this->backingString = ((string) $this) . $str;
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
    $this->backingString = (string) $this;
    if ($this->backingString !== '') {
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
   * Changes the current string part's start and/or end positions.
   *
   * @param int $startPosition
   *   Inclusive start position, relative to the backing string. Must be -1 if
   *   the backing string is empty.
   * @param int $endPosition
   *   Inclusive end position, relative to the backing string. Must be -1 if the
   *   backing string is empty.
   *
   * @return static
   *   Current object.
   *
   * @throws \InvalidArgumentException
   *   If $startPosition is -1, thrown if $endPosition is not -1.
   * @throws \InvalidArgumentException
   *   If $startPosition is not -1, thrown if either $startPosition or
   *   $endPosition is less than zero.
   * @throws \InvalidArgumentException
   *   Thrown if $startPosition is greater than $endPosition.
   * @throws \InvalidArgumentException
   *   Thrown if $endPosition is greater than or equal to the length of the
   *   backing string.
   */
  public function recut(int $startPosition, int $endPosition) : StringPart {
    static::validateStartAndEndPosition($startPosition, $endPosition, $this->backingString);
    $this->startPositionInclusive = $startPosition;
    $this->endPositionExclusive = $endPosition + 1;

    return $this;
  }

  /**
   * Creates a new string part based on the same backing string.
   *
   * The new string part, although it has the same backing string, may have
   * different endpoints (as are specified here).
   *
   * @param int $startPosition
   *   Inclusive start position of new string part, relative to the backing
   *   string. Must be -1 if the backing string is empty.
   * @param int $endPosition
   *   Inclusive end position of new string part, relative to the backing
   *   string. Must be -1 if the backing string is empty.
   *
   * @return static
   *   The resulting new string part (the current object is not modified).
   *
   * @throws \InvalidArgumentException
   *   If $startPosition is -1, thrown if $endPosition is not -1.
   * @throws \InvalidArgumentException
   *   If $startPosition is not -1, thrown if either $startPosition or
   *   $endPosition is less than zero.
   * @throws \InvalidArgumentException
   *   Thrown if $startPosition is greater than $endPosition.
   * @throws \InvalidArgumentException
   *   Thrown if $endPosition is greater than or equal to the length of the
   *   backing string.
   */
  public function withNewEndpoints(int $startPosition, int $endPosition) : StringPart {
    static::validateStartAndEndPosition($startPosition, $endPosition, $this->backingString);
    return new static($this->backingString, $startPosition, $endPosition + 1);
  }

  /**
   * Creates and returns a new string part.
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
  public static function create(string $backingString = '', int $startPosition = -1, int $endPosition = -1) : StringPart {
    static::validateStartAndEndPosition($startPosition, $endPosition, $backingString);
    return new static($backingString, $startPosition, $endPosition + 1);
  }

  /**
   * Validates the given start and end position variables.
   *
   * Throws exception(s) on validation failure.
   *
   * @param int $inclusiveStartPosition
   *   Inclusive start position, relative to the backing string.
   * @param int $inclusiveEndPosition
   *   Inclusive end position, relative to the backing string.
   * @param string $backingString
   *   Backing string.
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
  protected static function validateStartAndEndPosition(int $startPosition, int $endPosition, string $backingString) : void {
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
        throw new \InvalidArgumentException('$endPosition is greater than or equal to the length of the backing string.');
      }
    }
  }

}
