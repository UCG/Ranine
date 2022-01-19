<?php

declare (strict_types = 1);

namespace Ranine\Collection;

use Ranine\Exception\KeyExistsException;

/**
 * Represents unordered set of key/value pairs with hashed lookup.
 *
 * Hash codes based on keys are used to store the key/value pairs. No duplicate
 * keys are allowed.
 *
 * Note: Using this class to, e.g., store values indexed by strings, is much
 * less efficient than using a native PHP array. Hence, use of this class should
 * be restricted to special cases.
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
   * Set of key/value pairs (equality between items <--> key equality).
   */
  private HashSet $pairs;

  /**
   * Creates a new hash map.
   *
   * @param callable|null $equalityComparison
   *   If not NULL, this is the key equality comparison, of the form
   *   ($key1, $key2) : bool. Should return TRUE if $key1 and $key2 are to be
   *   considered equal within this hash set; else should return FALSE. If NULL
   *   If NULL is passed, the HashSet::compareEqualityStrictly() default
   *   comparison is used. In order to avoid unexpected behavior, you may want
   *   to design $equalityComparison in such a way as to throw an exception if
   *   an unexpected item is passed to it.
   * @param callable|null $hashing
   *   If not NULL, this is the hash code generation function, of form
   *   ($key) : int. Should return the hash code of $item, which must satisfy
   *   the property that $hashing($key1) === $hashing($key2) if $key1 and
   *   $key2 are considered equal within this hash set. For good
   *   performance, the hashing function should be such that the hash codes of a
   *   set of typical objects are spread fairly evenly and randomly throughout
   *   the bit space of integers. If NULL is passed, the
   *   HashSet::computeHashCode() default hashing is used. In order to avoid
   *   unexpected behavior, you may want to design $hashing in such a way as to
   *   throw an exception if an unexpected item is passed to it.
   */
  public function __construct(?callable $keyEqualityComparison = NULL, ?callable $keyHashing = NULL) {
    $keyEqualityComparison ??= HashSet::compareEqualityStrictly(...);
    $keyHashing ??= HashSet::computeHashCode(...);
    $this->pairs = new HashSet(
      fn(array $pair1, array $pair2) => $keyEqualityComparison($pair1[static::PAIR_KEY_INDEX], $pair2[static::PAIR_KEY_INDEX]),
      fn(array $pair) => $keyHashing($pair[static::PAIR_KEY_INDEX]));
  }

  /**
   * Adds a key/value pair to the map.
   *
   * @param mixed $key
   *   Key to add.
   * @param mixed $value
   *   Corresponding value to add.
   *
   * @throws \Ranine\Exception\KeyExistsException
   *   Thrown if $key already exists in the collection.
   */
  public function add($key, $value) : void {
    $pair = static::generatePair($key, $value);
    if ($this->pairs->has($pair)) {
      throw new KeyExistsException('The key already exists in the hash map.');
    }
    $this->pairs->add($pair);
  }

  /**
   * Gets the number of key/value pairs in the collection.
   */
  public function getCount() : int {
    return $this->pairs->getCount();
  }

  /**
   * {@inheritdoc}
   */
  public function getIterator(): \Iterator {
    foreach ($this->pairs as $pair) {
      yield $pair[static::PAIR_KEY_INDEX] => $pair[static::PAIR_VALUE_INDEX];
    }
  }

  /**
   * Tells whether key $key exists in the collection.
   */
  public function hasKey($key) : bool {
    // The value doesn't matter for the purposes of key equality comparison, so
    // make up a fake value.
    return $this->pairs->has(static::generatePair($key, 0));
  }

  /**
   * Removes the key/value pair corresponding to the given key.
   *
   * @param mixed $key
   *   Key whose pair should be removed.
   *
   * @return bool
   *   TRUE if the key/value pair was found and successfully removed; FALSE if
   *   the key did not exist in the collection.
   */
  public function remove($key) : bool {
    // The value doesn't matter for the purposes of key equality comparison, so
    // make up a fake value.
    return $this->pairs->remove(static::generatePair($key, 0));
  }

  /**
   * Makes and returns a pair array from the given key and value.
   *
   * @param mixed $key
   *   Key.
   * @param mixed $value
   *   Value.
   */
  private static function generatePair($key, $value) : array {
    return [static::PAIR_KEY_INDEX => $key, static::PAIR_VALUE_INDEX => $value];
  }

}
