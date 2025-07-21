<?php

declare(strict_types = 1);

namespace Ranine\Tests\Helper;

use PHPUnit\Framework\TestCase;
use Ranine\Exception\ParseException;
use Ranine\Helper\ParseHelpers;
use Ranine\Tests\Trait\IterableAssertionTrait;

class ParseHelpersTest extends TestCase {

  use IterableAssertionTrait;

  /**
   * @covers ::parseInt
   * @dataProvider provideDataForTestParseInt
   */
  public function testParseInt(int|string $valueToTryToParse, int $expectedResult) : void {
    $this->assertEquals($expectedResult, ParseHelpers::parseInt($valueToTryToParse));
  }

  /**
   * @covers ::parseInt
   * @dataProvider provideDataForTestParseIntInvalid
   */
  public function testParseIntInvalid(mixed $valueToTryToParse) : void {
    $this->expectException(ParseException::class);
    ParseHelpers::parseInt($valueToTryToParse);
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

  public function provideDataForTestParseInt() : array {
    return [
      'zero-int' => [0, 0],
      'zero-string' => ['0', 0],
      'negative-int' => [-4, -4],
      'negative-string' => ['-23472', -23472],
      'positive-int' => [PHP_INT_MAX, PHP_INT_MAX],
      'positive-string' => ['1', 1],
    ];
  }

  public function provideDataForTestParseIntInvalid() : array {
    return [
      'strange-type' => [NULL],
      'empty-string' => [''],
      'bad-string' => ['4a'],
      'really-bad-string' => ['abackjsdf!!'],
      'float' => [4.0],
    ];
  }

}
