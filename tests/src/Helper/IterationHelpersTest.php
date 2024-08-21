<?php

declare(strict_types = 1);

namespace Ranine\Tests\Helper;

use PHPUnit\Framework\TestCase;
use Ranine\Helper\IterationHelpers;

/**
 * Tests the IterationHelpers class.
 *
 * @coversDefaultClass \Ranine\Helper\IterationHelpers
 * @group ranine
 */
class IterationHelpersTest extends TestCase {

  /**
   * Tests the walkRecursiveIterator() method.
   *
   * @covers ::walkRecursiveIterator
   */
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
      },
      function(int $key, $value, ?int &$context, ?int &$newContext) {
        $newContext = $key;
        return TRUE;
      }, function (?int $context) use (&$currentSum, $sumOfValues) : bool {
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

  /**
   * Tests context modification (by reference) for walkRecursiveIterator().
   *
   * @covers ::walkRecursiveIterator
   */
  public function testWalkRecursiveIteratorContextModification() : void {
    $arr = [4 => [2, 3], 6 => 3];
    /** @var \RecursiveArrayIterator<int, int[]|int> */
    $iterator = new \RecursiveArrayIterator($arr);
    // At each level, store a reference to the actual array as the context.
    $this->assertTrue(IterationHelpers::walkRecursiveIterator($iterator,
      function (int $key, int|array $value, array &$context) : bool {
        if ($key === 6) $context[$key] = 11;
        elseif ($value === 3) $context[$key] = 13;
        return TRUE;
      }, function (int $key, $value, array &$context, ?array &$newContext) : bool {
        $newContext =& $context[$key];
        return TRUE;
      }, NULL, $arr));
    $this->assertArrayHasKey(6, $arr);
    $this->assertEquals(11, $arr[6]);
    $this->assertArrayHasKey(4, $arr);
    $this->assertArrayHasKey(1, $arr[4]);
    $this->assertEquals(13, $arr[4][1]);
  }

}
