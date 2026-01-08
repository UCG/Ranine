<?php

declare(strict_types = 1);

namespace Ranine\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;
use Ranine\Collection\HashSet;
use Ranine\Iteration\ExtendableIterable;

#[CoversClass(HashSet::class)]
#[Group('ranine')]
class HashSetTest extends TestCase {

  /**
   * @param array $items
   *   Items to add.
   */
  #[DataProvider('provideTestAddArguments')]
  #[TestDox('Tests the add() and has() methods.')]
  public function testAdd(array $items) : void {
    // Uses the default hashing / comparison.

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

  public function testGetCount() : void {
    // Uses the default hashing / comparison.

    /** @var \Ranine\Collection\HashSet<int> */
    $set = new HashSet();
    $this->assertTrue($set->getCount() === 0);
    $set->add(2);
    $set->add(0);
    $this->assertTrue($set->getCount() === 2);
  }

  #[TestDox('Tests the remove() and has() method.')]
  public function testRemove() : void {
    // Uses the default hashing / comparison. 

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
