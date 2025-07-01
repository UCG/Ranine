<?php

declare(strict_types = 1);

namespace Ranine\Tests\Iteration;

use PHPUnit\Framework\TestCase;
use Ranine\Exception\InvalidOperationException;
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
  array $expectedKeys,
  array $expectedValues,
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
  int $valueToAppend,
  array $expectedKeys,
  array $expectedValues,
  int $expectedCount) : void {
    
  $iter = ExtendableIterable::from($iterData);
  $appendedIter = $iter->appendValue($valueToAppend);
  $this->assertIterableKeysAndValues($appendedIter, $expectedKeys, $expectedValues, $expectedCount);

  }

  /**
   * @covers ::apply
   * @dataProvider provideDataForTestApply
   */
  public function testApply(iterable $iterData, array $expectedKeys,
  array $expectedValues) : void {
    $iter = ExtendableIterable::from($iterData);
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
    // Perform dummy assertion to avoid risky test warning
    $this->assertTrue(TRUE);
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
    function (mixed $kCurrent, mixed $vCurrent, mixed $kOther, mixed $vOther)
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
    }, function (mixed $kCurrent, mixed $vCurrent)
    use($isIterBiggerThanOther,
      $numberOfElementsForWhichBothItersAreValid,
      $expectedCurrentKeys,
      $expectedCurrentValues,
      &$i) : void {

      $this->assertTrue($isIterBiggerThanOther);
      $this->assertGreaterThanOrEqual($numberOfElementsForWhichBothItersAreValid, $i);
      $this->assertSame($kCurrent, $expectedCurrentKeys[$i]);
      $this->assertSame($vCurrent, $expectedCurrentValues[$i]);
      $i++;
    }, function (mixed $kOther, mixed $vOther) use ($isIterBiggerThanOther,
      $numberOfElementsForWhichBothItersAreValid,
      $expectedOtherKeys,
      $expectedOtherValues,
      &$i) {

      $this->assertFalse($isIterBiggerThanOther);
      $this->assertGreaterThanOrEqual($numberOfElementsForWhichBothItersAreValid, $i);
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
   * @dataProvider provideDataForTestExpand
   */
  public function testExpand(iterable $iterData,
    array $expectedKeys,
    array $expectedValues,
    int $expectedCount) : void {
    $iter = ExtendableIterable::from($iterData);
    $expansion = fn($key, $value) => is_iterable($value) ? $value : NULL;
    $expandedIter = $iter->expand($expansion);
    $this->assertIterableKeysAndValues($expandedIter,$expectedKeys,$expectedValues,$expectedCount);
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
   * @covers ::first
   */
  public function testFirst() : void {
    $iter = ExtendableIterable::from([1]);
    $first = $iter->first();
    $this->assertSame(1,$first);
  }

  /**
   * @covers ::first
   */
  public function testFirstIsEmpty() : void {
    $iter = ExtendableIterable::empty();
    $this->expectException(InvalidOperationException::class);
    $iter->first();
  }

    /**
   * @covers ::firstKey
   */
  public function testFirstKey() : void {
    $iter = ExtendableIterable::from(['b' => NULL]);
    $firstKey = $iter->firstKey();
    $this->assertSame('b', $firstKey);
  }

  /**
   * @covers ::firstKey
   */
  public function testFirstKeyIsEmpty() : void {
    $iter = ExtendableIterable::empty();
    $this->expectException(InvalidOperationException::class);
    $iter->firstKey();
  }

  /**
   * @covers ::firstKeyAndValue
   */
  public function testFirstKeyAndValue() : void {
    $key = 0;
    $value = 0;
    $iter = ExtendableIterable::from([1 => NULL]);
    $iter->firstKeyAndValue($key, $value);
    $this->assertSame(1, $key);
    $this->assertSame(NULL, $value);
  }
  
  /**
   * @covers ::firstKeyAndValue
   */
  public function testFirstKeyAndValueIsEmpty() : void {
    $key = 0;
    $value = 0;
    $iter = ExtendableIterable::empty();
    $this->expectException(InvalidOperationException::class);
    $iter->firstKeyAndValue($key, $value);
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
    $emptyIter = ExtendableIterable::from([]);   $nonEmptyIter = ExtendableIterable::from([0 => NULL]);

    $this->assertTrue($emptyIter->isEmpty());
    $this->assertFalse($nonEmptyIter->isEmpty());
  }

  /**
   * @covers ::map
   * @dataProvider provideDataForTestMap
   */
  public function testMap(iterable $iterData,
    callable $keyMap,
    callable $valueMap,
    array $expectedKeys,
    array $expectedValues,
    int $expectedCount) : void {
    $iter = ExtendableIterable::from($iterData);
    // We'll use arrow notation since our functions are just single return expressions.
    // $keyMap = fn($k, $v) => $k**2 + $v;
    // $valueMap = fn($k, $v) => $v**2 - $k;
    $mappedIter = $iter->map($valueMap, $keyMap);
    $this->assertIterableKeysAndValues($mappedIter, $expectedKeys, $expectedValues, $expectedCount);
  }

  /**
   * @covers ::mapSequentialKeys
   */
  public function testMapSequentialKeys() : void {
    $iter = ExtendableIterable::from([1,2]);
    $valueMap = fn($k, $v) => $v;
    $mappedIter = $iter->mapSequentialKeys($valueMap);
    $this->assertIterableKeysAndValues($mappedIter, [0,1], [1,2], 2);
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

  /**
   * @covers ::take
   * @dataProvider provideDataForTestTake
   */
  public function testTake(array $iterData,
    int $numberOfItemsToTake,
    array $expectedKeys,
    array $expectedValues,
    int $expectedCount) : void {

    $iter = ExtendableIterable::from($iterData);
    $result = $iter->take($numberOfItemsToTake);
    $this->assertIterableKeysAndValues($result, $expectedKeys, $expectedValues, $expectedCount);
  }

  /**
   * @covers ::take
   */
  public function testTakeInvalidNumber() : void {
    $iter = ExtendableIterable::fromKeyAndValue(2, 3);
    $this->expectException(\InvalidArgumentException::class);
    $iter->take(-1);
  }

  /**
   * @covers ::takeWhile
   */
  public function testTakeWhile() : void {
    $iter = ExtendableIterable::from([1,2,3,4,5]);
    $predicate = fn($key, $value) : bool => TRUE;
    $max = 4;
    $newIter = $iter->takeWhile($predicate, $max);
    $this->assertIterableKeysAndValues($newIter, [0,1,2,3], [1,2,3,4], 4);
  }
  
  public function testTakeWhileMaxLessThanZero() : void {

  }

  public function provideDataForTestAppend() : array {
    return [
      'empty' => [[],[],[],[],0],
      'single-append' => [[1],[7],[1, 7], [0, 0], 2],
    ];
  }
  
  public function provideDataForTestAppendValue() : array {
    return [
      'single-append' => [[1],7,[0,0],[1,7],2],
    ];
  }
  
  public function provideDataForTestAppendKeyAndValue() : array {
    return [
      'single-key-value-append' => [[2,5],1,7,[0,1,1],[2,5,7],3],
      'single-key-value-append-to-empty-array' => [[],1,7,[1],[7],1],
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
  
  public function provideDataForTestApply() : array {
    return [
      'empty' => [[],[],[]],
      'single' => [[1],[0],[1]],
      'multi' => [[8,6,11],[0,1,2],[8,6,11]],
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
      'iterable' => [ExtendableIterable::fromKeyAndValue(2,1),1],
    ];
  }

  public function provideDataForTestExpand() : array {
    return [
      'empty' => [[],[],[],0],
      'all-expandable' => [[[0,1],[2,3]],[0,1,0,1],[0,1,2,3],4],
      'all-non-expandable' => [[1,2,3],[0,1,2],[1,2,3],3],
      'normal-mixed' => [[0,[1,2],3,[4,5]],[0,0,1,2,0,1],[0,1,2,3,4,5],6],
      'does-not-expand-beyond-top-level' => [[[[0,1],2]],[0,1],[[0,1],2],2],
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

  public function provideDataForTestMap() : array {
    return [
      'empty' => [[],fn($k, $v) => $k**2 + $v,fn($k, $v) => $v**2 - $k,[],[],0],
      'negative-keyMap' => [
        [0 => -1],
        fn($k, $v) => $k**2 + $v,
        fn($k, $v) => $v**2 - $k,
        [-1],
        [1],
        1
      ],
      'negative-valueMap' => [
        [1 => 0],
        fn($k, $v) => $k**2 + $v,
        fn($k, $v) => $v**2 - $k,
        [1],
        [-1],
        1
      ],
      'negative-keyMap-and-valueMap' => [
        [-2 => -1],
        fn($k, $v) => $k + $v,
        fn($k, $v) => $v + $k,
        [-3],
        [-3],
        1
      ],
      'null-keyMap' => [
        ExtendableIterable::fromKeyAndValue(NULL, 7),
        fn($k) => $k,
        fn($k, $v) => $v,
        [NULL],
        [7],
        1
      ],
      'null-valueMap' => [
        [2 => NULL],
        fn($k, $v) => $k + $v,
        fn($k, $v) => $v,
        [2],
        [NULL],
        1
      ],
      'null-keyMap-and-valueMap' => [
        ExtendableIterable::fromKeyAndValue(NULL, NULL),
        fn($k) => $k,
        fn($v) => $v,
        [NULL],
        [NULL],
        1
      ],
      'multi' => [
        [4 => 5, 5 => 9],
        fn($k, $v) => $k**2 + $v,
        fn($k, $v) => $v**2 - $k,
        [21,34],
        [21,76],
        2
      ],
    ];
  }

  public function provideDataForTestReduce() : array{
    return [
      'empty' => [[], fn() => 1, 5, 5],
      'single' => [[1 => 2], fn($k, $v, $a) => $k + $v + $a, -1, 2],
      'multi' => [[1 => 2, 3 => 4], fn($k, $v, $a) => $a + $v, 0, 6],
    ];
  }

  public function provideDataForTestTake() : array {
    return [
      'empty' => [[], 0, [], [], 0],
      'more-than-we-have' => [[1, 2], 3, [0, 1], [1, 2], 2],
      'take-all-items' => [['a' => 0, 'b' => 1, 'c' => 2], 3, ['a', 'b', 'c'], [0, 1, 2], 3],
      'take-some-items' => [[2, 4, 5], 2, [0, 1], [2, 4], 2],
      'take-one-item' => [[2, 4, 5], 1, [0], [2], 1],
      'take-none' => [[1], 0, [], [], 0],
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
