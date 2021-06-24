<?php

declare(strict_types = 1);

namespace Ranine;

/**
 * Represents a binary stream.
 */
class BinaryStream {

  /**
   * The part of the stream that has been loaded but not yet read.
   */
  private StringPart $buffer;

  /**
   * Iterator of strings that yields data from the stream.
   *
   * Cannot yield an empty string.
   *
   * @var \Iterator
   */
  private $input;

  /**
   * Creates a new binary stream.
   *
   * @param iterable<string> $input
   *   Iterable that yields data from the stream. If there is no data left to
   *   yield, the iterable should terminate. All strings returned should be
   *   non-empty.
   */
  public function __construct(iterable $input) {
    $this->buffer = StringPart::create();
    $this->input = (function () use ($input) { yield from $input; })();
    $this->input->rewind();
  }

  /**
   * Attempts to read and return the given number of bytes from the stream.
   *
   * @param int $numBytes
   *   Number of bytes to read from the string.
   *
   * @throws \InvalidArgumentException
   *   Thrown if $numBytes is less than one.
   *
   * @return \Ranine\StringPart
   *   The given number of bytes (or the remainder of the stream if the end of
   *   the stream was encountered before reading the requested number of bytes).
   */
  public function readBytes(int $numBytes) : StringPart {
    if ($numBytes < 1) {
      throw new \InvalidArgumentException('$numBytes is less than one.');
    }

    $chunk = NULL;

    while (($bufferLength = $this->buffer->getLength()) < $numBytes && $chunk !== '') {
      $chunk = $this->readChunk();
    }

    $stopPosition = ($bufferLength < $numBytes ? $bufferLength : $numBytes) - 1 + $this->buffer->getStartPosition();
    if ($stopPosition === -1) {
      // An empty string.
      return StringPart::create();
    }
    else {
      return $this->cutOffBuffer($stopPosition, $chunk ?? $this->buffer->getBackingString());
    }
  }

  /**
   * Reads a unsigned 8-bit integer from the stream, if possible.
   *
   * @return int|null
   *   Result, or NULL if the stream did not contain such an integer.
   */
  public function readUInt8() : ?int {
    $byte = $this->readBytes(1);
    if ($byte->isEmpty()) {
      return NULL;
    }
    $result = unpack('C', $byte->getBackingString(), $byte->getStartPosition());
    if (!is_array($result) || !array_key_exists(1, $result)) {
      return NULL;
    }
    assert(is_int($result[1]));
    return $result[1];
  }

  /**
   * Reads a unsigned 16-bit big-endian integer from the stream, if possible.
   *
   * @return int|null
   *   Result, or NULL if the stream did not contain such an integer.
   */
  public function readUInt16BE() : ?int {
    $bytes = $this->readBytes(2);
    if ($bytes->getLength() !== 2) {
      return NULL;
    }
    $result = unpack('n', $bytes->getBackingString(), $bytes->getStartPosition());
    if (!is_array($result) || !array_key_exists(1, $result)) {
      return NULL;
    }
    assert(is_int($result[1]));
    return $result[1];
  }

  /**
   * Reads a unsigned 16-bit little-endian integer from the stream, if possible.
   *
   * @return int|null
   *   Result, or NULL if the stream did not contain such an integer.
   */
  public function readUInt16LE() : ?int {
    $bytes = $this->readBytes(2);
    if ($bytes->getLength() !== 2) {
      return NULL;
    }
    $result = unpack('v', $bytes->getBackingString(), $bytes->getStartPosition());
    if (!is_array($result) || !array_key_exists(1, $result)) {
      return NULL;
    }
    assert(is_int($result[1]));
    return $result[1];
  }

  /**
   * Reads a unsigned 32-bit big-endian integer from the stream, if possible.
   *
   * @return int|null
   *   Result, or NULL if the stream did not contain such an integer.
   */
  public function readUInt32BE() : ?int {
    $bytes = $this->readBytes(4);
    if ($bytes->getLength() !== 4) {
      return NULL;
    }
    $result = unpack('N', $bytes->getBackingString(), $bytes->getStartPosition());
    if (!is_array($result) || !array_key_exists(1, $result)) {
      return NULL;
    }
    assert(is_int($result[1]));
    return $result[1];
  }

  /**
   * Reads a unsigned 32-bit little-endian integer from the stream, if possible.
   *
   * @return int|null
   *   Result, or NULL if the stream did not contain such an integer.
   */
  public function readUInt32LE() : ?int {
    $bytes = $this->readBytes(4);
    if ($bytes->getLength() !== 4) {
      return NULL;
    }
    $result = unpack('V', $bytes->getBackingString(), $bytes->getStartPosition());
    if (!is_array($result) || !array_key_exists(1, $result)) {
      return NULL;
    }
    assert(is_int($result[1]));
    return $result[1];
  }

  /**
   * Reads a unsigned 64-bit big-endian integer from the stream, if possible.
   *
   * @return int|null
   *   Result, or NULL if the stream did not contain such an integer.
   */
  public function readUInt64BE() : ?int {
    $bytes = $this->readBytes(8);
    if ($bytes->getLength() !== 8) {
      return NULL;
    }
    $result = unpack('J', $bytes->getBackingString(), $bytes->getStartPosition());
    if (!is_array($result) || !array_key_exists(1, $result)) {
      return NULL;
    }
    assert(is_int($result[1]));
    return $result[1];
  }

  /**
   * Reads a unsigned 64-bit little-endian integer from the stream, if possible.
   *
   * @return int|null
   *   Result, or NULL if the stream did not contain such an integer.
   */
  public function readUInt64LE() : ?int {
    $bytes = $this->readBytes(8);
    if ($bytes->getLength() !== 8) {
      return NULL;
    }
    $result = unpack('P', $bytes->getBackingString(), $bytes->getStartPosition());
    if (!is_array($result) || !array_key_exists(1, $result)) {
      return NULL;
    }
    assert(is_int($result[1]));
    return $result[1];
  }

  /**
   * Reads until a position is identified at which to stop.
   *
   * Sequentially reads chunks from the stream, passing the current aggregation
   * of these chunks to $positionIdentification. If $positionIdentification
   * returns a valid index at which to stop, reading is terminated and the
   * entire string part up to that index is returned. If $positionIdentification
   * always returns NULL until the end of the stream is reached, NULL is
   * returned from thsi function.
   *
   * @param callable $positionIdentification
   *   Of the form
   *   (\Ranine\StringPart $current, int $newPartStartPosition) : ?int, this
   *   function is passed the current concatenation ($current) of all the chunks
   *   produced since the readUntil() method was called, and the start index of
   *   the part of $current that was appended since the last call to
   *   $positionIdentification(). If that function returns NULL, chunks continue
   *   to be read from the stream and passed to $positionIdentification().
   *   Otherwise, the part of $current from $current->getStartPosition() to the
   *   index returned by $positionIdentification() (inclusive) is returned.
   *
   * @return \Ranine\StringPart|null
   *   The part of the stream up to and including the position identified by
   *   $positionIdentification, or NULL if $positionIdentification always
   *   returned NULL.
   *
   * @throws \LogicException
   *   Thrown if $positionIdentifier returns an index that is not within the
   *   string portion passed to it.
   */
  public function readUntil(callable $positionIdentification) : ?StringPart {
    if (!$this->buffer->isEmpty()) {
      $stopPosition = $positionIdentification($this->buffer);
    }
    else {
      $stopPosition = NULL;
    }

    $chunk = NULL;
    while ($stopPosition === NULL) {
      $newStartPosition = $this->buffer->getEndPosition() + 1;
      $chunk = $this->readChunk();
      if ($chunk === '') {
        return NULL;
      }

      $stopPosition = $positionIdentification($this->buffer, $newStartPosition);
    }

    if ($stopPosition > $this->buffer->getEndPosition() || $stopPosition < $this->buffer->getStartPosition()) {
      throw new \LogicException('Invalid index returned from $positionIdentification() was invalid.');
    }

    return $this->cutOffBuffer($stopPosition, $chunk ?? $this->buffer->getBackingString());
  }

  /**
   * Cuts up the buffer into two parts, returning the first.
   *
   * Cuts off the [(buffer start position), $stopPosition] part of the buffer
   * and returns it, and sets the buffer equal to a new buffer containing the
   * rest of the previous
   * buffer.
   *
   * @param int $stopPosition
   *   Last index of first part of buffer (nonnegative).
   * @param ?string $lastChunk
   *   If not NULL, a string meeting the following conditions:
   *   1) If the length of $lastChunk is less than or equal to that of buffer,
   *   $lastChunk should be the result of taking a substring of buffer from some
   *   index to the end of the buffer, or,
   *   2) If the length of $lastChunk is greater than that of buffer, the buffer
   *   should be the result of taking a substring of $lastChunk from some index
   *   to the end of $lastChunk.
   *   If not NULL, $lastChunk which will be used (if possible) to construct the
   *   new buffer (provided the new buffer isn't empty). If $lastChunk is NULL,
   *   the new buffer's backing string will be generated by taking a substring
   *   of the old buffer's string.
   *
   * @return \Ranine\StringPart
   *   First cut of buffer.
   */
  protected function cutOffBuffer(int $stopPosition, ?string $lastChunk) : StringPart {
    $firstCut = $this->buffer->withNewEndpoints($this->buffer->getStartPosition(), $stopPosition);

    if ($stopPosition === $this->buffer->getEndPosition()) {
      $this->buffer->clear();
    }
    elseif ($lastChunk === NULL) {
      $this->buffer->recut($stopPosition + 1, $this->buffer->getEndPosition())->clean();
    }
    else {
      /** @var string $lastChunk */
      $startPosition = $stopPosition + 1;
      $lastChunkLength = strlen($lastChunk);
      $newStartPosition = $startPosition - ($this->buffer->getEndPosition() + 1 - $lastChunkLength);
      if ($newStartPosition < 0) {
        // A valid start position was not found in $lastChunk.
        $this->buffer->recut($startPosition, $this->buffer->getEndPosition())->clean();
      }
      else {
        $this->buffer = StringPart::create($lastChunk, $newStartPosition, $lastChunkLength - 1);
      }
    }

    return $firstCut;
  }

  /**
   * Reads a chunk from the stream and appends it to the buffer.
   *
   * @return string
   *   Returns the chunk read if not at the end of stream; else returns an empty
   *   string.
   */
  protected function readChunk() : string {
    if (!$this->input->valid()) {
      return '';
    }

    /** @var string */
    $chunk = $this->input->current();
    $this->buffer->append($chunk);
    $this->input->next();
    return $chunk;
  }

}
