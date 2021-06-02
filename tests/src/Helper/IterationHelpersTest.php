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
      function ($key, $value, ?int $context) use (&$currentSum) : bool {
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
      fn($k) => $k, NULL));
    $this->assertTrue($currentSum === $sumOfValues);
  }
}
