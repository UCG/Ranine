<?php

declare(strict_types = 1);

namespace Ranine\Tests\Helper;

use Ranine\Exception\ParseException;
use Ranine\Iteration\ExtendableIterable;
use PHPUnit\Framework\TestCase;
use Ranine\Helper\ParseHelpers;

class ParseHelpersTest extends TestCase {

  /**
   * @covers ::parseInt
   */
  public function testParseInt() : void {
    // Input:
    // Number: 5
    // Result: int 5
    $this->assertEquals(5, ParseHelpers::parseInt(5));
  }

  /**
   * @covers ::parseIntFromString
   */
  public function testParseIntFromString() : void {
    // Input:
    // String number: '5'
    // Result: int 5
    $this->assertEquals(5, ParseHelpers::parseInt('5'));
  }

  /**
   * @covers ::parseIntRange
   */
  public function testParseIntRange() : void {
    $divider = '-';
    $range = "[0]$divider[5]";
    $expectedResult = [0, 1, 2, 3, 4, 5];
    $this->assertEquals($expectedResult, ParseHelpers::parseIntRange($range, $divider));
  }

  /**
   * @covers ::parseIntRangeEndpoints
   */
  public function testParseIntRangeEndpoints() : void {

  }

  /**
   * @covers ::tryParseInt
   */
  public function testTryParseInt() : void {

  }

  /**
   * @covers ::tryParseIntFromString
   */
  public function testTryParseIntFromString() : void {

  }

  /**
   * @covers ::tryParseIntRange
   */
  public function testTryParseIntRange() : void {

  }

  /**
   * @covers ::tryParseIntRangeEndpoints
   */
  public function testTryParseIntRangeEndpoints() : void {

  }

}
