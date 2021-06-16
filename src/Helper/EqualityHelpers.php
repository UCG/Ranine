<?php

declare(strict_types = 1);

namespace Ranine\Helper;

/**
 * Static helper methods to deal with checking for equality.
 *
 * @static
 */
final class EqualityHelpers {

  /**
   * Empty private constructor to ensure no one instantiates this class.
   */
  private function __construct() {
  }

    /**
   * Checks if the keys/values of the two arrays are equal.
   *
   * Equality is reached if two things are true, provided $recursive is 'FALSE':
   * 1) for each key/value pair in $arr1, there is a strictly equal key/value
   * pair in $arr2, and 2) for each key/value pair in $arr2, there is a strictly
   * equal key/value pair in $arr1. If $recursive is 'TRUE', equality will be
   * checked as above, exactly that value equality between arrays is checked
   * recursively using this same procedure.
   *
   * @param array $arr1
   *   Array 1.
   * @param array $arr2
   *   Array 2.
   * @param bool $recursive
   *   Whether equality for sub-arrays should be checked recursively, using this
   *   function.
   *
   * @return bool
   *   Returns 'TRUE' if arrays are equal, else 'FALSE'.
   */
  public static function areArraysEqualStrictOrderInvariant(array $arr1, array $arr2, bool $recursive = FALSE) : bool {
    if (count($arr1) !== count($arr2)) {
      return FALSE;
    }

    if ($recursive) {
      $iterator = new \RecursiveArrayIterator($arr1);
      return IterationHelpers::walkRecursiveIterator($iterator,
        function ($key, $value, array $context) : bool {
          // Check to ensure the other array has a corresponding key.
          if (!array_key_exists($key, $context)) {
            return FALSE;
          }
          
          if (is_array($value)) {
            // If $value is an array, check to ensure the other array is an
            // array with the same number of elements (equality of the elements
            // themselves will be checked recursively later).
            return is_array($context[$key]) && count($context[$key]) === count($value);
          }
          else {
            return $value === $context[$key];
          }
        },
        fn($k, $v, $c) : array => $c[$k],
        $arr2);
    }
    else {
      foreach ($arr1 as $key => $value) {
        if (!array_key_exists($key, $arr2)) {
          return FALSE;
        }
        if ($value !== $arr2[$key]) {
          return FALSE;
        }
      }
    }

    return TRUE;
  }

  /**
   * Checks if the values in two arrays are equal.
   *
   * Equality is defined such that equality is obtained if and only if for each
   * element in $arr1, there is exactly one matching element in $arr2 with the
   * same value, and visa versa.
   *
   * @param array $arr1
   *   Array 1.
   * @param array $arr2
   *   Array 2.
   *
   * @return bool
   *   Returns 'TRUE' if array values are equal, else 'FALSE'.
   */
  public static function areArrayValuesEqualStrict(array $arr1, array $arr2) : bool {
    if (count($arr1) !== count($arr2)) {
      return FALSE;
    }

    // @todo Optimize if values can be nicely represented as strings/integers,
    // and maybe for cases where values can be sorted, and for in-between cases.
    // Right now this runs in O(n*m).

    // Set up a table of the used keys in $arr2.
    $usedKeys = [];
    foreach ($arr1 as $value1) {
      $foundValue = FALSE;
      foreach ($arr2 as $key2 => $value2) {
        if (!array_key_exists($key2, $usedKeys) && $value2 === $value1) {
          $foundValue = TRUE;
          break;
        }
      }
      if ($foundValue) {
        $usedKeys[$key2] = NULL;
      }
      else {
        return FALSE;
      }
    }

    return TRUE;
  }

}
