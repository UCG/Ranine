<?php

declare(strict_types = 1);

namespace Ranine\Tests\Iteration;

use PHPUnit\Framework\TestCase;
use Ranine\Iteration\ExtendableIterable;

/**
 * Tests the ExtendableIterable class.
 *
 * @coversDefaultClass \Ranine\Iteration\ExtendableIterable
 * @group ranine
 */
class ExtendableIterableTest extends TestCase {

  /**
   * Test the all() method with a collection with a single valid item.
   *
   * @covers ::all
   */
  public function testAllSingleValidInput() : void {
    $this->assertTrue(ExtendableIterable::fromKeyAndValue('a', 'b')
      ->all(fn($k, $v) => $k === 'a' && $v === 'b'));
  }

}
