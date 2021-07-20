<?php

declare(strict_types = 1);

namespace Ranine\Iteration;

use Ranine\Exception\InvalidOperationException;
use Ranine\Helper\ThrowHelpers;

/**
 * Iterates through an iterable object while providing useful extension methods.
 *
 * Sample use -- calculate sum of squares of first five items from array input
 * and where x < 10
 * <code>
 * <?php
 * $input = [2, 3, 7, 77, 99, 110];
 * $sum = ExtendableIterable::from($input)
 *  ->take(5)
 *  ->filter(fn($k, $v) => $v < 10)
 *  ->map(fn($k, $v) => $v * $v)
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
   * Tells whether $predicate applies to all items in this collection.
   *
   * @param callable $predicate
   *   Predicate, of form ($key, $value) : bool.
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
   * Tells whether $predicate applies to any item in this collection.
   *
   * @param callable $predicate
   *   Predicate, of form ($key, $value) : bool
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
   * @return static
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
   * Appends a single key/value pair to the end of this collection.
   *
   * @param mixed $key
   *   Key.
   * @param mixed $value
   *   Value.
   *
   * @return static
   *   Resulting iterable.
   */
  public function appendKeyAndValue($key, $value) : ExtendableIterable {
    return new static((function () use ($key, $value) {
      yield from $this->source;
      yield $key => $value;
    })());
  }

  /**
   * Appends a single value to the end of this collection.
   *
   * @param mixed $value
   *   Value to append.
   *
   * @return static
   *   Resulting iterable.
   */
  public function appendValue($value) : ExtendableIterable {
    return new static((function () use ($value) {
      yield from $this->source;
      yield $value;
    })());
  }

  /**
   * Calls a function for each key and value in the iterable.
   *
   * The processing function is called once for each element of the iterable,
   * just before the corresponding key and value are yielded.
   *
   * @param callable $processing
   *   Of the form ($key, $value) : void.
   *
   * @return static
   *   Resulting iterable.
   */
  public function apply(callable $processing) : ExtendableIterable {
    return new static((function () use ($processing) {
      foreach ($this->source as $key => $value) {
        $processing($key, $value);
        yield $key => $value;
      }
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
      return count($this->source);
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
   * @return static
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
   * Filters out elements from this collection.
   *
   * @param callable $filter
   *   Filter - of form ($key, $value) : bool - takes each key and value and
   *   returns TRUE (to preserve the value in the output) or FALSE (to not
   *   preserve the value in the output).
   *
   * @return static
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
    static::throwEmptyCollectionException();
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
    static::throwEmptyCollectionException();
  }

  /**
   * Grabs the first key and value of this collection, if possible.
   *
   * @param mixed $key
   *   First key.
   * @param mixed $value
   *   First value.
   *
   * @throws \Ranine\Exception\InvalidOperationException
   *   Thrown if the collection if empty.
   */
  public function firstKeyAndValue(&$key, &$value) : void {
    foreach ($this->source as $key => $value) {
      return;
    }
    static::throwEmptyCollectionException();
  }

  /**
   * Gets an iterator for looping through values associated with this object.
   */
  public function getIterator() : \Iterator {
    yield from $this->source;
  }

  /**
   * Gets a collection consisting that can iterate the keys from this iterable.
   */
  public function getKeys() : ExtendableIterable {
    return new static((function () {
      foreach ($this->source as $key => $value) {
        yield $key;
      }
    })());
  }

  /**
   * Tells whether the collection is empty.
   *
   * NOTE: This function will begin advancing through the collection if it isn't
   * empty, which could prevent foreach() from being used on the collection in
   * the future.
   */
  public function isEmpty() : bool {
    foreach ($this->source as $v) {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * Maps each key/value pair in the collection to another key/value pair.
   *
   * @param callable|null $valueMap
   *   Value map - of form ($key, $value) : mixed - takes each key and value and
   *   returns an output value. If NULL is passed for this parameter, the
   *   value map ($k, $v) => $v is used.
   * @param callable|null $keyMap
   *   Key map - of form ($key, $value) : mixed - takes each key and value and
   *   returns an output key. If NULL is passed for this parameter, the key map
   *   ($k, $v) => $k is used.
   *
   * @return static
   *   Output iterable. The order of elements is preserved.
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
   *   returns an output value. If NULL is passed for this parameter, the
   *   value map ($k, $v) => $v is used.
   *
   * @return static
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
   *   of the aggregate object will be passed to the reduction for the next key
   *   and value.
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
   * @return static
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
   * Iterates through at most $max elements while $predicate is TRUE.
   *
   * @param callable $predicate
   *   Predicate, of form ($key, $value) : bool.
   * @param int|null $max
   *   Max number of elements to take. Pass NULL for "unlimited."
   *
   * @return static
   *   Items. The order of elements is preserved.
   *
   * @throws \InvalidArgumentException
   *   Thrown if $max is an integer less than zero.
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
      /** int $max */
      ThrowHelpers::throwIfLessThanZero($max, 'max');
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
      /** @var array */
      $source = $this->source;
      return $source;
    }
    elseif (($this->source instanceof \ArrayObject)) {
      /** @var \ArrayObject */
      $source = $this->source;
      return $source->getArrayCopy();
    }
    else {
      return iterator_to_array($this->source, $preserveKeys);
    }
  }

  /**
   * Creates a collection by combining this iterable with another.
   *
   * The two iterables are iterated through simultaneously, and output keys
   * and values are given by:
   * - $keyMapBoth and $valueMapBoth (respectively), if both iterables are
   *   valid.
   * - $keyMapFirst and $valueMapFirst (respectively), if $other has terminated,
   *   but this iterable still has remaining items.
   * - $keyMapFirst and $valueMapFirst (respectively), if this iterable has
   *   terminated, but $other is still valid.
   *
   * When one iterable terminates, iteration continues through the other
   * iterable if it is still valid.
   *
   * @param iterable $other
   *   Other iterable.
   * @param callable $keyMapBoth
   *   Map to produce output keys when both iterators are valid, of form
   *   ($keyFromThisObject, $valueFromThisObject, $keyFromOtherIterable,
   *   $valueFromOtherIterable) : mixed.
   * @param callable $valueMapBoth
   *   Map to produce output values when both iterators are valid, of form
   *   ($keyFromThisObject, $valueFromThisObject, $keyFromOtherIterable,
   *   $valueFromOtherIterable) : mixed.
   * @param callable|null $keyMapCurrent
   *   Map to produce output keys when only this iterable (this instance)
   *   remains valid, of form
   *   ($keyFromThisObject, $valueFromThisObject) : mixed. If NULL is passed,
   *   the map ($k, $v) => $k is used.
   * @param callable|null $valueMapCurrent
   *   Map to produce output values when only this iterable (this instance)
   *   remains valid, of form
   *   ($keyFromThisObject, $valueFromThisObject) : mixed. If NULL is passed,
   *   the map ($k, $v) => $v is used.
   * @param callable|null $keyMapOther
   *   Map to produce output keys when only $other is valid, of form
   *   ($keyFromOtherIterable, $valueFromOtherIterable) : mixed. If NULL is
   *   passed, the map ($k, $v) => $k is used.
   * @param callable|null $valueMapOther
   *   Map to produce output values when only $other is valid, of form
   *   ($keyFromOtherIterable, $valueFromOtherIterable) : mixed. If NULL is
   *   passed, the map ($k, $v) => $v is used.
   *
   * @return static
   *   Resulting collection. The order of elements in this iterator and $other
   *   is preserved.
   */
  public function zip(iterable $other,
    callable $keyMapBoth,
    callable $valueMapBoth,
    ?callable $keyMapCurrent = NULL,
    ?callable $valueMapCurrent = NULL,
    ?callable $keyMapOther = NULL,
    ?callable $valueMapOther = NULL) : ExtendableIterable {
    if ($keyMapCurrent === NULL) {
      $keyMapCurrent = fn($k) => $k;
    }
    if ($keyMapOther === NULL) {
      $keyMapOther = fn($k) => $k;
    }
    if ($valueMapCurrent === NULL) {
      $valueMapCurrent = fn($k, $v) => $v;
    }
    if ($valueMapOther === NULL) {
      $valueMapOther = fn($k, $v) => $v;
    }

    // Wrap $other in a generator in order to ensure we can iterate through it
    // manually.
    $otherGenerator = (function () use ($other) { yield from $other; })();
    return new static((function () use ($otherGenerator, $keyMapBoth,
      $valueMapBoth, $keyMapCurrent, $valueMapCurrent, $keyMapOther,
      $valueMapOther) {
      $otherGenerator->rewind();
      foreach ($this->source as $key1 => $value1) {
        if ($otherGenerator->valid()) {
          $key2 = $otherGenerator->key();
          $value2 = $otherGenerator->current();
          yield $keyMapBoth($key1, $value1, $key2, $value2) => $valueMapBoth($key1, $value1, $key2, $value2);
          $otherGenerator->next();
        }
        else {
          yield $keyMapCurrent($key1, $value1) => $valueMapCurrent($key1, $value1);
        }
      }
      while ($otherGenerator->valid()) {
        $key2 = $otherGenerator->key();
        $value2 = $otherGenerator->current();
        yield $keyMapOther($key2, $value2) => $valueMapOther($key2, $value2);
        $otherGenerator->next();
      }
    })());
  }

  /**
   * Creates an returns a new empty extendable iterable.
   */
  public static function empty() : ExtendableIterable {
    return new static([]);
  }

  /**
   * Creates and returns a new extendable iterable from $source.
   *
   * @param iterable $source
   *   Source object over which we are iterating.
   */
  public static function from(iterable $source) : ExtendableIterable {
    return new static($source);
  }

  /**
   * Returns an extendable iterable containing the single key/value pair given.
   *
   * @param mixed $key
   *   Key.
   * @param mixed $value
   *   Value.
   */
  public static function fromKeyAndValue($key, $value) : ExtendableIterable {
    return new static((function () use ($key, $value) {
      yield $key => $value;
    })());
  }

  /**
   * Generates an extendable iterator from a range of integers.
   *
   * @param int $start
   *   Inclusive start value for range.
   * @param int $end
   *   Inclusive end value for range.
   */
  public static function fromRange(int $start, int $end) : ExtendableIterable {
    return new static((function () use ($start, $end) {
      for ($i = $start; $i <= $end; $i++) {
        yield $i;
      }
    })());
  }

  /**
   * Throws an exception indicating an empty collection.
   *
   * @throws \Ranine\Exception\InvalidOperationException
   */
  private static function throwEmptyCollectionException() : void {
    throw new InvalidOperationException('The collection is empty.');
  }

}
