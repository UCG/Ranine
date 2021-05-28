<?php

namespace Ranine\Iteration;

/**
 * Iterates through an iterable object while providing useful extension methods.
 */
class ExtendableIterator extends \IteratorAggregate {

  /**
   * The source iterable.
   */
  private iterable $source;

  /**
   * Creates a new extendable iterator.
   *
   * @param iterable $source
   *   Iterator which yields values over which we are iterating.
   */
  protected function __construct(iterable $source) {
    $this->source = $source;
  }

  /**
   * Appends elements from $other on the end of this iterator.
   *
   * @param iterable $other
   *   Iterator to append.
   *
   * @return ExtendableIterator
   *   Appended output.
   */
  public function append(iterable $other) : ExtendableIterator {
    return new static((function () use ($other) {
      yield from $this->source;
      yield from $other;
    })());
  }

  /**
   * Counts the elements in this iterable.
   *
   * @return int
   *   Count.
   */
  public function count() : int {
    if (is_array($this->source) || ($this->source instanceof \Countable)) {
      // Note: Intelephense gives a type error unless we do something like this.
      /** @var array|\Countable */
      $countableSource = $this->source;
      return count($countableSource);
    }
    else {
      return iterator_count($this->source);
    }
  }

  /**
   * Gets an iterator for looping through values associated with this object.
   *
   * @return \Traversable
   *   Iterator.
   */
  public function getIterator() : \Traversable {
    yield from $this->source;
  }

  /**
   * Filters on the collection's values.
   *
   * @param callable $filter
   *   Filter - takes each key (first argument) and value from and returns
   *   'TRUE' (to preserve the value in the output) or 'FALSE' (to not preserve
   *   the value in the output).
   *
   * @return \Ranine\Iteration\ExtendableIterator
   *   Filtered output.
   */
  public function filter(callable $filter = fn($k, $v) => TRUE) : ExtendableIterator {
    return new static((function () use ($filter) {
      foreach ($this->source as $key => $value) {
        if ($filter($key, $value)) {
          yield $key => $value;
        }
      }
    })());
  }

  /**
   * Lazily maps the given iterable collection to a generator.
   *
   * @param iterable $input
   *   Input collection.
   * @param callable $keyMap
   *   Key map - takes each key (first argument) and value and returns an output
   *   key.
   * @param callable $valueMap
   *   Value map - takes each key (first argument) and value form and returns an
   *   output value.
   *
   * @return iterable
   *   Output generator.
   */
  public function map(callable $valueMap = fn($k, $v) => $v, callable $keyMap = fn($k, $v) => $k) : ExtendableIterator {
    return new static((function () use ($keyMap, $valueMap) {
      foreach ($this->source as $key => $value) {
        yield $keyMap($key, $value) => $valueMap($key, $value);
      }
    })());
  }

  /**
   * Aggregates the iterable into a single object (the "aggregate").
   *
   * @param callable $reduction
   *   Reduction - takes each key (first argument), value (second argument), and
   *   the current value of the "aggregate" object, and produces the resulting
   *   value of the aggregate object, which will be passed to the reduction for
   *   the next key and value.
   * @param mixed $initialValue
   *   Initial value of the aggregate object (passed to $reduction on its first
   *   call).
   *
   * @return mixed
   *   Value of the aggregate object returned from the last call to $reduction.
   */
  public function reduce(callable $reduction = fn($k, $v, $a) => $a, $initialValue) {
    $aggregate = $initialValue;
    foreach ($this->source as $key => $value) {
      $aggregate = $reduction($key, $value, $aggregate);
    }
    return $aggregate;
  }

  /**
   * Converts the underlying iterable to an array.
   *
   * @param bool $preserveKeys
   *   Whether the iterable keys should be used for the new array.
   *
   * @return array
   *   Resulting array.
   */
  public function toArray(bool $preserveKeys = TRUE) : array {
    if (is_array($this->source)) {
      // This is necessary to make Intelephense work.
      /** @var array */
      $sourceArray = $this->source;
      return $sourceArray;
    }
    elseif (($this->source instanceof \ArrayObject)) {
      // Again, this is necessary to make Intelephense work.
      /** @var \ArrayObject */
      $sourceArrayObject = $this->source;
      return $sourceArrayObject->getArrayCopy();
    }
    else {
      return iterator_to_array($this->source, $preserveKeys);
    }
  }

  /**
   * Creates a new extendable iterator.
   *
   * @param iterable $source
   *   Source object over which we are iterating.
   *
   * @return ExtendableIterator
   *   Extendable iterator.
   */
  public static function create(iterable $source) : ExtendableIterator {
    return new static($source);
  }

}
