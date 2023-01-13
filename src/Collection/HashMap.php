<?php

declare (strict_types = 1);

namespace Ranine\Collection;

use Ranine\Exception\KeyExistsException;
use Ranine\Exception\KeyNotFoundException;
use Ranine\Helper\HashCodeHelpers;

/**
 * Represents unordered set of key/value pairs with hashed lookup.
 *
 * Hash codes based on keys are used to store the key/value pairs. No duplicate
 * keys are allowed.
 *
 * Note: Using this class to, e.g., store values indexed by strings, is much
 * less efficient than using a native PHP array. Hence, use of this class should
 * be restricted to special cases.
 *
 * @template TKey
 * @template TValue
 * @implements \IteratorAggregate<TKey, TValue>
 */
class HashMap implements \IteratorAggregate {

  /**
   * Array index in a key/value pair corresponding to the "key of the key."
   */
  private const PAIR_KEY_INDEX = 0;

  /**
   * Array index in a key/value pair corresponding to the "key of the value."
   */
  private const PAIR_VALUE_INDEX = 1;

  /**
   * Buckets. The keys are hash codes, and the values are the buckets.
   *
   * Each bucket is an array of key/value pairs, each having the form
   * [self::PAIR_KEY_INDEX => $key, self::PAIR_VALUE_INDEX => $value].
   *
   * @var array<int, array<int, TKey|TValue>[]>
   * @phpstan-var array<int, array{0: TKey, 1: TValue}[]>
   */
  private array $buckets = [];

  /**
   * Number of key/value pairs currently in this hash map.
   *
   * @phpstan-var int<0, max>
   */
  private int $count = 0;

  /**
   * Key equality comparison.
   *
   * Returns TRUE if $key1 and $key2 are to be considered equal within this
   * hash map; else returns FALSE.
   *
   * @var callable(TKey $key1, TKey $key2) : bool
   */
  private $keyEqualityComparison;

  /**
   * Key hash code function.
   *
   * Returns the hash code of $key, which must satisfy the property that
   * $hashing($key1) === $hashing($key2) if $key1 and $key2 are considered equal
   * within this hash set. For good performance, the hashing function should be
   * such that the hash codes of a set of typical objects are spread fairly
   * evenly and randomly throughout the bit space of integers.
   *
   * @var callable(TKey $key) : int
   */
  private $keyHashing;

  /**
   * Creates a new hash map.
   *
   * @param ?callable(TKey $key1, TKey $key2) : bool $keyEqualityComparison
   *   If not NULL, this is the key equality comparison. Should return TRUE if
   *   $key1 and $key2 are to be considered equal within this hash set; else
   *   should return FALSE. If NULL is passed, the
   *   \Ranine\Helper\HashCodeHelpers::compareEqualityStrictly() default
   *   comparison is used. In order to avoid unexpected behavior, you may want
   *   to design $keyEqualityComparison in such a way as to throw an exception
   *   if an unexpected item is passed to it.
   * @param ?callable(TKey $key) : int $keyHashing
   *   If not NULL, this is the hash code generation function. Should return the
   *   hash code of $item, which must satisfy the property that
   *   $hashing($key1) === $hashing($key2) if $key1 and $key2 are considered
   *   equal within this hash set. For good performance, the hashing function
   *   should be such that the hash codes of a set of typical objects are spread
   *   fairly evenly and randomly throughout the bit space of integers. If NULL
   *   is passed, the \Ranine\Helper\HashCodeHelpers::computeHashCode() default
   *   hashing is used. In order to avoid unexpected behavior, you may want to
   *   design $keyHashing in such a way as to throw an exception if an
   *   unexpected item is passed to it.
   * @param iterable<TKey, TValue> $initialPairs
   *   Initial keys and corresponding values with which to populate hash map.
   *
   * @throws \Ranine\Exception\KeyExistsException
   *   Thrown if there are duplicate keys in $initialKeys.
   */
  public function __construct(?callable $keyEqualityComparison = NULL, ?callable $keyHashing = NULL, iterable $initialPairs = []) {
    $this->keyEqualityComparison = $keyEqualityComparison ?? HashCodeHelpers::compareEqualityStrictly(...);
    $this->keyHashing = $keyHashing ?? HashCodeHelpers::computeHashCode(...);
    foreach ($initialPairs as $key => $value) {
      $this->add($key, $value);
    }
  }

  /**
   * Adds a key/value pair to the map.
   *
   * @param TKey $key
   *   Key to add.
   * @param TValue $value
   *   Corresponding value to add.
   *
   * @throws \Ranine\Exception\KeyExistsException
   *   Thrown if $key already exists in the collection.
   */
  public function add($key, $value) : void {
    $hash = ($this->keyHashing)($key);
    if (array_key_exists($hash, $this->buckets)) {
      $bucket =& $this->buckets[$hash];
      if ($this->isKeyInBucket($bucket, $key)) {
        throw new KeyExistsException('The key already exists in the hash table.');
      }
      $bucket[] = self::generatePair($key, $value);
    }
    else {
      $this->buckets[$hash][] = self::generatePair($key, $value);
    }
    $this->count++;
  }

  /**
   * Gets the value associated with the given $key.
   *
   * @param TKey $key
   *
   * @return TValue
   *
   * @throws \Ranine\Exception\KeyNotFoundException
   *   Thrown if $key was not found in the collection.
   */
  public function get($key) : mixed {
    $hash = ($this->keyHashing)($key);
    if (array_key_exists($hash, $this->buckets)) {
      foreach ($this->buckets[$hash] as $pair) {
        $bucketItemKey = $pair[self::PAIR_KEY_INDEX];
        if (($this->keyEqualityComparison)($key, $bucketItemKey)) {
          return $pair[self::PAIR_VALUE_INDEX];
        }
      }
    }

    throw new KeyNotFoundException('The key was not found in the hash table.');
  }

  /**
   * Gets the number of key/value pairs in the collection.
   *
   * @phpstan-return int<0, max>
   */
  public function getCount() : int {
    return $this->count;
  }

  /**
   * {@inheritdoc}
   */
  public function getIterator() : \Iterator {
    foreach ($this->buckets as $bucket) {
      foreach ($bucket as $pair) {
        yield $pair[self::PAIR_KEY_INDEX] => $pair[self::PAIR_VALUE_INDEX];
      }
    }
  }

  /**
   * Tells whether key $key exists in the collection.
   *
   * @param TKey $key
   */
  public function hasKey($key) : bool {
    if ($this->count === 0) {
      return FALSE;
    }

    $hash = ($this->keyHashing)($key);
    return array_key_exists($hash, $this->buckets) && $this->isKeyInBucket($this->buckets[$hash], $key);
  }

  /**
   * Removes the key/value pair corresponding to the given key.
   *
   * @param TKey $key
   *   Key whose pair should be removed.
   *
   * @return bool
   *   TRUE if the key/value pair was found and successfully removed; FALSE if
   *   the key did not exist in the collection.
   *
   * @phpstan-impure
   */
  public function remove($key) : bool {
    $hash = ($this->keyHashing)($key);
    if (array_key_exists($hash, $this->buckets)) {
      // Find and remove the item from the bucket.
      $bucket =& $this->buckets[$hash];
      $foundItem = FALSE;
      foreach ($bucket as $bucketKey => $pair) {
        if (($this->keyEqualityComparison)($key, $pair[self::PAIR_KEY_INDEX])) {
          $foundItem = TRUE;
          break;
        }
      }
      if ($foundItem) {
        assert($this->count > 0);
        $this->count--;
        assert(isset($bucketKey));
        unset($bucket[$bucketKey]);
        if (count($bucket) === 0) {
          unset($this->buckets[$hash]);
        }
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Sets the given key to the given value.
   *
   * @param TKey $key
   *   Key.
   * @param TValue $value
   *   Value.
   * @param bool $createIfKeyNotInMap
   *   TRUE to create a new key/value pair if the key does not exist in the map;
   *   FALSE not to do so.
   *
   * @return bool
   *   TRUE if a new key/value pair was created, else FALSE.
   *
   * @throws \Ranine\Exception\KeyNotFoundException
   *   Thrown if $key does not exist in this hash map, and $createIfKeyNotInMap
   *   is FALSE.
   *
   * @phpstan-impure
   */
  public function set($key, $value, bool $createIfKeyNotInMap = FALSE) : bool {
    $hash = ($this->keyHashing)($key);
    if (array_key_exists($hash, $this->buckets)) {
      $bucket =& $this->buckets[$hash];
      foreach ($bucket as &$pair) {
        $bucketItemKey = $pair[self::PAIR_KEY_INDEX];
        if (($this->keyEqualityComparison)($key, $bucketItemKey)) {
          $pair[self::PAIR_VALUE_INDEX] = $value;
          return FALSE;
        }
      }
    }
    else {
      $this->buckets[$hash] = [];
      $bucket =& $this->buckets[$hash];
    }

    if (!$createIfKeyNotInMap) throw new KeyNotFoundException('The key does not exist in the hash map.');
    $bucket[] = self::generatePair($key, $value);
    $this->count++;
    return TRUE;
  }

  /**
   * Tells whether pair key $key is in bucket $bucket.
   *
   * @param array<int, TKey|TValue>[] $bucket
   * @phpstan-param array{0: TKey, 1: TValue}[] $bucket
   * @param TKey $key
   */
  private function isKeyInBucket(array $bucket, $key) : bool {
    foreach ($bucket as $pair) {
      if (($this->keyEqualityComparison)($key, $pair[self::PAIR_KEY_INDEX])) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Makes and returns a pair array from the given key and value.
   *
   * @param TKey $key
   *   Key.
   * @param TValue $value
   *   Value.
   *
   * @return array<int, TKey|TValue>
   * @phpstan-return array{0: TKey, 1: TValue}
   */
  private static function generatePair($key, $value) : array {
    return [self::PAIR_KEY_INDEX => $key, self::PAIR_VALUE_INDEX => $value];
  }

}
