<?php

declare(strict_types = 1);

namespace Ranine\Collection;

use Ranine\Exception\InvalidOperationException;

/**
 * Can be used to implement a FILO (first in last out) collection.
 *
 * This trait contains an implementation of \IteratorAggregate.
 */
trait StackTrait {

  /**
   * Underlying array.
   *
   * Could also be implemented with a linked list, which might be useful for
   * large stacks.
   *
   * @var array
   */
  private array $arr = [];

  /**
   * Gets an iterator to iterate through the elements of this stack.
   *
   * Iteration proceeds from the first element pushed to the last.
   *
   * @return \Traversable
   *   Iterator.
   */
  public function getIterator() : \Traversable {
    return (function () {
      foreach ($this->arr as $element) {
        yield $element;
      }
    })();
  }

  /**
   * Checks if the given stack is empty.
   *
   * @return bool
   *   Returns 'TRUE' if empty; else 'FALSE'.
   */
  public function isEmpty() : bool {
    return empty($this->arr);
  }

  /**
   * Returns (but does not remove) the top element of the stack.
   *
   * @return mixed
   *   Top stack element.
   */
  private function peekInternal() {
    return end($this->arr);
  }

  /**
   * Returns and removes the top element of the stack.
   *
   * @return mixed
   *   Top stack element.
   *
   * @throws \Ranine\Exception\InvalidOperationException
   *   Thrown if the stack is empty.
   */
  private function popInternal() {
    if ($this->isEmpty()) {
      throw new InvalidOperationException('Cannot pop from an empty stack.');
    }

    $lastKey = array_key_last($this->arr);
    $lastElement = $this->arr[$lastKey];
    unset($this->arr[$lastKey]);
    return $lastElement;
  }

  /**
   * Pushes $element onto the stack.
   *
   * @param mixed $element
   */
  private function pushInternal($element) : void {
    $this->arr[] = $element;
  }

}
