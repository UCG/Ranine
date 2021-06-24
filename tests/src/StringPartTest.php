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
    // ~ C. S. Lewis, The Abolition of Man

    $part = StringPart::create('::' . $firstSentence, 2, strlen($firstSentence) + 1);
    $part->append($secondSentence);

    $comparison = $firstSentence . $secondSentence;

    $this->assertTrue(substr_compare($part->getBackingString(), $comparison, $part->getStartPosition(), $part->getLength()) === 0);
    $this->assertTrue(((string) $part) === $comparison);

    $part = StringPart::create('::' . $firstSentence . '::', 2, strlen($firstSentence) + 1);
    $part->append($secondSentence);
    $this->assertTrue(substr_compare($part->getBackingString(), $comparison, $part->getStartPosition(), $part->getLength()) === 0);
    $this->assertTrue(((string) $part) === $comparison);
  }

  /**
   * Tests the clean() method.
   *
   * @covers ::clean
   */
  public function testClean() : void {
    $sentence = 'But the Court has cheated both sides, robbing the winners of an honest victory, and the losers of the peace that comes from a fair defeat.';
    // ~ A. Scalia, J., dissenting in United States v. Windsor

    $part = StringPart::create('::.' . $sentence . 'jku', 3, strlen($sentence) + 2);
    $part->clean();
    $this->assertTrue($part->getBackingString() === $sentence);
  }

  /**
   * Tests the recut() method.
   *
   * @covers ::recut
   */
  public function testRecut() : void {
    $firstSentence = 'We laugh at honour and are shocked to find traitors in our midst. ';
    $secondSentence = 'We castrate and bid the geldings be fruitful.';
    // ~ C. S. Lewis, The Abolition of Man

    $part = StringPart::create('::' . $firstSentence . $secondSentence . 'jk', 1, strlen($firstSentence) + strlen($secondSentence));
    $part->recut(2, strlen($firstSentence) + 1);
    $this->assertTrue(substr_compare($part->getBackingString(), $firstSentence, $part->getStartPosition(), $part->getLength()) === 0);
    $this->assertTrue(((string) $part) === $firstSentence);
  }

  /**
   * Tests the withNewEndpoints() method.
   *
   * @covers ::withNewEndpoints
   */
  public function testWithNewEndpoints() : void {
    $firstSentence = 'The whole point of seeing through something is to see something through it. ';
    $secondSentence = 'To "see through" all things is the same as not to see.';
    // ~ C. S. Lewis, The Abolition of Man

    $part = StringPart::create('::' . $firstSentence . $secondSentence . 'jk', 1, strlen($firstSentence) + strlen($secondSentence));
    $result = $part->withNewEndpoints(2, strlen($firstSentence) + 1);
    $this->assertTrue(substr_compare($result->getBackingString(), $firstSentence, $result->getStartPosition(), $result->getLength()) === 0);
    $this->assertTrue(((string) $result) === $firstSentence);
  }

}
