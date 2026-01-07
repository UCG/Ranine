<?php

declare(strict_types = 1);

namespace Ranine\Tests\Helper;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;
use Ranine\Helper\EqualityHelpers;

#[CoversClass(EqualityHelpers::class)]
#[CoversMethod('EqualityHelpers','areArraysEqualStrictOrderInvariant')]
#[Group('ranine')]
#[TestDox('Tests the EqualityHelpers class.')]
class EqualityHelpersTest extends TestCase {

  #[TestDox('Tests the areArraysEqualStrictOrderInvariant() method.')]
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
