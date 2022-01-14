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
   * Tells whether the stack is empty.
   */
  public function isEmpty() : bool {
    return empty($this->arr);
  }

  /**
   * Returns (but does not remove) the top element of the stack.
   */
  private function peekInternal() : mixed {
    return end($this->arr);
  }

  /**
   * Returns and removes the top element of the stack.
   *
   * @throws \Ranine\Exception\InvalidOperationException
   *   Thrown if the stack is empty.
   */
  private function popInternal() : mixed {
    if ($this->isEmpty()) {
      throw new InvalidOperationException('Cannot pop from an empty stack.');
    }

    return array_pop($this->arr);
  }

  /**
   * Pushes $element onto the stack.
   */
  private function pushInternal($element) : void {
    array_push($this->arr, $element);
  }

}
