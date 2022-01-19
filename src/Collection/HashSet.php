<?php

declare (strict_types = 1);

namespace Ranine\Collection;

use Ranine\Helper\IterationHelpers;

/**
 * Represents set of objects which are stored in buckets indexed by hash code.
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
   * $hashing($item1) === $hashing($item2) if $element1 and $element2 are
   * considered equal within this hash set. For good performance, the hashing
   * function should be such that the hash codes of a set of typical objects are
   * spread fairly evenly and randomly throughout the bit space of integers.
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
   *   If NULL is passed, the static::compareEqualityStrictly() default
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
   *   static::computeHashCode() default hashing is used. In order to avoid
   *   unexpected behavior, you may want to design $hashing in such a way as to
   *   throw an exception if an unexpected item is passed to it.
   */
  public function __construct(?callable $equalityComparison = NULL, ?callable $hashing = NULL) {
    $this->equalityComparison = $equalityComparison ?? static::compareEqualityStrictly(...);
    $this->hashing = $hashing ?? static::computeHashCode(...);
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
      foreach ($bucket as $key => $value) {
        if (($this->equalityComparison)($value, $item)) {
          $foundItem = TRUE;
          break;
        }
      }
      if ($foundItem) {
        $this->count--;
        unset($bucket[$key]);
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

  /**
   * Returns $item1 === $item2.
   */
  public static function compareEqualityStrictly($item1, $item2) : bool {
    return ($item1 === $item2) ? TRUE : FALSE;
  }

  /**
   * Returns a hash code for the array $arr.
   *
   * The hash code is computed by walking through the "tree" associated with
   * $arr and its sub-arrays, and XORing together hashes for the array's keys
   * and non-array values (separator hashes between keys and values, array
   * items, and array depths are also "XORed into" the hash).
   *
   * This function is compatible with static::compareEqualityStrictly().
   */
  public static function computeArrayHashCode(array $arr) : int {
    $iterator = new class($arr) extends \RecursiveArrayIterator {
      public function hasChildren() : bool {
        // Only try to recurse into arrays (otherwise will try to recurse into
        // anything -- array objects, etc.).
        return is_array($this->current());
      }
    };
    $hash = 0;
    // Define separators between keys and values, between items in the array,
    // and between levels in the array's tree. Use prime numbers (and bitwise
    // manipulations thereof) for a hopefully better hash distribution.
    $keyValueSeparator = 1303;
    $itemSeparator = (~-23) >> 5;
    $levelSeparator = 2243 << 5;
    IterationHelpers::walkRecursiveIterator($iterator,
      function (string|int $key, $value) use (&$hash, $keyValueSeparator, $itemSeparator) : bool {
        if (is_string($key)) {
          $keyHash = static::computeStringHashCode($key);
        }
        elseif (is_int($key)) {
          $keyHash = static::computeIntegerHashCode($key);
        }
        $hash ^= $keyHash ^ $keyValueSeparator;
        // If $value is an array, the value hash will be computed as we iterate
        // over the children.
        if (!is_array($value)) {
          $valueHash = static::computeHashCode($value);
          $hash ^= $keyHash ^ $keyValueSeparator ^ $valueHash ^ $itemSeparator;
        }
        return TRUE;
      }, function () use (&$hash, $levelSeparator) : bool {
        $hash ^= $levelSeparator;
        return TRUE;
      }, function () use (&$hash, $levelSeparator) : bool {
        $hash ^= $levelSeparator;
        return TRUE;
      });

    return $hash;
  }

  /**
   * Gets a hash code for boolean $value.
   *
   * This function is compatible with static::compareEqualityStrictly().
   */
  public static function computeBooleanHashCode(bool $value) : int {
    return $value ? 1 : 0;
  }

  /**
   * Gets a hash code for float $value.
   *
   * This function is compatible with static::compareEqualityStrictly().
   */
  public static function computeFloatHashCode(float $value) : int {
    // Use a bitwise representation of $value.
    return static::computeStringHashCode(pack('d', $value));
  }

  /**
   * Gets a hash code for $item.
   *
   * The static::compute*HashCode() function corresponding to the type of $item
   * is used. There is a significant possibility of hash collisions between
   * items of different types if this function is used; hence, it may not be the
   * best choice if you are expecting a lot of type variation within your hash
   * set.
   *
   * This function is compatible with static::compareEqualityStrictly().
   */
  public static function computeHashCode($item) : int {
    if (is_object($item)) {
      return static::computeObjectHashCode($item);
    }
    elseif (is_resource($item)) {
      return static::computeResourceHashCode($item);
    }
    elseif (is_float($item)) {
      return static::computeFloatHashCode($item);
    }
    elseif (is_null($item)) {
      return static::computeNullHashCode();
    }
    elseif (is_bool($item)) {
      return static::computeBooleanHashCode($item);
    }
    elseif (is_int($item)) {
      return static::computeIntegerHashCode($item);
    }
    elseif (is_string($item)) {
      return static::computeStringHashCode($item);
    }
    elseif (is_array($item)) {
      return static::computeArrayHashCode($item);
    }
    else {
      throw new \RuntimeException('An unexpected type was encountered.');
    }
  }

  /**
   * Gets a hash code for integer $value.
   */
  public static function computeIntegerHashCode(int $value) : int {
    return $value;
  }

  /**
   * Gets a hash code for a NULL value.
   */
  public static function computeNullHashCode() : int {
    return 0;
  }

  /**
   * Gets a hash code for object $obj.
   *
   * This function is compatible with static::compareEqualityStrictly().
   */
  public static function computeObjectHashCode(object $obj) : int {
    return spl_object_id($obj);
  }

  /**
   * Gets a hash code for resource $resource.
   *
   * @var resource $resource
   *   Resource. If this is not a resource, a fatal error will occur.
   *
   * This function is compatible with static::compareEqualityStrictly().
   */
  public static function computeResourceHashCode($resource) : int {
    return get_resource_id($resource);
  }

  /**
   * Gets a hash code for string $str.
   *
   * This function is compatible with static::compareEqualityStrictly().
   */
  public static function computeStringHashCode(string $str) : int {
    // See, e.g., https://stackoverflow.com/a/7666668.
    $length = strlen($str);
    if ($length === 0) {
      return 0;
    }
    $hash = ord($str);
    for ($i = 1; $i < $length; $i++) {
      $hash <<= 5;
      $hash ^= ord($str[$i]);
    }

    return $hash;
  }

}
