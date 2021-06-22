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
   * Gets a minimal set of sorted integer ranges from the given integer ranges.
   *
   * The smallest possible number of ranges is returned. This function runs in
   * O(n) (best case), O(n*log n) (average case), or O(n^2) (worst case).
   *
   * @param array<int, int> $ranges
   *   Ranges of integers, where the keys represent the inclusive start values
   *   of the ranges, and the values represent the inclusive end values.
   *
   * @return \Ranine\Iteration\ExtendableIterable
   *   A collection whose keys are the starting values in inclusive ranges of
   *   integers, and whose values are the ending values in these ranges. The
   *   array is sorted from smallest starting value to largest starting value,
   *   and the ranges do not overlap. Every value from a range in $ranges is
   *   included in exactly one range in the output array, and every value in
   *   every range in the output array can be found in a range in $ranges.
   *
   * @throws \InvalidArgumentException
   *   Thrown if a key or value in $ranges is non-integral.
   * @throws \InvalidArgumentException
   *   Thrown if a key in $ranges is greater than its value.
   */
  public static function condenseAndSortRanges(array $ranges) : ExtendableIterable {
    ksort($ranges, SORT_NUMERIC);

    return ExtendableIterable::from((function () use ($ranges) {
      $isFirstIteration = TRUE;
      $currentOutputStartValue = 0;
      $currentOutputEndValue = 0;
      foreach ($ranges as $start => $end) {
        if (!is_int($start) || !is_int($end)) {
          throw new \InvalidArgumentException('A key or value in $ranges is non-integral.');
        }
        if ($start > $end) {
          throw new \InvalidArgumentException('A key in $ranges is greater than its value.');
        }
        if ($isFirstIteration) {
          $currentOutputStartValue = $start;
          $currentOutputEndValue = $end;
          $isFirstIteration = FALSE;
        }
        else {
          // There are three possible scenarios:
          // 1) The input interval is a subset of the current output interval.
          // In this case we don't do anything.
          // 2) The union of the input and output intervals is a longer
          // interval, in which case we just extend the right endpoint of the
          // current output interval.
          // 3) The input interval is separated from the output interval by a
          // gap of at least two, in which case we yield the current output
          // interval and start a new one.
          if ($end > $currentOutputEndValue) {
            // Having eliminated case 1, we can look at cases 2 and 3.
            if ($start > ($currentOutputEndValue + 1)) {
              // Case 3.
              yield $currentOutputStartValue => $currentOutputEndValue;
              $currentOutputStartValue = $start;
            }
            // In both cases 2 and 3, we update the output endpoint to the input
            // value.
            $currentOutputEndValue = $end;
          }
        }
      }

      // Yield the last interval.
      yield $currentOutputStartValue => $currentOutputEndValue;
    })());
  }

  /**
   * Gets integer ranges, in sorted order, from the given integers.
   *
   * The smallest possible number of ranges is returned. This function runs in
   * O(n) (best case), O(n*log n) (average case), or O(n^2) (worst case).
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
    /** @var array<int, null> */
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
      foreach ($integersAsKeys as $value => $discard) {
        if ($isFirstIteration) {
          $currentStartValue = $value;
          $currentEndValue = $value;
          $isFirstIteration = FALSE;
        }
        else {
          // If the current value is more than one away from the current
          // endpoint, we've reached a gap, so we yield the current interval and
          // reset the current start value.
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
