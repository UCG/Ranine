<?php

declare(strict_types = 1);

namespace Ranine\Collection;

/**
 * Represents a stack (FILO collection).
 */
class Stack implements \IteratorAggregate {

  use StackTrait;

  /**
   * Returns (but does not remove) the top element of the stack.
   *
   * @return mixed
   */
  public function peek() {
    return $this->peekInternal();
  }

  /**
   * Returns and removes the top element of the stack.
   *
   * @return mixed
   *
   * @throws \Ranine\Exception\InvalidOperationException
   *   Thrown if the stack is empty.
   */
  public function pop() {
    return $this->popInternal();
  }

  /**
   * Pushes $element onto the stack.
   *
   * @param mixed $element
   */
  public function push($element) : void {
    $this->pushInternal($element);
  }

}
