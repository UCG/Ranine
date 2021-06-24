<?php

declare(strict_types = 1);

namespace Ranine\Tests;

use PHPUnit\Framework\TestCase;
use Ranine\StringPart;

/**
 * Tests the StringPart class.
 *
 * @coversDefaultClass \Ranine\StringPart
 * @group ranine
 */
class StringPartTest extends TestCase {

  /**
   * Tests the append() method.
   *
   * @covers ::append
   */
  public function testAppend() : void {
    $firstSentence = 'In a sort of ghastly simplicity we remove the organ and demand the function. ';
    $secondSentence = 'We make men without chests and expect of them virtue and enterprise.';

    $part = new StringPart('::' . $firstSentence, 2, strlen($firstSentence) + 1);
    $part->append($secondSentence);
    $this->assertTrue(substr_compare($part->getBackingString(), $firstSentence . $secondSentence, $part->getStartPosition(), $part->getLength()) === 0);

    $part = new StringPart('::' . $firstSentence . '::', 2, strlen($firstSentence) + 1);
    $part->append($secondSentence);
    $this->assertTrue(substr_compare($part->getBackingString(), $firstSentence . $secondSentence, $part->getStartPosition(), $part->getLength()) === 0);
  }

  /**
   * Tests the clean() method.
   *
   * @covers ::clean
   */
  public function testClean() : void {
    $sentence = 'But the Court has cheated both sides, robbing the winners of an honest victory, and the losers of the peace that comes from a fair defeat.';
    $part = new StringPart('::.' . $sentence . 'jku', 3, strlen($sentence) + 2);
    $part->clean();
    $this->assertTrue($part->getBackingString() === $sentence);
  }

  /**
   * Tests the cut() method.
   *
   * @covers ::cut
   */
  public function testCut() : void {
    $firstSentence = 'We make men without chests and expect of them virtue and enterprise. ';
    $secondSentence = 'We laugh at honour and are shocked to find traitors in our midst.';

    $part = new StringPart('::' . $firstSentence . $secondSentence . 'jk', 1, strlen($firstSentence) + strlen($secondSentence));
    $part->cut(1, strlen($firstSentence));
    $this->assertTrue(substr_compare($part->getBackingString(), $firstSentence, $part->getStartPosition(), $part->getLength()) === 0);

    $part = new StringPart('::' . $firstSentence . $secondSentence, 2, strlen($firstSentence) + strlen($secondSentence) + 1);
    $part->cut(strlen($firstSentence));
    $this->assertTrue(substr_compare($part->getBackingString(), $secondSentence, $part->getStartPosition(), $part->getLength()) === 0);
  }

  /**
   * Tests the substring() method.
   *
   * @covers ::substring
   */
  public function testSubstring() : void {
    $firstSentence = 'The whole point of seeing through something is to see something through it. ';
    $secondSentence = 'To "see through" all things is the same as not to see.';

    $part = new StringPart('::' . $firstSentence . $secondSentence . 'jk', 1, strlen($firstSentence) + strlen($secondSentence));
    $result = $part->substring(1, strlen($firstSentence));
    $this->assertTrue(substr_compare($result->getBackingString(), $firstSentence, $result->getStartPosition(), $result->getLength()) === 0);

    $part = new StringPart('::' . $firstSentence . $secondSentence, 2, strlen($firstSentence) + strlen($secondSentence) + 1);
    $result = $part->substring(strlen($firstSentence));
    $this->assertTrue(substr_compare($result->getBackingString(), $secondSentence, $result->getStartPosition(), $result->getLength()) === 0);
  }

}
