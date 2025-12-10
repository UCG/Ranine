<?php

declare(strict_types = 1);

namespace Ranine\Tests;

use PHPUnit\Framework\TestCase;
use Ranine\Collection\HashSet;
use Ranine\Iteration\ExtendableIterable;

/**
 * Tests the HashSet class.
 *
 * @coversDefaultClass \Ranine\Collection\HashSet
 * @group ranine
 */
class HashSetTest extends TestCase {

  /**
   * Tests the add() and has() methods.
   *
   * Uses the default hashing / comparison.
   *
   * @covers ::add
   * @covers ::has
   * @dataProvider provideTestAddArguments
   *
   * @param array $items
   *   Items to add.
   */
  public function testAdd(array $items) : void {
    $set = new HashSet();
    $oneIterationCompleted = FALSE;
    foreach ($items as $item) {
      if (!$oneIterationCompleted) $this->assertTrue($set->add($item));
      else $set->add($item);
      $this->assertFalse($set->add($item));
      $oneIterationCompleted = TRUE;
    }
    
    $this->assertTrue(ExtendableIterable::from($items)->all(fn($k, $item) => $set->has($item)));
  }

  /**
   * Tests the getCount() method.
   *
   * Uses the default hashing / comparison.
   *
   * @covers ::getCount
   */
  public function testGetCount() : void {
    /** @var \Ranine\Collection\HashSet<int> */
    $set = new HashSet();
    $this->assertTrue($set->getCount() === 0);
    $set->add(2);
    $set->add(0);
    $this->assertTrue($set->getCount() === 2);
  }

  /**
   * Tests the remove() and has() method.
   *
   * Uses the default hashing / comparison.
   *
   * @covers ::remove
   * @covers ::has
   */
  public function testRemove() : void {
    /** @var \Ranine\Collection\HashSet<int> */
    $set = new HashSet();
    $this->assertFalse($set->remove(0));
    $set->add(3);
    $set->add(5);
    $this->assertTrue($set->remove(5));
    $this->assertFalse($set->has(5));
    $this->assertTrue($set->has(3));
  }

  /**
   * Provides arguments for testAdd().
   *
   * @return mixed[][][]
   *   Arguments.
   */
  public static function provideTestAddArguments() : array {
    return [
      [[
        'jkuu',
        'jk',
        'uiu',
      ]],
      [[
        0,
        4,
        5,
      ]],
      [[
        0.0,
        -1.0,
        -1.01,
        1,
      ]],
      [[
        ['a' => 'b', 'c' => 'd'],
        ['a' => 'c', 'c' => 'e'],
        ['a' => 'b', 'c' => 'd', 'e' => 'f'],
        ['c' => 'd', 'a' => 'b'],
        ['a' => 'b'],
        ['c' => 'd'],
        [0, 1, 2],
        ['a', 'b', 'c', 'd'],
        ['a', 'b'],
        ['a', 'c'],
      ]],
    ];
  }

}
