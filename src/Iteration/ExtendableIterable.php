<?php

declare(strict_types = 1);

namespace Ranine\Iteration;

use Ranine\Helper\ThrowHelpers;

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
   * Checks to see if $predicate applies to all items in this collection.
   *
   * @param callable $predicate
   *   Predicate, of form ($key, $value) : bool
   *
   * @return bool
   *   Returns 'TRUE' if $predicate evaluates to TRUE for all items; else
   *   returns 'FALSE'.
   */
  public function all(callable $predicate) : bool {
    foreach ($this->source as $key => $value) {
      if (!$predicate($key, $value)) {
        return FALSE;
      }
    }

    return TRUE;
  }

  /**
   * Checks to see if $predicate applies to any item in this collection.
   *
   * @param callable $predicate
   *   Predicate, of form ($key, $value) : bool
   *
   * @return bool
   *   Returns 'TRUE' if $predicate evaluates to TRUE for at least one item;
   *   else returns 'FALSE'.
   */
  public function any(callable $predicate) : bool {
    foreach ($this->source as $key => $value) {
      if ($predicate($key, $value)) {
        return TRUE;
      }
    }

    return FALSE;
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
   *   Filter - of form ($key, $value) : bool - takes each key and value and
   *   returns 'TRUE' (to preserve the value in the output) or 'FALSE' (to not
   *   preserve the value in the output).
   *
   * @return \Ranine\Iteration\ExtendableIterator
   *   Filtered output.
   */
  public function filter(callable $filter) : ExtendableIterator {
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
   * @param callable|null $keyMap
   *   Key map - of form ($key, $value) : mixed - takes each key and value and
   *   returns an output key. If 'NULL' is passed for this parameter, the key
   *   map ($k, $v) => $k is used.
   * @param callable $valueMap
   *   Value map - of form ($key, $value) : mixed - takes each key and value and
   *   returns an output value. If 'NULL' is passed for this parameter, the
   *   value map ($k, $v) => $v is used.
   *
   * @return \Ranine\Iteration\ExtendableIterator
   *   Output generator.
   */
  public function map(?callable $valueMap, ?callable $keyMap = NULL) : ExtendableIterator {
    if ($valueMap === NULL) {
      $valueMap = fn($k, $v) => $v;
    }
    if ($keyMap === NULL) {
      $keyMap = fn($k) => $k;
    }

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
   *   Reduction - of form ($key, $value, $aggregate) : mixed - produces
   *   resulting value of aggregate object (in that step of aggregation) from
   *   current value of aggregate object and key and value. The resulting value
   *   of the aggregate object, which will be passed to the reduction for the
   *   next key and value.
   * @param mixed $initialValue
   *   Initial value of the aggregate object (passed to $reduction on its first
   *   call).
   *
   * @return mixed
   *   Value of the aggregate object returned from the last call to $reduction.
   */
  public function reduce(callable $reduction, $initialValue) {
    $aggregate = $initialValue;
    foreach ($this->source as $key => $value) {
      $aggregate = $reduction($key, $value, $aggregate);
    }
    return $aggregate;
  }

  /**
   * Iterates through $num elements.
   *
   * @param int $num
   *   Number of elements to take.
   *
   * @return \Ranine\Iteration\ExtendableIterator
   *   Items.
   *
   * @throws \InvalidArgumentException
   *   Thrown if $num is less than zero.
   */
  public function take(int $num) : ExtendableIterator {
    ThrowHelpers::throwIfLessThanZero($num, 'num');

    return new static((function () use ($num) {
      $i = 0;
      foreach ($this->source as $key => $value) {
        if ($i === $num) {
          break;
        }
        yield $key => $value;
        $i++;
      }
    })());
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
   * Creates a new extendable iterator from $source.
   *
   * @param iterable $source
   *   Source object over which we are iterating.
   *
   * @return ExtendableIterator
   *   Extendable iterator.
   */
  public static function from(iterable $source) : ExtendableIterator {
    return new static($source);
  }

  /**
   * Generates an extendable iterator from a range of integers.
   *
   * @param int $start
   *   Inclusive start value for range.
   * @param int $end
   *   Inclusive end value for range.
   *
   * @return \Ranine\Iteration\ExtendableIterator
   *   Output range.
   */
  public static function fromRange(int $start, int $end) : ExtendableIterator {
    return new static((function () use ($start, $end) {
      for ($i = $start; $i <= $end; $i++) {
        yield $i;
      }
    })());
  }

}
