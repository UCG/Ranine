<?php

declare(strict_types = 1);

namespace Ranine\Tests\Helper;

use PHPUnit\Framework\TestCase;
use Ranine\Helper\CollectionHelpers;

/**
 * Tests the CollectionHelpers class.
 *
 * @coversDefaultClass \Ranine\Helper\CollectionHelpers
 * @group ranine
 */
class CollectionHelpersTest extends TestCase {

  /**
   * Tests the getSortedRanges() method.
   *
   * @covers ::getSortedRanges
   */
  public function testGetSortedRanges() : void {
    $output1 = CollectionHelpers::getSortedRanges([-1, 0, 2, 3, 4, 5, 7, 8, 10, 12, 14, 15, 16, 17]);
    $output2 = CollectionHelpers::getSortedRanges([-1, 0, 2, 3, 4, 5, 7, 8, 10, 12, 14, 15, 17]);
    $output3 = CollectionHelpers::getSortedRanges([-1, 2, 3, 4, 5, 7, 8, 10, 12, 14, 15, 17]);
    $output4 = CollectionHelpers::getSortedRanges([4]);
    $output5 = CollectionHelpers::getSortedRanges([]);

    $this->assertTrue($output1->toArray() === [-1 => 0, 2 => 5, 7 => 8, 10 => 10, 12 => 12, 14 => 17]);
    $this->assertTrue($output2->toArray() === [-1 => 0, 2 => 5, 7 => 8, 10 => 10, 12 => 12, 14 => 15, 17 => 17]);
    $this->assertTrue($output3->toArray() === [-1 => -1, 2 => 5, 7 => 8, 10 => 10, 12 => 12, 14 => 15, 17 => 17]);
    $this->assertTrue($output4->toArray() === [4 => 4]);
    $this->assertTrue($output5->isEmpty());
  }

}
