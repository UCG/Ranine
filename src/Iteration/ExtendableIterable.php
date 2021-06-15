<?php

declare(strict_types = 1);

namespace Ranine\Iteration;

use Ranine\Exception\InvalidOperationException;
use Ranine\Helper\ThrowHelpers;

/**
 * Iterates through an iterable object while providing useful extension methods.
 *
 * Sample use -- calculate sum of squares of first five items from array input
 * and where x^2 < 100:
 * <code>
 * <?php
 * $input = [2, 3, 7, 77, 99, 110];
 * $sum = ExtendableIterable::from($input)
 *  ->take(5)
 *  ->map(fn($k, $v) => $v * $v)
 *  ->filter(fn($k, $v) => $v < 100)
 *  ->reduce(fn($k, $v, $sum) => $sum + $v, 0);
 * // $sum = 2*2 + 3*3 + 7*7 = 62
 * </code>
 */
class ExtendableIterable implements \IteratorAggregate {

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
   *   Returns 'TRUE' if $predicate evaluates to 'TRUE' for all items; else
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
   *   Returns 'TRUE' if $predicate evaluates to 'TRUE' for at least one item;
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
   * @return \Ranine\Iteration\ExtendableIterable
   *   Appended output -- the order of elements in this iterator and $source is
   *   preserved.
   */
  public function append(iterable $other) : ExtendableIterable {
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
   * Expands sub-iterables of this collection.
   *
   * @param callable $expansion
   *   Of the form ($key, $value) => ?iterable, this function returns either
   *   NULL (if the element is not to be expanded) or an iterable (if the
   *   element is to be expanded into that iterable).
   *
   * @return ExtendableIterable
   *   Resulting iterator, which will iterate through items in this iterable
   *   that were not expanded, and through the expansion of any sub-iterables,
   *   as they are encountered.
   */
  public function expand(callable $expansion) : ExtendableIterable {
    return new static((function () use ($expansion) {
      foreach ($this->source as $key => $value) {
        $subElements = $expansion($key, $value);
        if ($subElements === NULL) {
          yield $key => $value;
        }
        else {
          yield from $subElements;
        }
      }
    })());
  }

  /**
   * Filters on the collection's values.
   *
   * @param callable $filter
   *   Filter - of form ($key, $value) : bool - takes each key and value and
   *   returns 'TRUE' (to preserve the value in the output) or 'FALSE' (to not
   *   preserve the value in the output).
   *
   * @return \Ranine\Iteration\ExtendableIterable
   *   Filtered output -- the order of elements in this iterator is preserved.
   */
  public function filter(callable $filter) : ExtendableIterable {
    return new static((function () use ($filter) {
      foreach ($this->source as $key => $value) {
        if ($filter($key, $value)) {
          yield $key => $value;
        }
      }
    })());
  }

  /**
   * Grabs the first value of this collection, if possible.
   *
   * @return mixed
   *   First value.
   *
   * @throws \Ranine\Exception\InvalidOperationException
   *   Thrown if the collection if empty.
   */
  public function first() {
    foreach ($this->source as $value) {
      return $value;
    }

    throw new InvalidOperationException('The collection is empty.');
  }

  /**
   * Grabs the first key of this collection, if possible.
   *
   * @return string|int
   *   First key.
   *
   * @throws \Ranine\Exception\InvalidOperationException
   *   Thrown if the collection if empty.
   */
  public function firstKey() {
    foreach ($this->source as $key => $value) {
      return $key;
    }

    throw new InvalidOperationException('The collection is empty.');
  }

  /**
   * Gets an iterator for looping through values associated with this object.
   *
   * @return \Iterator
   *   Iterator.
   */
  public function getIterator() : \Iterator {
    yield from $this->source;
  }

  /**
   * Yields all the keys from this collection.
   */
  public function getKeys() : ExtendableIterable {
    return new static((function () {
      foreach ($this->source as $key => $value) {
        yield $key;
      }
    })());
  }

  /**
   * Checks whether the collection is empty.
   */
  public function isEmpty() : bool {
    foreach ($this->source as $value) {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * Maps each key/value pair in the collection to another key/value pair.
   *
   * @param iterable $input
   *   Input collection.
   * @param callable|null $valueMap
   *   Value map - of form ($key, $value) : mixed - takes each key and value and
   *   returns an output value. If 'NULL' is passed for this parameter, the
   *   value map ($k, $v) => $v is used.
   * @param callable|null $keyMap
   *   Key map - of form ($key, $value) : string|int - takes each key and value
   *   and returns an output key. If 'NULL' is passed for this parameter, the
   *   key map ($k, $v) => $k is used.
   *
   * @return \Ranine\Iteration\ExtendableIterable
   *   Output generator. The order of elements is preserved.
   */
  public function map(?callable $valueMap, ?callable $keyMap = NULL) : ExtendableIterable {
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
   * Maps each key/value pair to a value, generating sequential keys.
   *
   * The keys are integers that start at zero, and increase by one as one moves
   * through the output iterator.
   *
   * @param callable|null $valueMap
   *   Value map - of form ($key, $value) : mixed - takes each key and value and
   *   returns an output value. If 'NULL' is passed for this parameter, the
   *   value map ($k, $v) => $v is used.
   *
   * @return \Ranine\Iteration\ExtendableIterable
   *   Output generator. The order of elements is preserved.
   */
  public function mapSequentialKeys(?callable $valueMap) : ExtendableIterable {
    if ($valueMap === NULL) {
      $valueMap = fn($k, $v) => $v;
    }

    return new static((function () use ($valueMap) {
      $i = 0;
      foreach ($this->source as $key => $value) {
        yield $i => $valueMap($key, $value);
        $i++;
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
   * @return \Ranine\Iteration\ExtendableIterable
   *   Items. The order of elements is preserved.
   *
   * @throws \InvalidArgumentException
   *   Thrown if $num is less than zero.
   */
  public function take(int $num) : ExtendableIterable {
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
   * Iterates through at most $max elements while $predicate is 'TRUE'.
   *
   * @param callable $predicate
   *   Predicate, of form ($key, $value) : bool.
   * @param int|null $max
   *   Max number of elements to take. Pass 'NULL' for "unlimited."
   *
   * @return ExtendableIterable
   *   Items. The order of elements is preserved.
   *
   * @throws \InvalidArgumentException
   *   Thrown if $num is less than zero.
   */
  public function takeWhile(callable $predicate, ?int $max = NULL) : ExtendableIterable {
    if ($max === NULL) {
      return new static((function () use ($predicate) {
        foreach ($this->source as $key => $value) {
          if (!$predicate($key, $value)) {
            break;
          }
          yield $key => $value;
        }
      })());
    }
    else {
      ThrowHelpers::throwIfLessThanZero((int) $max, 'max');
      return new static((function () use ($predicate, $max) {
        $i = 0;
        foreach ($this->source as $key => $value) {
          if (!$predicate($key, $value) || $i === $max) {
            break;
          }
          yield $key => $value;
          $i++;
        }
      })());
    }
  }

  /**
   * Converts the underlying iterable to an array.
   *
   * @param bool $preserveKeys
   *   Whether the iterable keys should be used for the new array.
   *
   * @return array
   *   Resulting array. The order of elements is preserved.
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
   * Creates a collection by producing output values from two iterables.
   *
   * The two iterables are iterated through simultaneously, and output keys
   * and values are given by $keyMap and $valueMap (respectively) while both
   * iterables are valid. If one iterable terminates before the other, the
   * remaining output keys and values are taken from the remaining keys/values
   * of the longer iterable.
   *
   * @param iterable $other
   *   Other iterable.
   * @param callable $keyMap
   *   Map to produce output keys, of form
   *   ($keyFromThisObject, $valueFromThisObject, $keyFromOtherIterable,
   *   $valueFromOtherIterable) : string|int
   * @param callable $valueMap
   *   Map to produce output values, of form
   *   ($keyFromThisObject, $valueFromThisObject, $keyFromOtherIterable,
   *   $valueFromOtherIterable) : mixed
   *
   * @return ExtendableIterable
   *   Resulting collection. The order of elements in this iterator and $other
   *   is preserved.
   */
  public function zip(iterable $other, callable $keyMap, callable $valueMap) : ExtendableIterable {
    // Wrap $other in a generator in order to ensure we can iterate through it
    // manually.
    $otherGenerator = (function () use ($other) { yield from $other; })();
    return new static((function () use ($otherGenerator, $keyMap, $valueMap) {
      $otherGenerator->rewind();
      foreach ($this->source as $key1 => $value1) {
        if ($otherGenerator->valid()) {
          $key2 = $otherGenerator->key();
          $value2 = $otherGenerator->current();
          yield $keyMap($key1, $value1, $key2, $value2) => $valueMap($key1, $value1, $key2, $value2);
          $otherGenerator->next();
        }
        else {
          yield $key1 => $value1;
        }
      }
      while ($otherGenerator->valid()) {
        yield $otherGenerator->key() => $otherGenerator->current();
        $otherGenerator->next();
      }
    })());
  }

  public static function empty() : ExtendableIterable {
    return new static(new \EmptyIterator());
  }

  /**
   * Creates a new extendable iterator from $source.
   *
   * @param iterable $source
   *   Source object over which we are iterating.
   *
   * @return \Ranine\Iteration\ExtendableIterable
   *   Extendable iterator.
   */
  public static function from(iterable $source) : ExtendableIterable {
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
   * @return \Ranine\Iteration\ExtendableIterable
   *   Output range.
   */
  public static function fromRange(int $start, int $end) : ExtendableIterable {
    return new static((function () use ($start, $end) {
      for ($i = $start; $i <= $end; $i++) {
        yield $i;
      }
    })());
  }

}
