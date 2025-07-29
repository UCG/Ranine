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
   * @covers ::parseIntRange
   * @dataProvider provideDataForTestParseIntRangeInvalidRange
   */
  public function testParseIntRangeInvalidRange(string $range, string $divider) : void {
    $this->expectException(ParseException::class);
    ParseHelpers::parseIntRange($range, $divider);
  }

  /**
   * @covers ::parseIntRange
   */
  public function testParseIntRangeEmptyDivider() : void {
    $this->expectException(\InvalidArgumentException::class);
    ParseHelpers::parseIntRange('2-3', '');
  }

  /**
   * @covers ::parseIntRangeEndpoints
   * @dataProvider provideDataForTestParseIntRangeEndpoints
   */
  public function testParseIntRangeEndpoints(string $range, string $divider, int $expectedStart, int $expectedEnd) : void {
    $start = 0;
    $end = 0;
    ParseHelpers::parseIntRangeEndpoints($range, $start, $end, $divider);
    $this->assertSame($expectedStart, $start);
    $this->assertSame($expectedEnd, $end);
  }

  /**
   * @covers ::parseIntRangeEndpoints
   * @dataProvider provideDataForTestParseIntRangeEndpointsInvalid
   */
  public function testParseIntRangeEndpointsInvalid(string $range, string $divider, int $start, int $end) : void {
    $this->expectException(ParseException::class);
    ParseHelpers::parseIntRangeEndpoints($range, $start, $end, $divider);
  }

  /**
   * @covers ::tryParseInt
   * @dataProvider provideDataForTestTryParseIntGoodData
   */
  public function testTryParseIntGoodData(mixed $inputData, int $expectedResult) : void {
    $result = 0;
    $this->assertSame(TRUE, ParseHelpers::tryParseInt($inputData, $result));
    $this->assertSame($expectedResult, $result);    
  }

  /**
   * @covers ::tryParseInt
   * @dataProvider provideDataForTestTryParseIntBadData
   */
  public function testTryParseIntBadData(mixed $inputData) : void {
    $result = 0;
    $this->assertFalse(ParseHelpers::tryParseInt($inputData, $result));
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
      'three-dividers-at-start' => ['1', '1', '10', [1, 2, 3, 4, 5, 6, 7, 8, 9, 10], 10],
      'three-dividers-at-end' => ['9', '1', '11', [9, 10, 11], 3],
      'start-end-negative' => ['-4', '-', '-1', [-4, -3, -2, -1], 4],
      'start-negative-end-positive' => ['-5', '-', '6', [-5, -4, -3, -2, -1, 0, 1, 2, 3, 4, 5, 6], 12],
    ];
  }
  
  public function provideDataForTestParseIntRangeInvalidRange() : array {
    return [
      'start-greater-than-end' => ['5-3', '-'],
      'empty-start' => ['&3', '&'],
      'empty-end' => ['3-', '-'],
      'start-is-float' => ['3.0:5.0', ':'],
      'end-is-bool' => ['2:FALSE', ':'],
      'both-endpoints-non-numeric' => ['a-b', '-'],
      'range-empty' => ['', '-'],
      'two-dividers-together' => ['5$$9', '$'],
      'two-dividers-with-int-between' => ['7(8(9', '('],
      'divider-is-dash-and-start-is-negative' => ['"-5"-1', '-'],
    ];
  }

  public function provideDataForTestParseIntRangeEndpoints() : array {
    return [
      'start-end-negative' => ['-4--1', '-', -4, -1],
      'start-negative-end-positive' => ['-5-6', '-', -5, 6],
      'start-end-same' => ['3&3', '&', 3, 3],
      'start-end-both-positive' => ['2+8', '+', 2, 8],
      'three-dividers-at-start' => ['1110', '1', 1, 10],
      'three-dividers-at-end' => ['9111', '1', 9, 11],
      'start-divider-same' => ['7712', '7', 7, 12],
    ];
  }

  public function provideDataForTestParseIntRangeEndpointsInvalid() : array {
    return [
      'start-greater-than-end' => ['5-3', '-', 5, 3],
      'empty-start' => ['&3', '&', 0, 3],
      'empty-end' => ['3-', '-', 3, 0],
      'start-is-float' => ['3.0:5.0', ':', 3, 5],
      'end-is-bool' => ['2:FALSE', ':', 2, 0],
      'both-endpoints-non-numeric' => ['a-b', '-', 1, 2],
      'range-empty' => ['', '-', 0, 0],
      'two-dividers-together' => ['5$$9', '$', 5, 9],
      'two-dividers-with-int-between' => ['7(8(9', '(', 7, 9],
    ];
  }

  public function provideDataForTestTryParseIntGoodData() : array {
    return [
      'int' => [777, 777],
      'int-string' => ['56', 56],
      '0' => ['0', 0],
      'negative-int' => [-4, -4],
      'negative-int-string' => ['-4', -4],
    ];
  }
  
  public function provideDataForTestTryParseIntBadData() : array {
    return [
      'empty' => [''],
      'float' => [1.1],
      'float-string' => ['1.1'],
      'int-string-too-large' => [(string) (PHP_INT_MAX*10.0)],
      'bad-string' => ['8a8'],
      'math' => ['5 + 9'],
    ];
  }


}
