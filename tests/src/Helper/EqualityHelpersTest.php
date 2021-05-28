<?php

declare(strict_types = 1);

namespace Ranine\Tests\Helper;

use PHPUnit\Framework\TestCase;
use Ranine\Helper\EqualityHelpers;

/**
 * Tests the EqualityHelpers class.
 *
 * @coversDefaultClass \Ranine\Helper\EqualityHelpers
 * @group ranine
 */
class RoleHelpersTest extends TestCase {

  /**
   * Tests the areArraysEqualStrictOrderInvariant() method.
   *
   * @covers ::areArraysEqualStrictOrderInvariant
   */
  public function testArrayEqualityStrictOrderInvariant() : void {
    $obj = new \stdClass();
    $baseArray = ['4' => $obj, 5 => [$obj, 2, 4, 5.0], 2 => 7, 3 => ['2', ['a' => 0, 'b' => 'happy']]];
    $equalArrayOnlyWhenRecursiveComparison = [5 => [$obj, 2, 4, 5.0], '4' => $obj, 2 => 7, 3 => ['2', ['b' => 'happy', 'a' => 0]]];
    $equalArrayWhenNoRecursion = [2 => 7, '4' => $obj, 5 => [$obj, 2, 4, 5.0], 3 => ['2', ['a' => 0, 'b' => 'happy']]];
    $nonEqualArray = ['4' => $obj, 5 => [$obj, 2, '4', 5.0], 2 => 7, 3 => ['2', ['a' => 0, 'b' => 'happy']]];

    $this->assertTrue(EqualityHelpers::areArraysEqualStrictOrderInvariant($baseArray, $equalArrayWhenNoRecursion));
    $this->assertTrue(EqualityHelpers::areArraysEqualStrictOrderInvariant($baseArray, $equalArrayOnlyWhenRecursiveComparison, TRUE));
    $this->assertFalse(EqualityHelpers::areArraysEqualStrictOrderInvariant($baseArray, $equalArrayOnlyWhenRecursiveComparison));
    $this->assertFalse(EqualityHelpers::areArraysEqualStrictOrderInvariant($baseArray, $nonEqualArray, TRUE));
  }

}
