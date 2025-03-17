<?php

declare(strict_types=1);

namespace Ranine\Tests\Helper;

use PHPUnit\Framework\TestCase;
use Ranine\Helper\StringHelpers;

/**
 * Tests the StringHelpers class.
 *
 * @coversDefaultClass \Ranine\Helper\StringHelpers
 * @group ranine
 */
class StringHelpersTest extends TestCase {

  /**
   * Tests the emptyToNull() method with null input.
   *
   * @covers ::emptyToNull
   */
  public function testEmptyToNullNullInput() {
    // Input: $str = NULL.
    // Expected output: NULL.
  }

  /**
   * Tests the emptyToNull() method with an empty string.
   *
   * @covers ::emptyToNull
   */
  public function testEmptyToNullEmptyInput() {
    // Input: $str = "".
    // Expected output: NULL.
  }

  /**
   * Tests the emptyToNull() method with a non-empty string.
   *
   * @covers ::emptyToNull
   */
  public function testEmptyToNullOrdinaryInput() {
    // Input: $str = 'Hello, there.'
    // Expected output: 'Hello, there.'
  }
}
