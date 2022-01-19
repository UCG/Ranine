<?php

declare (strict_types = 1);

namespace Ranine\Helper;

/**
 * Static helper methods to deal with hash code generation and associated stuff.
 *
 * @static
 */
final class HashCodeHelpers {

  /**
   * Empty private constructor to ensure no one instantiates this class.
   */
  private function __construct() {
  }

  /**
   * Returns $item1 === $item2.
   *
   * This equality comparison is consistent with static::computeHashCode().
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
