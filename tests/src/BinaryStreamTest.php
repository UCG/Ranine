<?php

declare(strict_types = 1);

namespace Ranine\Tests;

use PHPUnit\Framework\TestCase;
use Ranine\BinaryStream;
use Ranine\StringPart;

/**
 * Tests the BinaryStream class.
 *
 * @coversDefaultClass \Ranine\BinaryStream
 * @group ranine
 */
class BinaryStreamTest extends TestCase {

  /**
   * Tests the readBytes() method.
   *
   * @covers ::readBytes
   */
  public function testReadBytes() : void {
    $firstLine = 'Oh, Sir, the good die first,';
    $secondLine = 'And they whose hearts are dry as summer dust';
    $thirdLine = 'Burn to the socket.';
    $streamSource = (function () use ($firstLine, $secondLine, $thirdLine) {
      yield $firstLine;
      yield "\n";
      yield ($secondLine . "\n");
      yield $thirdLine;
    })();
    $stream = new BinaryStream($streamSource);
    $firstTwoLines = $stream->readBytes(strlen($firstLine) + strlen($secondLine) + 1);
    $this->assertTrue($firstTwoLines === ($firstLine. "\n" . $secondLine));
  }

  /**
   * Tests the readUntil() method.
   *
   * @covers ::readUntil
   */
  public function testReadUntil() : void {
    $firstPart = 'It\'s all God\'s will';
    $secondPart = 'you can die in your sleep,';
    $thirdPart = 'and God can spare you in battle.';
    $streamSource = (function () use ($firstPart, $secondPart, $thirdPart) {
      yield $firstPart;
      yield ': ';
      yield $secondPart;
      yield ' ' . $thirdPart;
    })();
    $stream = new BinaryStream($streamSource);
    // Cut it off before the first colon.
    $result = $stream->readUntil(fn(StringPart $part, int $currentStartPosition) : int =>
      strpos($part->getBackingString(), ':', $currentStartPosition + $part->getStartPosition()) - $part->getStartPosition());
    $this->assertTrue($result === ($firstPart . ':'));
  }

}
