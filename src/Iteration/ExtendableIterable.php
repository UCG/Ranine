<?php

declare(strict_types = 1);

namespace Ranine\Iteration;

use Ranine\Exception\InvalidOperationException;
use Ranine\Helper\ThrowHelpers;

/**
 * Iterates through an iterable object while providing useful extension methods.
 *
 * Sample use -- calculate sum of squares of first five items from array input
 * where each item is less than 10:
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
 *
 * @template TKey
 * @template TValue
 * @implements \IteratorAggregate<TKey, TValue>
 */
class ExtendableIterable implements \IteratorAggregate {

  /**
   * The source iterable.
   *
   * @var iterable<TKey, TValue>
   */
  private iterable $source;

  /**
   * Creates a new extendable iterator.
   *
   * @param iterable<TKey, TValue> $source
   *   Iterator which yields the keys and values over which we are iterating.
   */
  protected function __construct(iterable $source) {
    $this->source = $source;
  }

  /**
   * Tells whether $predicate applies to all items in this collection.
   *
   * @param callable(TKey $key, TValue $value) : bool $predicate
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
   * @param callable(TKey $key, TValue $value) : bool $predicate
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
   * @param iterable<TKey, TValue> $other
   *
   * @return self<TKey, TValue>
   *   Appended output -- the order of elements in this iterator and $source is
   *   preserved.
   */
  public function append(iterable $other) : self {
    return new self((function () use ($other) {
      yield from $this->source;
      yield from $other;
    })());
  }

  /**
   * Appends a single key/value pair to the end of this collection.
   *
   * @param TKey $key
   *   Key.
   * @param TValue $value
   *   Value.
   *
   * @return self<TKey, TValue>
   *   Resulting iterable.
   */
  public function appendKeyAndValue($key, $value) : self {
    return new self((function () use ($key, $value) {
      yield from $this->source;
      yield $key => $value;
    })());
  }

  /**
   * Appends a single value to the end of this collection.
   *
   * The key is automatically generated.
   *
   * @param TValue $value
   *   Value to append.
   *
   * @return self<TKey|int, TValue>
   *   Resulting iterable.
   */
  public function appendValue($value) : self {
    return new self((function () use ($value) {
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
   * @param callable(TKey $key, TValue $value) : void $processing
   *
   * @return self<TKey, TValue>
   *   Resulting iterable.
   */
  public function apply(callable $processing) : self {
    return new self((function () use ($processing) {
      foreach ($this->source as $key => $value) {
        $processing($key, $value);
        yield $key => $value;
      }
    })());
  }

  /**
   * Applies function sequentially to elements of this and another collection.
   *
   * The two iterables are iterated through simultaneously, and $processingBoth
   * is called on each iteration step when both iterables are valid.
   * $processingCurrent is called when the current iterable (but not $other) is
   * valid. $processingOther is called when $other is valid, but $current has no
   * more elements. When one iterable terminates, iteration continues through
   * the other iterable if it is still valid.
   *
   * @template TKeyOther
   * @template TValueOther
   *
   * @param iterable<TKeyOther, TValueOther> $other
   *   Other iterable.
   * @param callable(TKey $keyFromThisObject, TValue $valueFromThisObject, TKeyOther $keyFromOtherIterable, TValueOther $valueFromOtherIterable) : void $processingBoth
   *   Called when both iterators are valid.
   * @param callable(TKey $keyFromThisObject, TValue $valueFromThisObject) : void $processingCurrent
   *   Called when only this iterator is valid.
   * @param callable(TKeyOther $keyFromOtherIterable, TValueOther $valueFromOtherIterable) : void $processingOther
   *   Called when only the other iterator is valid.
   */
  public function applyWith(iterable $other, callable $processingBoth, callable $processingCurrent, callable $processingOther) : void {
    // Wrap $other in a generator in order to ensure we can iterate through it
    // manually.
    $otherGenerator = (function () use ($other) { yield from $other; })();
    $otherGenerator->rewind();
    foreach ($this->source as $key1 => $value1) {
      if ($otherGenerator->valid()) {
        $key2 = $otherGenerator->key();
        $value2 = $otherGenerator->current();
        $processingBoth($key1, $value1, $key2, $value2);
        $otherGenerator->next();
      }
      else {
        $processingCurrent($key1, $value1);
      }
    }
    while ($otherGenerator->valid()) {
      $key2 = $otherGenerator->key();
      $value2 = $otherGenerator->current();
      $processingOther($key2, $value2);
      $otherGenerator->next();
    }
  }

  /**
   * Counts the elements in this iterable.
   *
   * NOTE: This function will advance through the source collection for this
   * object if it isn't \Countable or an array, which could prevent foreach()
   * from being used on the collection in the future.
   *
   * @return int
   *   Count.
   * @php-stan-return int<0, max>
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
   * @param callable(TKey $key, TValue $value) : ?iterable<TKey, TValue> $expansion
   *   This function returns either NULL (if the element is not to be expanded)
   *   or an iterable (if the element is to be expanded into that iterable).
   *
   * @return self<TKey, TValue>
   *   Resulting iterator, which will iterate through items in this iterable
   *   that were not expanded, and through the expansion of any sub-iterables,
   *   as they are encountered.
   */
  public function expand(callable $expansion) : self {
    return new self((function () use ($expansion) {
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
   * @param callable(TKey $key, TValue $value) : bool $filter
   *   Filter -- takes each key and value and returns TRUE (to preserve the
   *   value in the output) or FALSE (to not preserve the value in the output).
   *
   * @return self<TKey, TValue>
   *   Filtered output -- the order of elements in this iterator is preserved.
   */
  public function filter(callable $filter) : self {
    return new self((function () use ($filter) {
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
   * @return TValue
   *
   * @throws \Ranine\Exception\InvalidOperationException
   *   Thrown if the collection if empty.
   */
  public function first() : mixed {
    foreach ($this->source as $value) {
      return $value;
    }
    self::throwEmptyCollectionException();
  }

  /**
   * Grabs the first key of this collection, if possible.
   *
   * @return TKey
   *
   * @throws \Ranine\Exception\InvalidOperationException
   *   Thrown if the collection if empty.
   */
  public function firstKey() : mixed {
    foreach ($this->source as $key => $value) {
      return $key;
    }
    self::throwEmptyCollectionException();
  }

  /**
   * Grabs the first key and value of this collection, if possible.
   *
   * @param TKey $key
   *   (output) First key.
   * @param TValue $value
   *   (output) First value.
   *
   * @throws \Ranine\Exception\InvalidOperationException
   *   Thrown if the collection if empty.
   */
  public function firstKeyAndValue(&$key, &$value) : void {
    foreach ($this->source as $key => $value) {
      return;
    }
    self::throwEmptyCollectionException();
  }

  /**
   * Gets an iterator for looping through the keys/values of this object.
   *
   * @return \Iterator<TKey, TValue>
   */
  public function getIterator() : \Iterator {
    yield from $this->source;
  }

  /**
   * Gets a collection that can iterate the keys from this iterable.
   *
   * @return self<int, TKey>
   */
  public function getKeys() : self {
    return new self((function () {
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
   * @template TKeyOut
   * @template TValueOut
   *
   * @param ?callable(TKey $key, TValue $value) : TValueOut $valueMap
   *   Takes each key and value and returns an output value. If NULL is passed
   *   for this parameter, the value map ($k, $v) => $v is used.
   * @param ?callable(TKey $key, TValue $value) : TKeyOut $keyMap
   *   Takes each key and value and returns an output key. If NULL is passed for
   *   this parameter, the key map ($k, $v) => $k is used.
   *
   * @return self<TKeyOut, TValueOut>
   *   Output iterable. The order of elements is preserved.
   */
  public function map(?callable $valueMap, ?callable $keyMap = NULL) : self {
    if ($valueMap === NULL) {
      $valueMap = fn($k, $v) => $v;
    }
    if ($keyMap === NULL) {
      $keyMap = fn($k) => $k;
    }

    return new self((function () use ($keyMap, $valueMap) {
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
   * @template TValueOut
   *
   * @param ?callable(TKey $key, TValue $value) : TValueOut $valueMap
   *   Takes each key and value and returns an output value. If NULL is passed
   *   for this parameter, the value map ($k, $v) => $v is used.
   *
   * @return self<int, TValueOut>
   *   Output iterable. The order of elements is preserved.
   */
  public function mapSequentialKeys(?callable $valueMap) : self {
    if ($valueMap === NULL) {
      $valueMap = fn($k, $v) => $v;
    }

    return new self((function () use ($valueMap) {
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
   * @template TAggregate
   *
   * @param callable(TKey $key, TValue $value, TAggregate $current) : TAggregate $reduction
   *   Produces resulting value of aggregate object (in that step of
   *   aggregation) from current value of aggregate object and key and value.
   *   The resulting value of the aggregate object will be passed to the
   *   reduction for the next key and value.
   * @param TAggregate $initialValue
   *   Initial value of the aggregate object (passed to $reduction() on its
   *   first call).
   *
   * @return TAggregate
   *   Value of the aggregate object returned from the last call to $reduction.
   */
  public function reduce(callable $reduction, $initialValue) : mixed {
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
   * @phpstan-param int<0, max> $num
   *
   * @return self<TKey, TValue>
   *   Items. The order of elements is preserved.
   *
   * @throws \InvalidArgumentException
   *   Thrown if $num is less than zero.
   */
  public function take(int $num) : self {
    ThrowHelpers::throwIfLessThanZero($num, 'num');
    if ($num === 0) {
      return static::empty();
    }

    return new self((function () use ($num) {
      $i = 0;
      foreach ($this->source as $key => $value) {
        yield $key => $value;
        $i++;
        if ($i === $num) {
          break;
        }
      }
    })());
  }

  /**
   * Iterates through at most $max elements while $predicate is TRUE.
   *
   * @param callable $predicate(TKey $key, TValue $value) : bool.
   * @param int|null $max
   *   Max number of elements to take. Pass NULL for "unlimited."
   * @phpstan-param null|int<0, max> $max
   *
   * @return self<TKey, TValue>
   *   Items. The order of elements is preserved.
   *
   * @throws \InvalidArgumentException
   *   Thrown if $max is an integer less than zero.
   */
  public function takeWhile(callable $predicate, ?int $max = NULL) : self {
    if ($max === NULL) {
      return new self((function () use ($predicate) {
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
      if ($max === 0) {
        return static::empty();
      }

      return new self((function () use ($predicate, $max) {
        $i = 0;
        foreach ($this->source as $key => $value) {
          if (!$predicate($key, $value)) {
            break;
          }
          yield $key => $value;
          $i++;
          if ($i === $max) {
            break;
          }
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
   * @return array<TKey|int, TValue>
   *   Resulting array. The order of elements is preserved.
   * @phpstan-return ($preserveKeys is TRUE ? array<TKey, TValue> : array<int, TValue>)
   */
  public function toArray(bool $preserveKeys = TRUE) : array {
    if (is_array($this->source) && $preserveKeys) {
      return $this->source;
    }
    elseif (($this->source instanceof \ArrayObject) && $preserveKeys) {
      return $this->source->getArrayCopy();
    }
    else {
      assert($this->source instanceof \Traversable);
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
   * - $keyMapCurrent and $valueMapCurrent (respectively), if $other has
   *   terminated, but this iterable still has remaining items.
   * - $keyMapOther and $valueMapOther (respectively), if this iterable has
   *   terminated, but $other is still valid.
   *
   * When one iterable terminates, iteration continues through the other
   * iterable if it is still valid.
   *
   * @template TKeyOther
   * @template TValueOther
   * @template TKeyOut
   * @template TValueOut
   *
   * @param iterable<TKeyOther, TValueOther> $other
   *   Other iterable.
   * @param callable(TKey $keyFromThisObject, TValue $valueFromThisObject, TKeyOther $keyFromOtherIterable, TValueOther $valueFromOtherIterable) : TKeyOut $keyMapBoth
   *   Map to produce output keys when both iterators are valid.
   * @param callable(TKey $keyFromThisObject, TValue $valueFromThisObject, TKeyOther $keyFromOtherIterable, TValueOther $valueFromOtherIterable) : TValueOut $valueMapBoth
   *   Map to produce output values when both iterators are valid.
   * @param ?callable(TKey $keyFromThisObject, TValue $valueFromThisObject) : TKeyOut $keyMapCurrent
   *   Map to produce output keys when only this iterable (this instance)
   *   remains valid. If NULL is passed, the map ($k, $v) => $k is used.
   * @param ?callable(TKey $keyFromThisObject, TValue $valueFromThisObject) : TValueOut $valueMapCurrent
   *   Map to produce output values when only this iterable (this instance)
   *   remains valid. If NULL is passed, the map ($k, $v) => $v is used.
   * @param ?callable(TKeyOther $keyFromOtherIterable, TValueOther $valueFromOtherIterable) : TKeyOut $keyMapOther
   *   Map to produce output keys when only $other is valid. If NULL is passed,
   *   the map ($k, $v) => $k is used.
   * @param ?callable(TKeyOther $keyFromOtherIterable, TValueOther $valueFromOtherIterable) : TValueOut $valueMapOther
   *   Map to produce output values when only $other is valid. If NULL is
   *   passed, the map ($k, $v) => $v is used.
   *
   * @return self<TKeyOut, TValueOut>
   *   Resulting collection. The order of elements in this iterator and $other
   *   is preserved.
   */
  public function zip(iterable $other,
    callable $keyMapBoth,
    callable $valueMapBoth,
    ?callable $keyMapCurrent = NULL,
    ?callable $valueMapCurrent = NULL,
    ?callable $keyMapOther = NULL,
    ?callable $valueMapOther = NULL,
    ) : self {
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
    return new self((function () use ($otherGenerator, $keyMapBoth,
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
  public static function empty() : self {
    return new self([]);
  }

  /**
   * Creates and returns a new extendable iterable from $source.
   *
   * @template TResultKey
   * @template TResultValue
   *
   * @param iterable<TResultKey, TResultValue> $source
   *   Source object over which we are iterating.
   *
   * @return self<TResultKey, TResultValue>
   */
  public static function from(iterable $source) : self {
    return new self($source);
  }

  /**
   * Returns an extendable iterable containing the single key/value pair given.
   *
   * @template TResultKey
   * @template TResultValue
   *
   * @param TResultKey $key
   *   Key.
   * @param TResultValue $value
   *   Value.
   *
   * @return self<TResultKey, TResultValue>
   */
  public static function fromKeyAndValue($key, $value) : self {
    return new self((function () use ($key, $value) {
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
   *
   * @return self<int, int>
   */
  public static function fromRange(int $start, int $end) : self {
    return new self((function () use ($start, $end) {
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
  private static function throwEmptyCollectionException() : never {
    throw new InvalidOperationException('The collection is empty.');
  }

}
