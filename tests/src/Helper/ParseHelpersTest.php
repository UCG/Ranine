<?php

declare(strict_types = 1);

namespace Ranine\Tests\Helper;

use PHPUnit\Framework\TestCase;
use Ranine\Exception\ParseException;
use Ranine\Helper\ParseHelpers;
use Ranine\Tests\Traits\IterableAssertionTrait;

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
   * @dataProvider provideDataForTestParseIntFromString
   */
  public function testParseIntFromString(string $inputString, int $expectedResult) : void {
    $this->assertEquals($expectedResult, ParseHelpers::parseIntFromString($inputString));
  }

  /**
   * @covers ::parseIntFromString
   * @dataProvider provideDataForTestParseIntFromStringInvalid
   */
  public function testParseIntFromStringInvalid(string $valueToTryToParse) : void {
    $this->expectException(ParseException::class);
    ParseHelpers::parseIntFromString($valueToTryToParse);
  }

  /**
   * @covers ::parseIntRange
   * @dataProvider provideDataForTestParseIntRange
   */
  public function testParseIntRange(string $start, string $divider, string $end, array $expectedValues, int $expectedCount) : void {
    $range = $start . $divider . $end;
    $this->assertIterableValues(ParseHelpers::parseIntRange($range, $divider), $expectedValues, $expectedCount);
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

  public function provideDataForTestParseIntFromString() : array {
    return [
      'negative-int' => ['-9', -9],
      'positive-int' => ['77', 77],
    ];
  }

  public function provideDataForTestParseIntFromStringInvalid() : array {
    return [
      'bool' => ['True'],
      'letters' => ['a1a'],
      'symbols' => ['@'],
      'float' => ['0.01']
    ];
  }

  public function provideDataForTestParseIntRange() : array {
    return [
      'start-end-negative' => ['-7', '/', '-2', [-7, -6, -5, -4, -3, -2], 6],
      'same-start-and-end' => ['0', '|', '0', [0], 1],
      'ordinary-start-divider-and-end' => ['1', 'A', '9', [1, 2, 3, 4, 5, 6, 7, 8, 9], 9],
    ];
  }
  
  public function provideDataForTestParseIntRangeInvalid() : array {
    return [
      'start-greater-than-end' => ['5', '-', '3'],
      'start-divider-same' => ['7', '7', '12'],
      'invalid-divider' => [],
      'empty-divider' => [],
      'empty-start' => [],
      'empty-end' => [],
      'start-is-float' => [],
      'end-is-bool' => [],
    ];
  }
   
}
