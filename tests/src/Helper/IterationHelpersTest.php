<?php

declare(strict_types = 1);

namespace Ranine\Tests\Helper;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;
use Ranine\Helper\IterationHelpers;
use Ranine\Iteration\RecursiveReferenceArrayIterator;

#[TestDox('Tests the IterationHelpers class.')]
#[CoversClass(IterationHelpers::class)]
#[Group('ranine')]
class IterationHelpersTest extends TestCase {

  #[TestDox('Tests the walkRecursiveIterator() method.')]
  #[CoversFunction('walkRecursiveIterator')]
  public function testWalkRecursiveIteator() : void {
    $arr = [
      1 => [2, 3, 4 => [5, 6, 7], 8 => [9, 10]],
      11 => [12, 13, 14],
    ];
    $sumOfValues = 81;

    $currentSum = 0;
    $this->assertTrue(IterationHelpers::walkRecursiveIterator(new \RecursiveArrayIterator($arr),
      function ($key, $value, ?int &$context) use (&$currentSum) : bool {
        if (is_array($value)) {
          return TRUE;
        }

        $currentSum += $value;
        switch ($value) {
          case 2:
          case 3:
            return $context === 1 ? TRUE : FALSE;

          case 5:
          case 6:
          case 7:
            return $context === 4 ? TRUE : FALSE;

          case 9:
          case 10:
            return $context === 8 ? TRUE : FALSE;

          case 12:
          case 13:
          case 14:
            return $context === 11 ? TRUE : FALSE;

          default:
            return FALSE;
        }
      }, fn&(int $key) : int => $key,
      function (?int $context) use (&$currentSum, $sumOfValues) : bool {
        switch ($context) {
          case NULL:
            return $currentSum === $sumOfValues ? TRUE : FALSE;

          case 1:
            return $currentSum === 42 ? TRUE : FALSE;

          case 4:
            return $currentSum === 23 ? TRUE : FALSE;

          case 8:
            return $currentSum === 42 ? TRUE : FALSE;

          case 11:
            return $currentSum === $sumOfValues ? TRUE : FALSE;

          default:
            return FALSE;
        }
      }));

    $this->assertTrue($currentSum === $sumOfValues);
  }

  #[TestDox('Tests context modification (by reference) for walkRecursiveIterator().')]
  #[CoversFunction('walkRecursiveIterator')]
  public function testWalkRecursiveIteratorContextModification() : void {
    $arr = [4 => [2, 3], 6 => 3];
    /** @var \Ranine\Iteration\RecursiveReferenceArrayIterator<int, int[]|int> */
    $iterator = new RecursiveReferenceArrayIterator($arr);
    $sumOfValues = 0;
    $sumOfKeys = 0;
    // At each level, store a reference to the actual array as the context.
    $this->assertTrue(IterationHelpers::walkRecursiveIterator($iterator,
      function (int|string $key, int|array $value, array &$context) use(&$sumOfValues, &$sumOfKeys) : bool {
        if ($key === 6) $context[$key] = 11;
        elseif ($value === 3) $context[$key] = 13;
        $sumOfKeys += $key;
        if (is_int($value)) $sumOfValues += $value;
        return TRUE;
      }, function &(int $key, $value, array &$context) : array {
        return $context[$key];
      }, NULL, $arr));
    $this->assertArrayHasKey(6, $arr);
    $this->assertEquals(11, $arr[6]);
    $this->assertArrayHasKey(4, $arr);
    $this->assertArrayHasKey(1, $arr[4]);
    $this->assertEquals(13, $arr[4][1]);
    $this->assertEquals(11, $sumOfKeys);
    $this->assertEquals(8, $sumOfValues);
  }

}
