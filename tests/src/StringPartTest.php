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
   * Tests the equals() method.
   *
   * @covers ::equals
   */
  public function testEquals() : void {
    $firstPart = 'It occurred to him that those scarcely perceptible impulses of his to protest what people of high rank considered good,';
    $secondPart = 'vague impulses which he had always suppressed, might have been precisely what mattered,';
    $thirdPart = 'and all the rest not been the real thing.';
    // ~L. Tolstoy, The Death of Ivan Ilych
    $part = StringPart::create($firstPart . $secondPart . $thirdPart, strlen($firstPart), strlen($firstPart) + strlen($secondPart) + strlen($thirdPart) - 1);
    $this->assertFalse($part->equals(str_replace('a', 'b', $secondPart)));
    $this->assertTrue($part->equals($thirdPart));
  }

  /**
   * Tests the equalsStringPart() method.
   *
   * @covers ::equalsStringPart
   */
  public function testEqualsStringPart() : void {
    $firstSentence = 'There are a dozen views about everything until you know the answer.';
    $secondSentence = 'Then there\'s never more than one.';
    $part1 = StringPart::create($firstSentence . $secondSentence, strlen($firstSentence), strlen($firstSentence) + strlen($secondSentence) - 1);
    $part2 = StringPart::create($firstSentence . $secondSentence, strlen($firstSentence) - 1, strlen($firstSentence) + strlen($secondSentence) - 2);
    $part3 = StringPart::create('a' . $secondSentence, 1, strlen($secondSentence));
    $this->assertFalse($part1->equalsStringPart($part2));
    $this->assertFalse($part2->equalsStringPart($part1));
    $this->assertTrue($part1->equalsStringPart($part3));
    $this->assertTrue($part3->equalsStringPart($part1));
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
