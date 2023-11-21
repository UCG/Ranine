<?php

declare(strict_types = 1);

namespace Ranine\Collection;

use Ranine\Exception\InvalidOperationException;

/**
 * Represents a stack (FILO collection).
 *
 * @template T
 * @implements \IteratorAggregate<T>
 */
class Stack implements \IteratorAggregate {

  /**
   * Underlying array.
   *
   * Could also be implemented with a linked list, which might be useful for
   * large stacks.
   *
   * @var T[]
   */
  private array $arr = [];

  /**
   * Returns (but does not remove) the top element of the stack.
   *
   * @return T
   *
   * @throws \Ranine\Exception\InvalidOperationException
   *   Thrown if the stack is empty.
   */
  public function peek() : mixed {
    if ($this->isEmpty()) {
      throw new InvalidOperationException('The stack is empty.');
    }

    return end($this->arr);
  }

  /**
   * Returns and removes the top element of the stack.
   *
   * @return T
   *
   * @throws \Ranine\Exception\InvalidOperationException
   *   Thrown if the stack is empty.
   *
   * @phpstan-impure
   */
  public function pop() : mixed {
    if ($this->isEmpty()) {
      throw new InvalidOperationException('Cannot pop from an empty stack.');
    }

    return array_pop($this->arr);
  }

  /**
   * Pushes $element onto the stack.
   *
   * @param T $element
   */
  public function push($element) : void {
    array_push($this->arr, $element);
  }

  /**
   * {@inheritdoc}
   */
  public function &getIterator() : \Iterator {
    foreach ($this->arr as &$element) {
      yield $element;
    }
  }

  /**
   * Tells whether the stack is empty.
   *
   * @phpstan-assert-if-false !empty $this->arr
   */
  public function isEmpty() : bool {
    return ($this->arr === []) ? TRUE : FALSE;
  }

}
