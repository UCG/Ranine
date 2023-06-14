<?php

declare(strict_types = 1);

namespace Ranine\Helper;

/**
 * Static helper methods to deal with throwing exceptions.
 *
 * @static
 */
final class ThrowHelpers {

  /**
   * Empty private constructor to ensure no one instantiates this class.
   */
  private function __construct() {
  }

  /**
   * Throws an \InvalidArgumentException if $value is an empty string.
   *
   * @param string $variableName
   *   Variable name to include in the exception message. Should not contain the
   *   leading "$".
   *
   * @throws \InvalidArgumentException
   */
  public static function throwIfEmptyString(?string $value, string $variableName) : void {
    if ($value === '') {
      throw new \InvalidArgumentException('$' . $variableName . ' is empty.');
    }
  }

  /**
   * Throws an \InvalidArgumentException if $value is less than/equal to zero.
   *
   * @param string $variableName
   *   Variable name to include in the exception message. Should not contain the
   *   leading "$".
   *
   * @throws \InvalidArgumentException
   */
  public static function throwIfLessThanOrEqualToZero(int|float|null $value, string $variableName) : void {
    if ($value !== NULL && $value <= 0) {
      throw new \InvalidArgumentException('$' . $variableName . ' is less than or equal to zero.');
    }
  }

  /**
   * Throws an \InvalidArgumentException if $value is less than zero.
   *
   * @param string $variableName
   *   Variable name to include in the exception message. Should not contain the
   *   leading "$".
   *
   * @throws \InvalidArgumentException
   */
  public static function throwIfLessThanZero(int|float|null $value, string $variableName) : void {
    if ($value < 0) {
      throw new \InvalidArgumentException('$' . $variableName . ' is less than zero.');
    }
  }

}
