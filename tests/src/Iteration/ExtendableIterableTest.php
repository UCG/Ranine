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
   * @dataProvider provideDataForTestAppend - Remember to write one!
   */
  public function testAppend(array $iterData,
    array $iterToAppend,
    array $expectedValues,
    array $expectedKeys,
    int $count) : void {
      
    $iter = ExtendableIterable::from($iterData);
    $appendedIter = $iter->append($iterToAppend);
    $i = 0;
    foreach ($appendedIter as $key => $value) {
      $this->assertSame($expectedValues[$i], $value);
      $this->assertSame($expectedKeys[$i], $key);
      $i++;
    }
    $this->assertSame($count, $i);
  
  }

  public function testAppendKeyAndValue() : void {
    $iter = ExtendableIterable::from([]);
    $appendKeyAndValue = $iter->appendKeyAndValue(5, 8);
    $i = 0;
    $expectedValues = [8];
    $expectedKeys = [5];
    foreach ($appendKeyAndValue as $key => $value) {
      $this->assertSame($expectedValues[$i], $value);
      $this->assertSame($expectedKeys[$i], $key);
      $i++;
    }
  }

  public function provideDataForTestAppend() : array {
    return [
      'empty' => [[],[],[],[],0],
      'single-append' => [[1],[7],[1, 7], [0, 0], 2],
      'bad-key' => []
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

}
