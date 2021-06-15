<?php

declare(strict_types = 1);

namespace Ranine\Helper;

use Ranine\Iteration\ExtendableIterable;

/**
 * Collection-related static helper methods.
 *
 * @static
 */
final class CollectionHelpers {

  /**
   * Empty private constructor to ensure no one instantiates this class.
   */
  private function __construct() {
  }

  /**
   * Gets integer ranges, in sorted order, from the given integers.
   *
   * The smallest possible number of ranges is returned.
   *
   * @param iterable<int> $integers
   *   Collection whose values are integers.
   *
   * @return \Ranine\Iteration\ExtendableIterable
   *   A collection whose keys are the starting values in inclusive ranges of
   *   integers, and whose values are the ending values in these ranges. The
   *   array is sorted from smallest starting value to largest starting value,
   *   and the ranges do not overlap. Every value from $integers is included in
   *   exactly one range in the output array, and every value in every range in
   *   the output array can be found in $integers.
   *
   * @throws \InvalidArgumentException
   *   Thrown if a value in $integers was not an integer.
   */
  public static function getSortedRanges(iterable $integers) : ExtendableIterable {
    /** @var null[] */
    $integersAsKeys = [];
    foreach ($integers as $value) {
      if (!is_int($value)) {
        throw new \InvalidArgumentException('A value in $integers is not an integer.');
      }
      $integersAsKeys[$value] = NULL;
    }
    if ($integersAsKeys === []) {
      return ExtendableIterable::empty();
    }

    ksort($integersAsKeys, SORT_NUMERIC);

    return ExtendableIterable::from((function () use ($integersAsKeys) {
      $isFirstIteration = TRUE;
      $currentStartValue = 0;
      $currentEndValue = 0;
      foreach ($integersAsKeys as $value => $x) {
        if ($isFirstIteration) {
          $currentStartValue = $value;
          $currentEndValue = $value;
        }
        else {
          // If the current value is more than one away from the current endpoint,
          // we've reached a gap, so we yield the current interval and reset the
          // current start value.
          if ($value > ($currentEndValue + 1)) {
            yield $currentStartValue => $currentEndValue;
            $currentStartValue = $value;
          }
          // The endpoint is always updated to the current value.
          $currentEndValue = $value;
        }
      }

      // Yield the last interval.
      yield $currentStartValue => $currentEndValue;
    })());
  }

}
