<?php

declare(strict_types = 1);

namespace Ranine\Iteration;

use Ranine\Exception\InvalidOperationException;

/**
 * Iterates recursively over (a ref to) an array and (refs to) its sub-arrays.
 */
class RecursiveReferenceArrayIterator implements \RecursiveIterator {

  private array $arr;
  private string|int|null $key;
  private mixed $value;

  /**
   * Creates a new recursive reference array iterator.
   *
   * @param array $arr
   *   Reference to array to iterate over.
   */
  public function __construct(array &$arr) {
    $this->arr =& $arr;
    $this->setKeyAndValueFromCurrentPosition();
  }

  /**
   * {@inheritdoc}
   */
  public function current() : mixed {
    self::throwIfNotValid();
    return $this->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getChildren() : self {
    self::throwIfNotValid();
    if ($this->hasChildren()) return new self($this->value);
  }

  /**
   * {@inheritdoc}
   */
  public function hasChildren() : bool {
    self::throwIfNotValid();
    return is_array($this->value) && count($this->value) > 0;
  }

  /**
   * {@inheritdoc}
   */
  public function key() : string|int {
    self::throwIfNotValid();
    return $this->key;
  }

  /**
   * {@inheritdoc}
   */
  public function next() : void {
    next($this->arr);
    $this->setKeyAndValueFromCurrentPosition();
  }

  /**
   * {@inheritdoc}
   */
  public function rewind() : void {
    reset($this->arr);
    $this->setKeyAndValueFromCurrentPosition();
  }

  /**
   * @phpstan-assert-if-true !bool $this->value
   * @phpstan-assert-if-true !null $this->key
   */
  public function valid() : bool {
    return $this->key === NULL ? FALSE : TRUE;
  }

  private function setKeyAndValueFromCurrentPosition() : void {
    $this->key = key($this->arr);
    if (array_key_exists($this->key, $this->arr)) {
      $this->value =& $this->arr[$this->key];
    }
    else {
      unset($this->value);
      $this->value = NULL;
      $this->key = NULL;
    }
  }

  private function throwIfNotValid() : void {
    if (!$this->valid()) {
      throw new InvalidOperationException('Iterator is not set to any element.');
    }
  }

}
