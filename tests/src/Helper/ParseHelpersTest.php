<?php

declare(strict_types = 1);

namespace Ranine\Tests\Helper;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Ranine\Exception\ParseException;
use Ranine\Helper\ParseHelpers;
use Ranine\Tests\Traits\IterableAssertionTrait;

#[CoversClass(ParseHelpers::class)]
#[CoversMethod('ParseHelpers','parseInt')]
#[CoversMethod('ParseHelpers','parseIntFromString')]
#[CoversMethod('ParseHelpers','parseIntRange')]
#[CoversMethod('ParseHelpers','parseIntRangeEndpoints')]
#[CoversMethod('ParseHelpers','tryParseInt')]
#[CoversMethod('ParseHelpers','tryParseIntFromString')]
#[CoversMethod('ParseHelpers','tryParseIntRange')]
#[CoversMethod('ParseHelpers','tryParseIntRangeEndpoints')]
class ParseHelpersTest extends TestCase {

  use IterableAssertionTrait;

  #[DataProvider('provideBadDataForParseIntAndTryParseIntTests')]
  public function testParseIntBadData(mixed $valueToTryToParse) : void {
    $this->expectException(ParseException::class);
    ParseHelpers::parseInt($valueToTryToParse);
  }

  #[DataProvider('provideGoodDataForParseIntAndTryParseIntTests')]
  public function testParseIntGoodData(int|string $valueToTryToParse, int $expectedResult) : void {
    $this->assertEquals($expectedResult, ParseHelpers::parseInt($valueToTryToParse));
  }

  #[DataProvider('provideGoodDataForParseIntFromStringAndTryTests')]
  public function testParseIntFromString(string $inputString, int $expectedResult) : void {
    $this->assertEquals($expectedResult, ParseHelpers::parseIntFromString($inputString));
  }

  #[DataProvider('provideBadDataForParseIntFromStringAndTryTests')]
  public function testParseIntFromStringInvalid(string $valueToTryToParse) : void {
    $this->expectException(ParseException::class);
    ParseHelpers::parseIntFromString($valueToTryToParse);
  }

  #[DataProvider('provideDataForParseIntRangeAndTryParseIntRangeTests')]
  public function testParseIntRange(string $range, string $divider, array $expectedValues, int $expectedCount) : void {
    $this->assertIterableValues(ParseHelpers::parseIntRange($range, $divider), $expectedValues, $expectedCount);
  }

  public function testParseIntRangeEmptyDivider() : void {
    $this->expectException(\InvalidArgumentException::class);
    ParseHelpers::parseIntRange('2-3', '');
  }

  #[DataProvider('provideGoodDataForParseIntRangeEndpointsAndTryTests')]
  public function testParseIntRangeEndpoints(string $range, string $divider, int $expectedStart, int $expectedEnd) : void {
    $start = 0;
    $end = 0;
    ParseHelpers::parseIntRangeEndpoints($range, $start, $end, $divider);
    $this->assertSame($expectedStart, $start);
    $this->assertSame($expectedEnd, $end);
  }

  public function testParseIntRangeEndpointsInvalidDivider() : void {
    $start = 0;
    $end = 0;
    $range = '0-0';
    $divider = '';
    $this->expectException(\InvalidArgumentException::class);
    ParseHelpers::parseIntRangeEndpoints($range, $start, $end, $divider);
  }

  #[DataProvider('provideBadDataForParseIntRangeEndpointsAndTryTests')]
  public function testParseIntRangeEndpointsInvalidRange(string $range, string $divider) : void {
    $start = 0;
    $end = 0;
    $this->expectException(ParseException::class);
    ParseHelpers::parseIntRangeEndpoints($range, $start, $end, $divider);
  }

  #[DataProvider('provideInvalidRangeDataForParseIntRangeAndTryParseIntRangeTests')]
  public function testParseIntRangeInvalidRange(string $range, string $divider) : void {
    $this->expectException(ParseException::class);
    ParseHelpers::parseIntRange($range, $divider);
  }

  #[DataProvider('provideBadDataForParseIntAndTryParseIntTests')]
  public function testTryParseIntBadData(mixed $inputData) : void {
    $result = 0;
    $this->assertFalse(ParseHelpers::tryParseInt($inputData, $result));
  }

  #[DataProvider('provideGoodDataForParseIntAndTryParseIntTests')]
  public function testTryParseIntGoodData(int|string $valueToTryToParse, int $expectedResult) : void {
    $result = 0;
    $this->assertSame(TRUE, ParseHelpers::tryParseInt($valueToTryToParse, $result));
    $this->assertSame($expectedResult, $result);
  }

  #[DataProvider('provideGoodDataForParseIntFromStringAndTryTests')]
  public function testTryParseIntFromString(mixed $inputData, $expectedResult) : void {
    $result = 0;
    $this->assertSame(TRUE, ParseHelpers::tryParseIntFromString($inputData, $result));
    $this->assertSame($expectedResult, $result);
  }

  #[DataProvider('provideBadDataForParseIntFromStringAndTryTests')]
  public function testTryParseIntFromStringInvalid(mixed $inputData) : void {
    $result = 0;
    $this->assertSame(FALSE, ParseHelpers::tryParseIntFromString($inputData, $result));
  }

  #[DataProvider('provideDataForParseIntRangeAndTryParseIntRangeTests')]
  public function testTryParseIntRange(string $range, string $divider, array $expectedValues, int $expectedCount) : void {
    $output = NULL;
    $succeeded = ParseHelpers::tryParseIntRange($range, $output, $divider);

    $this->assertTrue($succeeded);
    $this->assertIsIterable($output);
    $this->assertIterableValues($output, $expectedValues, $expectedCount);
  }

  public function testTryParseIntRangeEmptyDivider() : void {
    $output = NULL;
    $this->expectException(\InvalidArgumentException::class);
    ParseHelpers::tryParseIntRange('2-3', $output, '');
  }

  #[DataProvider('provideGoodDataForParseIntRangeEndpointsAndTryTests')]
  public function testTryParseIntRangeEndpoints(string $range, string $divider, int $expectedStart, int $expectedEnd) : void {
    $start = 0;
    $end = 0;
    $result = ParseHelpers::tryParseIntRangeEndpoints($range, $start, $end, $divider);
    $this->assertSame($expectedStart, $start);
    $this->assertSame($expectedEnd, $end);
    $this->assertTrue($result);
  }

  public function testTryParseIntRangeEndpointsInvalidDivider() : void {
    $start = 0;
    $end = 0;
    $range = '0-0';
    $divider = '';
    $this->expectException(\InvalidArgumentException::class);
    ParseHelpers::tryParseIntRangeEndpoints($range, $start, $end, $divider);
  }

  #[DataProvider('provideBadDataForParseIntRangeEndpointsAndTryTests')]
  public function testTryParseIntRangeEndpointsInvalidRange(string $range, string $divider) : void {
    $start = 0;
    $end = 0;
    $result = ParseHelpers::tryParseIntRangeEndpoints($range, $start, $end, $divider);
    $this->assertFalse($result);    
  }

  #[DataProvider('provideInvalidRangeDataForParseIntRangeAndTryParseIntRangeTests')]
  public function testTryParseIntRangeInvalidRange(string $range, string $divider) : void {
    $output = NULL;
    $this->assertFalse(ParseHelpers::tryParseIntRange($range, $output, $divider));
  }

  public static function provideBadDataForParseIntAndTryParseIntTests() : array {
    return [
      'empty' => [''],
      'float' => [1.1],
      'float-string' => ['1.1'],
      'int-string-too-large' => [(string) (PHP_INT_MAX*10.0)],
      'bad-string' => ['8a8'],
      'math' => ['5 + 9'],
      'strange-type' => [NULL],
      'really-bad-string' => ['abackjsdf!!'],
    ];
  }

  public static function provideBadDataForParseIntFromStringAndTryTests() : array {
    return [
      'bool' => ['True'],
      'letters' => ['a1a'],
      'symbols' => ['@'],
      'float' => ['0.01'],
      'int-written-out' => ['one'],
    ];
  }

  public static function provideBadDataForParseIntRangeEndpointsAndTryTests() : array {
    return [
      'start-greater-than-end' => ['5-3', '-'],
      'empty-start' => ['&3', '&'],
      'empty-end' => ['3-', '-'],
      'start-is-float' => ['3.0:5.0', ':'],
      'end-is-bool' => ['2:FALSE', ':'],
      'both-endpoints-non-numeric' => ['a-b', '-'],
      'range-empty' => ['', '-'],
      'two-dividers-together' => ['5$$9', '$'],
      'two-dividers-with-int-between' => ['7(8(9', '(', 7, 9],
    ];
  }

  public static function provideDataForParseIntRangeAndTryParseIntRangeTests() : array {
    return [
      'start-end-negative' => ['-7/-2', '/', [-7, -6, -5, -4, -3, -2], 6],
      'same-start-and-end' => ['0|0', '|', [0], 1],
      'ordinary-start-divider-and-end' => ['1A9', 'A', [1, 2, 3, 4, 5, 6, 7, 8, 9], 9],
      'three-dividers-at-start' => ['1110', '1', [1, 2, 3, 4, 5, 6, 7, 8, 9, 10], 10],
      'three-dividers-at-end' => ['9111', '1', [9, 10, 11], 3],
      'start-end-negative' => ['-4--1', '-', [-4, -3, -2, -1], 4],
      'start-negative-end-positive' => ['-5-6', '-', [-5, -4, -3, -2, -1, 0, 1, 2, 3, 4, 5, 6], 12],
    ];
  }

  public static function provideGoodDataForParseIntAndTryParseIntTests() : array {
    return [
      'int' => [777, 777],
      'int-string' => ['56', 56],
      '0' => ['0', 0],
      'zero-int' => [0, 0],
      'negative-int' => [-4, -4],
      'negative-int-string' => ['-4', -4],
    ];
  }

  public static function provideGoodDataForParseIntFromStringAndTryTests() : array {
    return [
      'negative-int' => ['-9', -9],
      'positive-int' => ['77', 77],
      'zero' => ['0', 0],
      'max-int' => [(string) PHP_INT_MAX, PHP_INT_MAX],
    ];
  }

  public static function provideGoodDataForParseIntRangeEndpointsAndTryTests() : array {
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

  public static function provideInvalidRangeDataForParseIntRangeAndTryParseIntRangeTests() : array {
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

}
