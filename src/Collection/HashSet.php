<?php

declare (strict_types = 1);

namespace Ranine\Collection;

use Ranine\Helper\HashCodeHelpers;
use Ranine\Helper\IterationHelpers;

/**
 * Represents set of objects which are stored in buckets indexed by hash code.
 *
 * Note: Using this class to, e.g., store a set of strings, is much less
 * efficient (by about ~5x w/ JIT enabled, by our measurements) than using a
 * native PHP array. Hence, use of this class should be restricted to special
 * cases.
 *
 * @todo Test out (possibly more efficient) custom implemention using
 * \SplFixedArray.
 */
class HashSet implements \IteratorAggregate {

  /**
   * Buckets.
   *
   * The keys are the hash codes, and the values are the buckets.
   *
   * @var array<int, array>
   */
  private array $buckets = [];

  /**
   * Number of items currently in this hash set.
   */
  private int $count = 0;

  /**
   * Equality comparison, of form ($item1, $item2) : bool.
   *
   * Returns TRUE if $item1 and $item2 are to be considered equal within this
   * hash set; else returns FALSE.
   *
   * @var callable
   */
  private $equalityComparison;

  /**
   * Hash code function, of form ($item) : int.
   *
   * Returns the hash code of $item, which must satisfy the property that
   * $hashing($item1) === $hashing($item2) if $item1 and $item2 are considered
   * equal within this hash set. For good performance, the hashing function
   * should be such that the hash codes of a set of typical objects are spread
   * fairly evenly and randomly throughout the bit space of integers.
   *
   * @var callable
   */
  private $hashing;

  /**
   * Creates a new hash set.
   *
   * @param callable|null $equalityComparison
   *   If not NULL, this is the equality comparison, of the form
   *   ($item1, $item2) : bool. Should return TRUE if $item1 and $item2 are to
   *   be considered equal within this hash set; else should return FALSE.
   *   If NULL is passed, the
   *   \Ranine\Helper\HashCodeHelpers::compareEqualityStrictly() default
   *   comparison is used. In order to avoid unexpected behavior, you may want
   *   to design $equalityComparison in such a way as to throw an exception if
   *   an unexpected item is passed to it.
   * @param callable|null $hashing
   *   If not NULL, this is the hash code generation function, of form
   *   ($item) : int. Should return the hash code of $item, which must satisfy
   *   the property that $hashing($item1) === $hashing($item2) if $element1 and
   *   $element2 are considered equal within this hash set. For good
   *   performance, the hashing function should be such that the hash codes of a
   *   set of typical objects are spread fairly evenly and randomly throughout
   *   the bit space of integers. If NULL is passed, the
   *   \Ranine\Helper\HashCodeHelpers::computeHashCode() default hashing is
   *   used. In order to avoid unexpected behavior, you may want to design
   *   $hashing in such a way as to throw an exception if an unexpected item is
   *   passed to it.
   * @param iterable $initialItems
   *   Initial items to populate hash set. Duplicate items are ignored.
   */
  public function __construct(?callable $equalityComparison = NULL, ?callable $hashing = NULL, iterable $initialItems = []) {
    $this->equalityComparison = $equalityComparison ?? fn($item1, $item2) : bool => HashCodeHelpers::compareEqualityStrictly($item1, $item2);
    $this->hashing = $hashing ?? fn($item) : int => HashCodeHelpers::computeHashCode($item);
    foreach ($initialItems as $item) {
      $this->add($item);
    }
  }

  /**
   * Adds $item to this hash set.
   *
   * @return bool
   *   TRUE if the item did not exist and was successfully added; FALSE if the
   *   item already existed.
   */
  public function add($item) : bool {
    $hash = ($this->hashing)($item);
    if (array_key_exists($hash, $this->buckets)) {
      $bucket =& $this->buckets[$hash];
      if ($this->isItemInBucket($bucket, $item)) {
        return FALSE;
      }
      $bucket[] = $item;
    }
    else {
      $this->buckets[$hash][] = $item;
    }
    $this->count++;
    return TRUE;
  }

  /**
   * Gets the number of items in this hash set.
   */
  public function getCount() : int {
    return $this->count;
  }

  /**
   * {@inheritdoc}
   */
  public function getIterator() : \Iterator {
    foreach ($this->buckets as $bucket) {
      foreach ($bucket as $value) {
        yield $value;
      }
    }
  }

  /**
   * Tell whether $item exists in this hash set.
   */
  public function has($item) : bool {
    if ($this->count === 0) {
      return FALSE;
    }

    $hash = ($this->hashing)($item);
    return array_key_exists($hash, $this->buckets) && $this->isItemInBucket($this->buckets[$hash], $item);
  }

  /**
   * Removes $item from this hash set.
   *
   * @return bool
   *   TRUE if $item existed in the hash set and was successfully removed; FALSE
   *   if $item did not exist in the hash set.
   */
  public function remove($item) : bool {
    $hash = ($this->hashing)($item);
    if (array_key_exists($hash, $this->buckets)) {
      // Find and remove the item from the bucket.
      $bucket =& $this->buckets[$hash];
      $foundItem = FALSE;
      foreach ($bucket as $key => $bucketItem) {
        if (($this->equalityComparison)($bucketItem, $item)) {
          $foundItem = TRUE;
          break;
        }
      }
      if ($foundItem) {
        $this->count--;
        unset($bucket[$key]);
        if (count($bucket) === 0) {
          unset($this->buckets[$hash]);
        }
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Tells whether $item is in bucket $bucket.
   */
  private function isItemInBucket(array $bucket, $item) : bool {
    foreach ($bucket as $bucketItem) {
      if (($this->equalityComparison)($item, $bucketItem)) {
        return TRUE;
      }
    }
    return FALSE;
  }

}
