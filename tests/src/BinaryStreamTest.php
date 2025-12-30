<?php

declare(strict_types = 1);

namespace Ranine\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;
use Ranine\BinaryStream;
use Ranine\StringPart;

#[TestDox('Tests the BinaryStream class.')]
#[CoversClass(BinaryStream::class)]
#[Group('ranine')]
class BinaryStreamTest extends TestCase {

  #[TestDox('Tests various integer reading methods.')]
  #[CoversFunction('readUInt8')]
  #[CoversFunction('readUInt16BE')]
  #[CoversFunction('readUInt16LE')]
  #[CoversFunction('readUInt32BE')]
  #[CoversFunction('readUInt32LE')]
  #[CoversFunction('readUInt64BE')]
  #[CoversFunction('readUInt64LE')]
  public function testIntegerReading() : void {
    $source = (function () {
      $str = pack('CVNnvPJ', 1, 1, 2, 3, 5, 8, 13);
      yield substr($str, 0, 9);
      yield substr($str, 9, 3);
      yield substr($str, 12);
    })();
    $stream = new BinaryStream($source);
    
    $this->assertTrue($stream->readUint8() === 1);
    $this->assertTrue($stream->readUint32LE() === 1);
    $this->assertTrue($stream->readUint32BE() === 2);
    $this->assertTrue($stream->readUint16BE() === 3);
    $this->assertTrue($stream->readUint16LE() === 5);
    $this->assertTrue($stream->readUint64LE() === 8);
    $this->assertTrue($stream->readUint64BE() === 13);
  }

  #[TestDox('Tests the readBytes() method.')]
  #[CoversFunction('readBytes')]
  public function testReadBytes() : void {
    $firstLine = 'O Sir! the good die first,';
    $secondLine = 'And they whose hearts are dry as summer dust';
    $thirdLine = 'Burn to the socket.';
    // ~ W. Wordsworth, The Ruined Cottage

    $streamSource = (function () use ($firstLine, $secondLine, $thirdLine) {
      yield $firstLine;
      yield "\n";
      yield ($secondLine . "\n");
      yield $thirdLine;
    })();
    $stream = new BinaryStream($streamSource);
    $firstTwoLines = $stream->readBytes(strlen($firstLine) + strlen($secondLine) + 1);
    $this->assertTrue(((string) $firstTwoLines) === ($firstLine. "\n" . $secondLine));
  }

  #[TestDox('Tests the readUntil() method.')]
  #[CoversFunction('readUntil')]
  public function testReadUntil() : void {
    $firstPart = 'It\'s all God\'s will';
    $secondPart = 'you can die in your sleep,';
    $thirdPart = 'and God can spare you in battle.';
    // ~ L. Tolstoy, War and Peace

    $streamSource = (function () use ($firstPart, $secondPart, $thirdPart) {
      yield $firstPart;
      yield ': ';
      yield $secondPart;
      yield ' ' . $thirdPart;
    })();
    $stream = new BinaryStream($streamSource);
    // Cut it off at the first colon.
    $result = $stream->readUntil(function(StringPart $part, int $currentStartPosition) : ?int {
      $colonLocation = strpos($part->getBackingString(), ':', $currentStartPosition);
      return ($colonLocation === FALSE) ? NULL : $colonLocation;
    });
    $this->assertTrue(((string) $result) === ($firstPart . ':'));
  }

}
