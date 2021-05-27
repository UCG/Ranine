<?php

declare(strict_types = 1);

namespace Ranine\Collection;

/**
 * Represents a stack (FILO collection) of arrays.
 */
class StackOfArrays implements \IteratorAggregate {

  use StackTrait;

  /**
   * Returns (but does not remove) the top element of the stack.
   *
   * @return array
   *   Top stack element.
   */
  public function peek() : array {
    return $this->peekInternal();
  }

  /**
   * Returns and removes the top element of the stack.
   *
   * @return array
   *   Top stack element.
   */
  public function pop() : array {
    return $this->popInternal();
  }

  /**
   * Pushes $element onto the stack.
   *
   * @param array $element
   */
  public function push(array $element) : void {
    $this->pushInternal($element);
  }

}