<?php

declare(strict_types = 1);

namespace Ranine\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;
use Ranine\Collection\HashMap;
use Ranine\Exception\KeyNotFoundException;

#[CoversClass(HashMap::class)]
#[Group('ranine')]
#[TestDox('Tests the HashMap class.')]
class HashMapTest extends TestCase {

  /**
   * @param callable() : iterable $pairsGeneration
   *   Returns key/value pairs to add. Should be idempotent.
   */
  #[CoversFunction('add')]
  #[CoversFunction('get')]
  #[CoversFunction('haskey')]
  #[DataProvider('provideTestAddArgument')]
  #[TestDox('Tests the add(), get() and hasKey() methods. 
    Uses the default hashing / comparison.')]
  public function testAdd(callable $pairsGeneration) : void {
    $map = new HashMap();
    foreach ($pairsGeneration() as $key => $value) {
      $map->add($key, $value);
    }
    foreach ($pairsGeneration() as $key => $value) {
      $this->assertTrue($map->hasKey($key));
      /** @phpstan-ignore-next-line */
      $this->assertTrue($map->get($key) === $value);
    }
  }

  #[CoversFunction('getCount')]
  #[TestDox('Tests the getCount() method.
    Uses the default hashing / comparison.')]
  public function testGetCount() : void {
    $map = new HashMap(NULL, NULL, [2 => 1, 3 => 1]);
    $this->assertTrue($map->getCount() === 2);
  }

  #[CoversFunction('getReference')]
  #[TestDox('Tests the getReference() method.
    Uses the default hashing / comparison.')]
  public function testGetReference() : void {
    $map = new HashMap(NULL, NULL, [2 => 1, 4 => 2]);
    $ref =& $map->getReference(4);
    $ref = 5;
    $this->assertTrue($map->get(4) === 5);
  }

  #[CoversFunction('remove')]
  #[CoversFunction('has')]
  #[TestDox('Tests the remove() and has() method.
    Uses the default hashing / comparison.')]
  public function testRemove() : void {
    $map = new HashMap(NULL, NULL, [2 => 4]);
    $this->assertFalse($map->remove(0));
    $map->add(3, 4);
    $this->assertTrue($map->remove(2));
    $this->assertFalse($map->hasKey(2));
    $this->assertTrue($map->hasKey(3));
  }

  #[CoversFunction('set')]
  #[CoversFunction('remgetove')]
  #[CoversFunction('hasKey')]
  #[TestDox('Tests the set() (and get() and hasKey()) methods.
    Uses the default hashing / comparison.')]
  public function testSet() : void {
    $map = new HashMap(NULL, NULL, [2 => 4, 3 => 4, 4 => 5]);
    $this->assertFalse($map->set(4, 4, FALSE));
    $this->assertTrue($map->get(4) === 4);
    $this->assertTrue($map->set(8, 0, TRUE));
    $this->assertTrue($map->hasKey(8));
    $this->assertTrue($map->get(8) === 0);
    $this->expectException(KeyNotFoundException::class);
    $map->set(9, 0, FALSE);
  }

  /**
   * Provides arguments for testAdd().
   *
   * @return array<callable() : iterable>[]
   *   Arguments.
   * @phpstan-return array<array{0: callable() : iterable}>
   */
  public static function provideTestAddArgument() : array {
    return [
      [function () : iterable {
        yield 'jkuu' => 2;
        yield 'jk' => 3.0;
        yield 'uiu' => 2;
      }],
      [function () : iterable {
        yield 0 => 0;
        yield 4 => 'a';
        yield 5 => 'b';
      }],
      [function () : iterable {
        yield 0.0 => 'a';
        yield -1.0 => 2;
        yield -1.01 => NULL;
        yield NULL => NULL;
      }],
      [function () : iterable {
        yield ['a' => 'b', 'c' => 'd'] => NULL;
        yield ['a' => 'c', 'c' => 'e'] => 0;
        yield ['a' => 'b', 'c' => 'd', 'e' => 'f'] => 0;
        yield ['c' => 'd', 'a' => 'b'] => NULL;
        yield ['a' => 'b'] => 3;
        yield ['c' => 'd'] => 4;
        yield [0, 1, 2] => 6;
        yield ['a', 'b', 'c', 'd'] => 2;
        yield ['a', 'b'] => '';
        yield ['a', 'c'] => 1;
      }],
    ];
  }

}
