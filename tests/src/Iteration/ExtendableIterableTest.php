<?php

declare(strict_types = 1);

namespace Ranine\Tests\Iteration;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;
use Ranine\Exception\InvalidOperationException;
use Ranine\Iteration\ExtendableIterable;
use Ranine\Tests\Traits\IterableAssertionTrait;

#[TestDox('Tests the ExtendableIterable class.')]
#[CoversClass(ExtendableIterable::class)]
#[Group('ranine')]
class ExtendableIterableTest extends TestCase {

  use IterableAssertionTrait;

  #[TestDox('Test the all() method.')]
  #[CoversFunction('all')]
  #[DataProvider('provideDataForTestAll')]
  public function testAll(array $inputData, callable $predicate, bool $expectedResult) : void {
    $iter = ExtendableIterable::from($inputData);
    $this->assertSame($expectedResult, $iter->all($predicate));
  }

  #[TestDox('Test the any() method.')]
  #[CoversFunction('any')]
  #[DataProvider('provideDataForTestAny')]
  public function testAny(array $inputData, callable $predicate, bool $expectedResult) : void {
    $iter = ExtendableIterable::from($inputData);  
    $this->assertSame($expectedResult, $iter->any($predicate));
  }

  #[TestDox('Test the append() method.')]
  #[CoversFunction('append')]
  #[DataProvider('provideDataForTestAppend')]
  public function testAppend(array $iterData,
    iterable $iterToAppend,
    array $expectedValues,
    array $expectedKeys,
    int $expectedCount) : void {
      
    $iter = ExtendableIterable::from($iterData);
    $appendedIter = $iter->append($iterToAppend);
    $this->assertIterableKeysAndValues($appendedIter, $expectedKeys, $expectedValues, $expectedCount);
  }

  #[CoversFunction('appendKeyAndValue')]
  #[DataProvider('provideDataForTestAppendKeyAndValue')]
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

  #[CoversFunction('appendValue')]
  #[DataProvider('provideDataForTestAppendValue')]
  public function testAppendValue(iterable $iterData,
    int $valueToAppend,
    array $expectedKeys,
    array $expectedValues,
    int $expectedCount) : void {
    
    $iter = ExtendableIterable::from($iterData);
    $appendedIter = $iter->appendValue($valueToAppend);
    $this->assertIterableKeysAndValues($appendedIter, $expectedKeys, $expectedValues, $expectedCount);
  }

  #[CoversFunction('apply')]
  #[DataProvider('provideDataForTestApply')]
  public function testApply(iterable $iterData, 
    array $expectedKeys,
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

  #[CoversFunction('applyWith')]
  #[DataProvider('provideDataForTestApplyWith')]
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

  #[CoversFunction('count')]
  #[DataProvider('provideDataForTestCount')]
  public function testCount(iterable $iterData, int $expectedCount) : void {
    $source = ExtendableIterable::from($iterData);
    $countedSource = $source->count();
    $this->assertSame($expectedCount, $countedSource);
  }

  #[CoversFunction('expand')]
  #[DataProvider('provideDataForTestExpand')]
  public function testExpand(iterable $iterData,
  array $expectedKeys,
  array $expectedValues,
  int $expectedCount) : void {
    
    $iter = ExtendableIterable::from($iterData);
    $expansion = fn($key, $value) => is_iterable($value) ? $value : NULL;
    $expandedIter = $iter->expand($expansion);
    $this->assertIterableKeysAndValues($expandedIter,$expectedKeys,$expectedValues,$expectedCount);
  }

  #[CoversFunction('filter')]
  #[DataProvider('provideDataForTestFilter')]
  public function testFilter(array $input,
  callable   $filter,
  array $expectedKeys,
  array $expectedValues,
  int $expectedCount) : void {

    $iter = ExtendableIterable::from($input);
    $filteredIter = $iter->filter($filter);
    $this->assertIterableKeysAndValues($filteredIter, $expectedKeys, $expectedValues, $expectedCount);
  }

  #[CoversFunction('first')]
  public function testFirst() : void {
    $iter = ExtendableIterable::from([1]);
    $first = $iter->first();
    $this->assertSame(1,$first);
  }

  #[CoversFunction('first')]
  public function testFirstEmptyIter() : void {
    $iter = ExtendableIterable::empty();
    $this->expectException(InvalidOperationException::class);
    $iter->first();
  }

  #[CoversFunction('firstKey')]
  public function testFirstKey() : void {
    $iter = ExtendableIterable::from(['b' => NULL]);
    $firstKey = $iter->firstKey();
    $this->assertSame('b', $firstKey);
  }

  #[CoversFunction('firstKey')]
  public function testFirstKeyEmptyIter() : void {
    $iter = ExtendableIterable::empty();
    $this->expectException(InvalidOperationException::class);
    $iter->firstKey();
  }

  #[CoversFunction('firstKeyAndValue')]
  public function testFirstKeyAndValue() : void {
    $key = 0;
    $value = 0;
    $iter = ExtendableIterable::from([1 => NULL]);
    $iter->firstKeyAndValue($key, $value);
    $this->assertSame(1, $key);
    $this->assertSame(NULL, $value);
  }

  #[CoversFunction('firstKeyAndValue')]
  public function testFirstKeyAndValueEmptyIter() : void {
    $key = 0;
    $value = 0;
    $iter = ExtendableIterable::empty();
    $this->expectException(InvalidOperationException::class);
    $iter->firstKeyAndValue($key, $value);
  }

  #[CoversFunction('getKeys')]
  #[DataProvider('provideDataForTestGetKeys')]
  public function testGetKeys(array $dataForInitialIter, array $expectedKeys, array $expectedValues, int $expectedCount) : void {
    $iter = ExtendableIterable::from($dataForInitialIter);
    $keysIter = $iter->getKeys();
    $this->assertIterableKeysAndValues($keysIter, $expectedKeys, $expectedValues, $expectedCount);
  }

  #[CoversFunction('isEmpty')]
  public function testIsEmpty() : void {
    $emptyIter = ExtendableIterable::from([]);   $nonEmptyIter = ExtendableIterable::from([0 => NULL]);
    $this->assertTrue($emptyIter->isEmpty());
    $this->assertFalse($nonEmptyIter->isEmpty());
  }

  #[CoversFunction('map')]
  #[DataProvider('provideDataForTestMap')]
  public function testMap(iterable $iterData,
  callable $keyMap,
  callable $valueMap,
  array $expectedKeys,
  array $expectedValues,
  int $expectedCount) : void {

    $iter = ExtendableIterable::from($iterData);
    $mappedIter = $iter->map($valueMap, $keyMap);
    $this->assertIterableKeysAndValues($mappedIter, $expectedKeys, $expectedValues, $expectedCount);
  }

  #[CoversFunction('mapSequentialKeys')]
  #[DataProvider('provideDataForTestMapSequentialKeys')]
  public function testMapSequentialKeys(array $iterData,
  ?callable $valueMap,
  array $expectedKeys,
  array $expectedValues,
  int $expectedCount) : void {

    $iter = ExtendableIterable::from($iterData);
    $mappedIter = $iter->mapSequentialKeys($valueMap);
    $this->assertIterableKeysAndValues($mappedIter, $expectedKeys, $expectedValues, $expectedCount);
  }

  #[CoversFunction('reduce')]
  #[DataProvider('provideDataForTestReduce')]
  public function testReduce(array $iterData,
  callable $reduction,
  mixed $initialValueOfAggregate,
  mixed $expectedOutput) : void {

    $iter = ExtendableIterable::from($iterData);
    $finalValue = $iter->reduce($reduction, $initialValueOfAggregate);
    $this->assertSame($expectedOutput, $finalValue);
  }

  #[CoversFunction('take')]
  #[DataProvider('provideDataForTestTake')]
  public function testTake(array $iterData,
  int $numberOfItemsToTake,
  array $expectedKeys,
  array $expectedValues,
  int $expectedCount) : void {

    $iter = ExtendableIterable::from($iterData);
    $result = $iter->take($numberOfItemsToTake);
    $this->assertIterableKeysAndValues($result, $expectedKeys, $expectedValues, $expectedCount);
  }

  #[CoversFunction('take')]
  public function testTakeInvalidNumber() : void {
    $iter = ExtendableIterable::fromKeyAndValue(2, 3);
    $this->expectException(\InvalidArgumentException::class);
    $iter->take(-1);
  }

  #[CoversFunction('takeWhile')]
  #[DataProvider('provideDataForTestTakeWhile')]
  public function testTakeWhile(iterable $iterData,
  callable $predicate,
  ?int $max,
  array $expectedKeys,
  array $expectedValues,
    int $expectedCount) : void {

    $iter = ExtendableIterable::from($iterData);
    $newIter = $iter->takeWhile($predicate, $max);
    $this->assertIterableKeysAndValues($newIter, $expectedKeys, $expectedValues, $expectedCount);
  }

  #[CoversFunction('takeWhile')]
  public function testTakeWhileMaxLessThanZero() : void {
    $iter = ExtendableIterable::from([1]);
    $predicate = fn($k,$v) : bool => TRUE;
    $max = -7;
    $this->expectException(\InvalidArgumentException::class);
    $iter->takeWhile($predicate,$max);
  }

  #[CoversFunction('toArray')]
  #[DataProvider('provideDataForTestToArray')]
  public function testToArray(iterable $iterData, bool $preserveKeys, array $expectedFinalArray) : void {
    $iter = ExtendableIterable::from(ExtendableIterable::from($iterData));
    $arr = $iter->toArray($preserveKeys);
    $this->assertTrue($arr === $expectedFinalArray);
  }

  #[CoversFunction('zip')]
  #[DataProvider('provideDataForTestZip')]
  public function testZip(iterable $iterData,
    iterable $other,
    callable $keyMapBoth,
    callable $valueMapBoth,
    ?callable $keyMapCurrent,
    ?callable $valueMapCurrent,
    ?callable $keyMapOther,
    ?callable $valueMapOther,
    array $expectedKeys,
    array $expectedValues,
    int $expectedCount) : void {

    $iter = ExtendableIterable::from($iterData);
    $zipped = $iter->zip($other,
      $keyMapBoth,
      $valueMapBoth,
      $keyMapCurrent,
      $valueMapCurrent,
      $keyMapOther,
      $valueMapOther);

    $this->assertIterableKeysAndValues($zipped, $expectedKeys, $expectedValues, $expectedCount);
  }

  public static function provideDataForTestAll() : array {
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

  public static function provideDataForTestAny() : array {
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

  public static function provideDataForTestAppend() : array {
    return [
      'empty' => [[],[],[],[],0],
      'single-append' => [[1],[7],[1, 7], [0, 0], 2],
    ];
  }
  
  public static function provideDataForTestAppendKeyAndValue() : array {
    return [
      'single-key-value-append' => [[2,5],1,7,[0,1,1],[2,5,7],3],
      'single-key-value-append-to-empty-array' => [[],1,7,[1],[7],1],
    ];
  }

  public static function provideDataForTestAppendValue() : array {
    return [
      'single-append' => [[1],7,[0,0],[1,7],2],
    ];
  }

  public static function provideDataForTestApply() : array {
    return [
      'empty' => [[],[],[]],
      'single' => [[1],[0],[1]],
      'multi' => [[8,6,11],[0,1,2],[8,6,11]],
    ];
  }

  public static function provideDataForTestApplyWith() : array {
    return [
      'empty-both' => [[], [], [], [], [], []],
      'current-empty' => [[], [2 => 3, 4 => 5], [], [], [2, 4], [3, 5]],
      'other-empty' => [['a' => 'b', 'c' => 'd'], [], ['a', 'c'], ['b', 'd'], [], []],
      'same-size' => [[2 => 4], [5 => 7], [2], [4], [5], [7]],
      'current-larger' => [[3 => 3, 0 => 0], [1 => 'a'], [3, 0], [3, 0], [1], ['a']],
      'other-larger' => [[0 => NULL], [1 => 2, 3 => 4], [0], [NULL], [1, 3], [2, 4]],
    ];
  }

  public static function provideDataForTestCount() : array {
    return [
      'empty' => [[],0],
      'array' => [[2,4,6],3],
      'iterable' => [ExtendableIterable::fromKeyAndValue(2,1),1],
    ];
  }

  public static function provideDataForTestExpand() : array {
    return [
      'empty' => [[],[],[],0],
      'all-expandable' => [[[0,1],[2,3]],[0,1,0,1],[0,1,2,3],4],
      'all-non-expandable' => [[1,2,3],[0,1,2],[1,2,3],3],
      'normal-mixed' => [[0,[1,2],3,[4,5]],[0,0,1,2,0,1],[0,1,2,3,4,5],6],
      'does-not-expand-beyond-top-level' => [[[[0,1],2]],[0,1],[[0,1],2],2],
    ];
  }

  public static function provideDataForTestFilter() : array {
    return [
      'empty' => [[], fn() => TRUE, [], [], 0],
      'single-pass' => [[2 => 3], fn($k, $v) => $k > 0 && $v > 0, [2], [3], 1],
      'single-fail' => [[1 => 1], fn($k, $v) => $k < 0, [], [], 0],
      'multi-pass' => [[2 => 3, 4 => NULL], fn($k, $v) => $k % 2 === 0, [2, 4], [3, NULL], 2],
      'multi-fail' => [[0 => 0, 1 => 1], fn($k, $v) => $v > 2, [], [], 0],
      'some-pass' => [[2, 5, 6], fn($k, $v) => $v % 2 === 0, [0, 2], [2, 6], 2],
    ];
  }

  public static function provideDataForTestGetKeys() : array {
    return [
      'empty' => [[], [], [], 0],
      'single' => [[1 => 'b'], [0], [1], 1],
      'multi' => [[1 => 'b', 'c' => NULL], [0, 1], [1, 'c'], 2],
    ];
  }

  public static function provideDataForTestMap() : array {
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

  public static function provideDataForTestMapSequentialKeys() : array {
    return [
      'empty' => [[],fn($k, $v) => $v,[],[],0],
      'null-value-map' => [[NULL,NULL],NULL,[0,1],[NULL,NULL],2],
      'negative-value-map' => [[-11,-22],fn($k, $v) => $v + $v,[0,1],[-22,-44],2],
      'single' => [[5],fn($k, $v) => $v * $k,[0],[0],1],
      'non-sequential-input-keys' => [[9=>1,4=>13],fn($k,$v) => $v**2 - $k,[0,1],[-8,165],2],
    ];
  }

  public static function provideDataForTestReduce() : array{
    return [
      'empty' => [[], fn() => 1, 5, 5],
      'single' => [[1 => 2], fn($k, $v, $a) => $k + $v + $a, -1, 2],
      'multi' => [[1 => 2, 3 => 4], fn($k, $v, $a) => $a + $v, 0, 6],
    ];
  }

  public static function provideDataForTestTake() : array {
    return [
      'empty' => [[], 0, [], [], 0],
      'more-than-we-have' => [[1, 2], 3, [0, 1], [1, 2], 2],
      'take-all-items' => [['a' => 0, 'b' => 1, 'c' => 2], 3, ['a', 'b', 'c'], [0, 1, 2], 3],
      'take-some-items' => [[2, 4, 5], 2, [0, 1], [2, 4], 2],
      'take-one-item' => [[2, 4, 5], 1, [0], [2], 1],
      'take-none' => [[1], 0, [], [], 0],
    ];
  }

  public static function provideDataForTestTakeWhile() : array {
    return [
      'empty' => [[],fn($k,$v) => TRUE,2,[],[],0],
      'predicate-true-for-all-no-max' => [[1,2,3,4],fn($k,$v) => $v>0,NULL,[0,1,2,3],[1,2,3,4],4],
      'predicate-false-immediately' => [[1,2,3,4],fn($k,$v) => $v<0,NULL,[],[],0],
      'predicate-fails-before-max' => [[1,2,3,4],fn($k,$v) => $v+$k<6,5,[0,1,2],[1,2,3],3],
      'predicate-fails-after-max' => [[1,2,3,4],fn($k,$v) => $v*$k<30,3,[0,1,2],[1,2,3],3],
      'max-equals-zero' => [[1,2,3,4],fn($k,$v) => $v>0,0,[],[],0],
    ];
  }

  public static function provideDataForTestToArray() : array {
    return [
      'empty' => [ExtendableIterable::empty(), FALSE, []],
      'preserve-keys' => [ExtendableIterable::fromKeyAndValue(2, 4), TRUE, [2 => 4]],
      'keys-not-preserved' => [ExtendableIterable::from(ExtendableIterable::from([2 => 3, 0 => 1])), FALSE, [0 => 3, 1 => 1]],
      'keys-preserved-with-array-object' => [new \ArrayObject([3 => 2, 1 => 3]), TRUE, [3 => 2, 1 => 3]],
      'keys-preserved-with-array' => [['a' => 'c', 'd' => 'e'], TRUE, ['a' => 'c', 'd' => 'e']],
    ];
  }

  public static function provideDataForTestZip() : array {
    return [
      'both-iters-same-size' => [
        [0 => 1, 4 => 5],
        [2 => 2, 1 => 1],
        fn($kC, $vC, $kO, $vO) => $kC**2 + $vO,
        fn($kC, $vC, $kO, $vO) => $vC + $vO,
        fn() => 0,
        fn() => 0,
        fn() => 0,
        fn() => 0,
        [2, 17],
        [3, 6],
        2,
      ],
      'current-iter-longer' => [
        [2 => 3, 4 => 5, 10 => 13],
        [2 => 2, 1 => 1],
        fn($kC, $vC, $kO, $vO) => $kC + $vO,
        fn($kC, $vC, $kO, $vO) => $vC - $vO,
        fn($kC, $vC) => $vC,
        fn($kC, $vC) => $kC,
        fn() => 0,
        fn() => 0,
        [4, 5, 13],
        [1, 4, 10],
        3,
      ],
      'other-iter-longer' => [
        [2 => 3, -1 => 5],
        [2 => 2, 1 => 1, 20 => 20],
        fn($kC, $vC, $kO, $vO) => $kC + $vO,
        fn($kC, $vC, $kO, $vO) => $vC - $vO,
        fn() => 0,
        fn() => 0,
        fn() => -1,
        fn() => -2,
        [4, 0, -1],
        [1, 4, -2],
        3,
      ],
      'both-empty' => [[], [], fn() => 0, fn() => 0, NULL, NULL, NULL, NULL, [], [], 0],
      'current-empty' => [
        [],
        [2 => 2, 1 => 1],
        fn() => 0,
        fn() => 0,
        fn() => 0,
        fn() => 0,
        fn($kO, $vO) => $kO**2,
        fn($kO, $vO) => $vO**2,
        [4, 1],
        [4, 1],
        2,
      ],
      'other-empty' => [
        [3 => 2, 0 => 1],
        [],
        fn() => 0,
        fn() => 0,
        fn($kC, $vC) => $kC**2 + 1,
        fn($kC, $vC) => $vC**2,
        fn() => 0,
        fn() => 0,
        [10, 1],
        [4, 1],
        2,
      ],
      'optional-maps-null' => [
        [1 => 2],
        [2 => 2, 1 => 8],
        fn($kC, $vC, $kO, $vO) => $kC + $vO,
        fn($kC, $vC, $kO, $vO) => $vC - $vO,
        NULL,
        NULL,
        NULL,
        NULL,
        [3, 1],
        [0, 8],
        2,
      ],
    ];
  }

}
