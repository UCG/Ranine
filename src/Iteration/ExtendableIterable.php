<?php

namespace Ranine\Iteration;

use Generator;
use Traversable;

/**
 * Iterates through an iterable object while providing useful extension methods.
 */
class ExtendableIterator extends \IteratorAggregate {

  /**
   * The generator which produces elements from the iterable source.
   */
  private Generator $generator;

  /**
   * Creates a new extendable iterator.
   *
   * @param iterable $generator
   *   Generator which yields values over which we are iterating.
   */
  protected function __construct(Generator $generator) {
    $this->generator = $generator;
  }

  /**
   * Counts the elements in this iterable.
   *
   * If the iterable represents an array or implements \Countable, it is better
   * to use the native count() function.
   *
   * @return int
   *   Count.
   */
  public function count() : int {
    return iterator_count($this->generator);
  }

  /**
   * Gets the iterator underlying this object.
   *
   * @return \Traversable
   *   Iterator.
   */
  public function getIterator() : Traversable {
    return $this->generator;
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
      foreach ($this->generator as $key => $value) {
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
      foreach ($this->generator as $key => $value) {
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
  public function reduce(callable $reduction = fn($k, $v, $c) => $c, $initialValue) {
    $aggregate = $initialValue;
    foreach ($this->generator as $key => $value) {
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
    return iterator_to_array($this->generator, $preserveKeys);
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
    return new static((function () use ($source) { yield from $source; })());
  }

}