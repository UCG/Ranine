<?php

declare(strict_types = 1);

namespace Ranine\Tests\Iteration;

use PHPUnit\Framework\TestCase;
use Ranine\Iteration\ExtendableIterable;

/**
 * Tests the ExtendableIterable class.
 *
 * @coversDefaultClass \Ranine\Iteration\ExtendableIterable
 * @group ranine
 */
class ExtendableIterableTest extends TestCase {

  /**
   * Test the all() method.
   *
   * @covers ::all
   * @dataProvider provideDataForTestAll
   */
  public function testAll(array $inputData, callable $predicate, bool $expectedResult) : void {
    $iter = ExtendableIterable::from($inputData);
    $this->assertSame($expectedResult, $iter->all($predicate));
  }

  /**
   * Test the any() method.
   * 
   * @covers ::any
   * @dataProvider provideDataForTestAny
   */
  public function testAny(array $inputData, callable $predicate, bool $expectedResult) : void {
    $iter = ExtendableIterable::from($inputData);  
    $this->assertSame($expectedResult, $iter->any($predicate));
  }

  /**
   * Test the append() method.
   * 
   * @covers ::append
   * @dataProvider provideDataForTestAppend
   */
  public function testAppend(array $iterData,
    iterable $iterToAppend,
    array $expectedValues,
    array $expectedKeys,
    int $expectedCount) : void {
      
    $iter = ExtendableIterable::from($iterData);
    $appendedIter = $iter->append($iterToAppend);
    $this->assertIterableKeysAndValues($appendedIter, $expectedKeys, $expectedValues, $expectedCount);
  }

  /**
   * @covers ::appendKeyAndValue
   * @dataProvider provideDataForTestAppendKeyAndValue
   */
  public function testAppendKeyAndValue(array $iterData,
  int $keyToAppend,
  int $valueToAppend,
  array $expectedValues,
  array $expectedKeys,
  int $expectedCount) : void {

    $iter = ExtendableIterable::from($iterData);
    $appendedKeyAndValue = $iter->appendKeyAndValue($keyToAppend, $valueToAppend);
    $this->assertIterableKeysAndValues($appendedKeyAndValue, $expectedKeys, $expectedValues, $expectedCount);
  }

  /**
   * @covers ::appendValue
   * @dataProvider provideDataForTestAppendValue
   */
  public function testAppendValue(iterable $iterData,
  int $iterToAppend,
  array $expectedKeys,
  array $expectedValues,
  int $expectedCount) : void {
    
  $iter = ExtendableIterable::from($iterData);
  $appendedIter = $iter->appendValue($iterToAppend);
  $this->assertIterableKeysAndValues($appendedIter, $expectedKeys, $expectedValues, $expectedCount);

  }

  public function testApply() : void {
    $iter = ExtendableIterable::from([1,5,12]);
    $expectedKeys = [0,1,2];
    $expectedValues = [1,5,12];
    $currentIndex = -1;
    $processing = function($key, $value) use ($expectedKeys, $expectedValues, &$currentIndex) {
      $this->assertGreaterThanOrEqual(0,$currentIndex);
      $this->assertLessThanOrEqual(2, $currentIndex);
      $this->assertSame($expectedValues[$currentIndex], $value);
      $this->assertSame($expectedKeys[$currentIndex], $key);
    };
    $appliedIter = $iter->apply($processing);
    $currentIndex++;
    foreach ($appliedIter as $k => $v) {
      $this->assertSame($expectedValues[$currentIndex], $v);
      $this->assertSame($expectedKeys[$currentIndex], $k);
      $currentIndex++;
    }
  }

  /**
   * @covers ::applyWith
   * @dataProvider provideDataForTestApplyWith
   */
  public function testApplyWith(array $iterData,
    array $other,
    array $expectedCurrentKeys,
    array $expectedCurrentValues,
    array $expectedOtherKeys,
    array $expectedOtherValues) : void {

    $iter = ExtendableIterable::from($iterData);

    $numberOfElementsForWhichBothItersAreValid = min(count($iterData), count($other));
    $isIterBiggerThanOther = count($iterData) > count($other);

    $i = 0;
    $iter->applyWith($other,
    function (int $kCurrent, int $vCurrent, int $kOther, int $vOther)
    use ($expectedCurrentKeys,
      $expectedCurrentValues,
      $expectedOtherKeys,
      $expectedOtherValues,
      $numberOfElementsForWhichBothItersAreValid, &$i) : void {

      $this->assertLessThan($numberOfElementsForWhichBothItersAreValid, $i);
      $this->assertSame($kCurrent, $expectedCurrentKeys[$i]);
      $this->assertSame($vCurrent, $expectedCurrentValues[$i]);
      $this->assertSame($kOther, $expectedOtherKeys[$i]);
      $this->assertSame($vOther, $expectedOtherValues[$i]);
      $i++;
    }, function (int $kCurrent, int $vCurrent)
    use($isIterBiggerThanOther,
      $numberOfElementsForWhichBothItersAreValid,
      $expectedCurrentKeys,
      $expectedCurrentValues,
      &$i) : void {

      $this->assertTrue($isIterBiggerThanOther);
      $this->assertGreaterThan($numberOfElementsForWhichBothItersAreValid, $i);
      $this->assertSame($kCurrent, $expectedCurrentKeys[$i]);
      $this->assertSame($vCurrent, $expectedCurrentValues[$i]);
      $i++;
    }, function (int $kOther, int $vOther) use ($isIterBiggerThanOther,
      $numberOfElementsForWhichBothItersAreValid,
      $expectedOtherKeys,
      $expectedOtherValues,
      &$i) {

      $this->assertFalse($isIterBiggerThanOther);
      $this->assertGreaterThan($numberOfElementsForWhichBothItersAreValid, $i);
      $this->assertSame($kOther, $expectedOtherKeys[$i]);
      $this->assertSame($vOther, $expectedOtherValues[$i]);
      $i++;
    });

    $totalNumberOfIterations = max(count($iterData), count($other));
    $this->assertSame($totalNumberOfIterations, $i);
  }

  /**
   * @covers ::count
   * @dataProvider provideDataForTestCount
   */
  public function testCount(iterable $iterData,
    int $expectedCount
    ) : void {
    // assemble
    $source = ExtendableIterable::from($iterData);
    // act
    $countedSource = $source->count();
    // assert
    $this->assertSame($expectedCount, $countedSource);
  }

  /**
   * @covers ::expand
   */
  public function testExpand() : void {
    $iter = ExtendableIterable::from([[0,1],[2,3],7]);
    $expansion = fn($key, $value) => is_iterable($value) ? $value : NULL;
    $expandedIter = $iter->expand($expansion);
    $this->assertIterableKeysAndValues($expandedIter,[0,1,0,1,2],[0,1,2,3,7],5);
  }

  
  /**
   * @covers ::filter
   * @dataProvider provideDataForTestFilter
   */
  public function testFilter(array $input,
  callable $filter,
  array $expectedKeys,
    array $expectedValues,
    int $expectedCount) : void {
      
    $iter = ExtendableIterable::from($input);
    $filteredIter = $iter->filter($filter);
    $this->assertIterableKeysAndValues($filteredIter, $expectedKeys, $expectedValues, $expectedCount);
  }

  /**
   * @covers ::getKeys
   * @dataProvider provideDataForTestGetKeys
   */
  public function testGetKeys(array $dataForInitialIter, array $expectedKeys, array $expectedValues, int $expectedCount) : void {
    $iter = ExtendableIterable::from($dataForInitialIter);
    $keysIter = $iter->getKeys();
    $this->assertIterableKeysAndValues($keysIter, $expectedKeys, $expectedValues, $expectedCount);
  }
  
  /**
   * @covers ::isEmpty
   */
  public function testIsEmpty() : void {
    $emptyIter = ExtendableIterable::from([]);
    $nonEmptyIter = ExtendableIterable::from([0 => NULL]);

    $this->assertTrue($emptyIter->isEmpty());
    $this->assertFalse($nonEmptyIter->isEmpty());
  }
  
  /**
   * @covers ::reduce
   * @dataProvider provideDataForTestReduce
   */
  public function testReduce(array $iterData,
    callable $reduction,
    mixed $initialValueOfAggregate,
    mixed $expectedOutput) : void {

    $iter = ExtendableIterable::from($iterData);
    $finalValue = $iter->reduce($reduction, $initialValueOfAggregate);
    $this->assertSame($expectedOutput, $finalValue);
  }

  public function provideDataForTestAppend() : array {
    return [
      'empty' => [[],[],[],[],0],
      'single-append' => [[1],[7],[1, 7], [0, 0], 2],
    ];
  }
  
  public function provideDataForTestAppendValue() : array {
    return [
      'single-append' => [1,7,[1, 7], [0, 1], 2],
    ];
  }
  
  public function provideDataForTestAppendKeyAndValue() : array {
    return [
      'single-key-value-append' => [[2,5],7,1,[0,1,1],[2,5,7],3],
      'single-key-value-append-to-empty-array' => [[],7,1,[1],[7],1],
    ];
  }
  
  public function provideDataForTestAll() : array {
    return [
      'empty' => [[], fn() => FALSE, TRUE],
      'single-false-predicate' => [[1], fn() => FALSE, FALSE],
      'single-true-predicate' => [[1], fn() => TRUE, TRUE],
      'double-key-only-true-predicate' => [[2 => 4, 4 => 6], fn(int $k) => $k % 2 === 0, TRUE],
      'double-key-only-false-predicate' =>[[2 => 4, 3 => 6], fn(int $k) => $k % 2 === 1, FALSE],
      'normal-false-predicate' => [[1 => 2, 3 => 4], fn(int $k, int $v) => $k + $v < 7, FALSE],
      'normal-true-predicate' => [[1 => 2, 3 => 4], fn(int $k, int $v) => $k + $v <= 7, TRUE],
    ];
  }
  
  public function provideDataForTestAny() : array {
    return [
      'empty' => [[], fn() => TRUE, FALSE],
      'single-false-predicate' => [[1], fn() => FALSE, FALSE],
      'single-true-predicate' => [[1], fn() => TRUE, TRUE],
      'double-key-only-true-predicate' => [[2 => 4, 3 => 6], fn(int $k) => $k % 2 === 0, TRUE],
      'double-key-only-false-predicate' =>[[5 => 4, 3 => 6], fn(int $k) => $k % 2 === 0, FALSE],
      'normal-false-predicate' => [[1 => 2, 3 => 4], fn(int $k, int $v) => $k + $v < 1, FALSE],
      'normal-true-predicate' => [[1 => 2, 3 => 4], fn(int $k, int $v) => $k + $v === 7, TRUE],
    ];
  }
  
  public function provideDataForTestApplyWith() : array {
    return [
      'empty-both' => [[], [], [], [], [], []],
      'current-empty' => [[], [2 => 3, 4 => 5], [], [], [2, 4], [3, 5]],
      'other-empty' => [['a' => 'b', 'c' => 'd'], [], ['a', 'c'], ['b', 'd'], [], []],
      'same-size' => [[2 => 4], [5 => 7], [2], [4], [5], [7]],
      'current-larger' => [[3 => 3, 0 => 0], [1 => 'a'], [3, 0], [3, 0], [1], ['a']],
      'other-larger' => [[0 => NULL], [1 => 2, 3 => 4], [0], [NULL], [1, 3], [2, 4]],
    ];
  }
  
  public function provideDataForTestCount() : array {
    return [
      'empty' => [[],0],
      'array' => [[2,4,6],3],
      'iterable' => [2,1],
      'other' => ['a',1]      
    ];
  }

  public function provideDataForTestFilter() : array {
    return [
      'empty' => [[], fn() => TRUE, [], [], 0],
      'single-pass' => [[2 => 3], fn($k, $v) => $k > 0 && $v > 0, [2], [3], 1],
      'single-fail' => [[1 => 1], fn($k, $v) => $k < 0, [], [], 0],
      'multi-pass' => [[2 => 3, 4 => NULL], fn($k, $v) => $k % 2 === 0, [2, 4], [3, NULL], 2],
      'multi-fail' => [[0 => 0, 1 => 1], fn($k, $v) => $v > 2, [], [], 0],
      'some-pass' => [[2, 5, 6], fn($k, $v) => $v % 2 === 0, [0, 2], [2, 6], 2],
    ];
  }

  public function provideDataForTestGetKeys() : array {
    return [
      'empty' => [[], [], [], 0],
      'single' => [[1 => 'b'], [0], [1], 1],
      'multi' => [[1 => 'b', 'c' => NULL], [0, 1], [1, 'c'], 2],
    ];
  }

  public function provideDataForTestReduce() : array{
    return [
      'empty' => [[], fn() => 1, 5, 5],
      'single' => [[1 => 2], fn($k, $v, $a) => $k + $v + $a, -1, 2],
      'multi' => [[1 => 2, 3 => 4], fn($k, $v, $a) => $a + $v, 0, 10],
    ];
  }

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
