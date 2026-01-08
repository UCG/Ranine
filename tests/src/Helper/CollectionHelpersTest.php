<?php

declare(strict_types = 1);

namespace Ranine\Tests\Helper;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;
use Ranine\Helper\CollectionHelpers;

#[CoversClass(CollectionHelpers::class)]
#[Group('ranine')]
class CollectionHelpersTest extends TestCase {

  public function testCondenseAndSortRanges() : void {
    $output1 = CollectionHelpers::condenseAndSortRanges([-1 => 3, 3 => 4, 5 => 5, 7 => 7, 8 => 8, -4 => 1, 18 => 20]);
    $output2 = CollectionHelpers::condenseAndSortRanges([-1 => -1, -2 => -1, 5 => 6, 7 => 7]);
    $output3 = CollectionHelpers::condenseAndSortRanges([0 => 0]);
    $output4 = CollectionHelpers::condenseAndSortRanges([]);
    $this->assertTrue(($output1 instanceof \Traversable ? iterator_to_array($output1) : $output1) === [-4 => 5, 7 => 8, 18 => 20]);
    $this->assertTrue(($output2 instanceof \Traversable ? iterator_to_array($output2) : $output2) === [-2 => -1, 5 => 7]);
    $this->assertTrue(($output3 instanceof \Traversable ? iterator_to_array($output3) : $output3) === [0 => 0]);
    foreach ($output4 as $v) { $this->assertTrue(FALSE); }
  }

  #[DataProvider('provideDataWhereKeyIsGreaterThanValue')]
  #[TestDox("Tests the condenseAndSortRanges() method to make sure exception is 
    thrown when a key is greater than it's value.")]
  public function testCondenseAndSortRangesKeysGreaterThanValues(array $badArray) : void {
    $this->expectException(\InvalidArgumentException::class);
    $result = CollectionHelpers::condenseAndSortRanges($badArray);
    foreach ($result as $v);
  }

  #[DataProvider('provideDataWhereKeyAndOrValueIsNonIntegral')]
  #[TestDox("Tests the condenseAndSortRanges() method to make sure exception is 
    thrown when a key or value in $/ranges is non-integral.")]
  public function testCondenseAndSortRangesNonIntegralKeyOrValue(array $badArray) : void {
    $this->expectException(\InvalidArgumentException::class);
    $result = CollectionHelpers::condenseAndSortRanges($badArray);
    foreach ($result as $v);
  }

  public function testGetSortedRanges() : void {
    $output1 = CollectionHelpers::getSortedRanges([-1, 0, 2, 3, 4, 5, 7, 8, 10, 12, 14, 15, 16, 17]);
    $output2 = CollectionHelpers::getSortedRanges([-1, 0, 2, 3, 4, 5, 7, 8, 10, 12, 14, 15, 17]);
    $output3 = CollectionHelpers::getSortedRanges([-1, 2, 3, 4, 5, 7, 8, 10, 12, 14, 15, 17]);
    $output4 = CollectionHelpers::getSortedRanges([4]);
    $output5 = CollectionHelpers::getSortedRanges([]);

    $this->assertTrue(($output1 instanceof \Traversable ? iterator_to_array($output1) : $output1) === [-1 => 0, 2 => 5, 7 => 8, 10 => 10, 12 => 12, 14 => 17]);
    $this->assertTrue(($output2 instanceof \Traversable ? iterator_to_array($output2) : $output2) === [-1 => 0, 2 => 5, 7 => 8, 10 => 10, 12 => 12, 14 => 15, 17 => 17]);
    $this->assertTrue(($output3 instanceof \Traversable ? iterator_to_array($output3) : $output3) === [-1 => -1, 2 => 5, 7 => 8, 10 => 10, 12 => 12, 14 => 15, 17 => 17]);
    $this->assertTrue(($output4 instanceof \Traversable ? iterator_to_array($output4) : $output4) === [4 => 4]);
    foreach ($output5 as $v) { $this->assertTrue(FALSE); }
  }

  #[TestDox("Tests the getSortedRanges() method to make sure exception is 
    thrown when a value in $/integers was not an integer.")]
  public function testGetSortedRangesValueNotInteger() : void {
    $this->expectException(\InvalidArgumentException::class);
    CollectionHelpers::getSortedRanges([-1, 0, 2, 3, 'a']);
  }

  #[DataProvider('provideDataForRemoveDuplicatesTest')]
  public function testRemoveDuplicatesFromSortedArray(array $input, array $expectedValues, array $expectedKeys) : void {
    CollectionHelpers::removeDuplicatesFromSortedArray($input);

    // Test if values are the same.
    $actualValues = array_values($input);
    $this->assertSame($expectedValues, $actualValues, 'Values do not match.');

    // Test if keys are same.
    $numberOfKeys = count($expectedKeys);
    $actualKeys = array_keys($input);
    for ($i = 0; $i < $numberOfKeys; $i++) {
      $this->assertTrue(in_array($actualKeys[$i], $expectedKeys[$i]));
    }

  }

  public static function provideDataForRemoveDuplicatesTest() : array {
    return [
      'Ordinary' => [
        [-1, -1, 0, 0, 2, 3, 4, 5, 7, 8, 'a' => 10, 10, 12, 14, 15, 16, 17, 17],
        [-1, 0, 2, 3, 4, 5, 7, 8, 10, 12, 14, 15, 16, 17],
        [[0,1], [2,3], [4], [5], [6], [7], [8], [9], ['a', 10], [11], [12], [13], [14], [15, 16]],
      ],
      'All Duplicates' => [
        [-1, -1, 0, 0, 2, 2, 3, 3, 17, 17],
        [-1, 0, 2, 3, 17],
        [[0,1], [2,3], [4,5], [6,7], [8,9]],
      ],
      'Single' => [
        [-1],
        [-1],
        [[0]],
      ],
      'Empty' => [
        [],
        [],
        [],
      ],
      
    ];
  }

  public static function provideDataWhereKeyAndOrValueIsNonIntegral() : array {
    return [
      [[4 => 3, '3.3' => 4]],
      [['5.5' => -7]],
      [[77 => 9.9]],
    ];
  }

  public static function provideDataWhereKeyIsGreaterThanValue() : array {
    return [
      [[4 => 3, 3 => 4]],
      [[55 => -7]],
      [[77 => 9]],
    ];
  }

}
