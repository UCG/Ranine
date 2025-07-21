<?php

declare(strict_types = 1);

namespace Ranine\Tests\Traits;

/**
 * Contains methods useful for asserting things about iterables.
 *
 * Only intended to be used within test classes.
 */
trait IterableAssertionTrait {

  /**
   * Asserts that a given iterable has keys and values in a certain order.
   *
   * @param iterable $iterableUnderTest
   *   Iterable we are testing.
   * @param array $expectedKeys
   *   The values of this array are the keys we expect to be in
   *   $iterableUnderTest. The keys of this array indicate the expected order
   *   of the keys in $iterableUnderTest (i.e., $expectedKeys[0] is the first
   *   key we expect in $iterableUnderTest, $expectedKeys[1] is the second,
   *   etc.).
   * @param array $expectedValues
   *   The values of this array are the values we expect to be in
   *   $iterableUnderTest. The keys of this array indicate the expected order
   *   of the values in $iterableUnderTest (i.e., $expectedValues[0] is the
   *   first value we expect in $iterableUnderTest, $expectedValues[1] is the
   *   second, etc.).
   * @param int $expectedCount
   *   Expected number of items in $iterableUnderTest.
   */
  private function assertIterableKeysAndValues(iterable $iterableUnderTest,
    array $expectedKeys, array $expectedValues, int $expectedCount) : void {

    $i = 0;
    foreach ($iterableUnderTest as $k => $v) {
      $this->assertSame($expectedKeys[$i], $k);
      $this->assertSame($expectedValues[$i], $v);
      $i++;
    }
    $this->assertSame($expectedCount, $i);
  }

}
