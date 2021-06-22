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
   * Tests the condenseAndSortRanges() method.
   *
   * @covers ::condenseAndSortRanges
   */
  public function testCondenseAndSortRanges() : void {
    $output1 = CollectionHelpers::condenseAndSortRanges([-1 => 3, 3 => 4, 5 => 5, 7 => 7, 8 => 8, -4 => 1, 18 => 20]);
    $output2 = CollectionHelpers::condenseAndSortRanges([-1 => -1, -2 => -1, 5 => 5, 5 => 6, 7 => 7]);
    $output3 = CollectionHelpers::condenseAndSortRanges([0 => 0]);
    $output4 = CollectionHelpers::condenseAndSortRanges([]);

    $this->assertTrue($output1->toArray() === [-4 => 5, 7 => 8, 18 => 20]);
    $this->assertTrue($output2->toArray() === [-2 => -1, 5 => 7]);
    $this->assertTrue($output3->toArray() === [0 => 0]);
    $this->assertTrue($output4->isEmpty());
  }

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
