<?php

declare(strict_types = 1);

namespace Ranine\Helper;

use Ranine\Collection\Stack;

/**
 * Static helper methods to deal with iteration.
 *
 * @static
 */
final class IterationHelpers {

  /**
   * Empty private constructor to ensure no one instantiates this class.
   */
  private function __construct() {
  }

  /**
   * Walks the given recursive iterator, applying $operation to each element.
   *
   * Walks every element, not just leaf nodes. Walks elements in the order they
   * would be walked if you went "down" a tree graph like the one below:
   * 
   * > Node [1st element visited]
   * > --Node [2nd '' '']
   * > ----Leaf [3rd '' '']
   * > ----Leaf [etc.]
   * > --Node
   * > Node
   * ...
   *
   * @param \RecursiveIterator $iterator
   *   Iterator to walk.
   * @param callable $operation
   *   Operation to apply for every element, of the form
   *   ($key, $value, $context) : bool. The "context" object stores information
   *   associated with the current level being traversed. The return value
   *   indicates whether iteration should be continued ('TRUE' to continue,
   *   'FALSE' to halt).
   * @param callable|null $drillDown
   *   Of the form ($key, $value, $context) : mixed, this is called before
   *   moving down a level, and allows information to be stored, as the return
   *   value of this callable, with the level to which we are moving. The key
   *   and value of the node whose children we are about to move down to are
   *   passed to this function, along with the current context (of the level
   *   *above* the level whose context information we are creating). If 'NULL'
   *   is passed for this parameter, the function ($k, $v, $c) => NULL is used.s
   * @param mixed $initialContext
   *   The context information to be stored at the root level.
   *
   * @return bool
   *   Returns 'FALSE' if it was necessary to exit early because of a 'FALSE'
   *   return value of $operation; otherwise returns 'TRUE'.
   */
  public static function walkRecursiveIterator(\RecursiveIterator $iterator, callable $operation, ?callable $drillDown = NULL, $initialContext = NULL) : bool {
    if ($drillDown === NULL) {
      $drillDown = fn() => NULL;
    }

    // Prepare the iterator.
    $iterator->rewind();
    if (!$iterator->valid()) {
      return;
    }

    // Stores information associated with each parent level. This is a
    // collection of two-element arrays, the first element of which is a
    // ?\RecursiveIterator object indicating the parent \RecursiveIterator for
    // this level ('NULL' indicates no parent), and the second is the user
    // context information.
    $parentLevels = new Stack();
    // Store the current level information.
    $currentLevel = [NULL, $initialContext];

    // Keep track of the current iterator.
    $currentIterator = $iterator;
    // Loop until we run out of elements.
    do {
      $key = $iterator->key();
      $value = $iterator->current();
      /** @var \RecursiveIterator|null */
      $parentIterator = $currentLevel[0];
      $currentContext = $currentLevel[1];

      if (!$operation($key, $value, $currentContext)) {
        return FALSE;
      }

      // Move. Try the following moves, in this order:
      // 1) Move down a level, to the first child element, if possible.
      // 2) Otherwise, move to the next sibling of this element.
      // 3) Otherwise, move to the next sibling of the parent of this element.
      // Move 1:
      if (($childIterator = static::prepareChildIterator($currentIterator)) !== NULL) {
        // Push the current level onto $parentLevels, and create and store the
        // current level information.
        // Create the information for the next level and push it onto the stack.
        $childContext = $drillDown($key, $value, $currentContext);
        $parentLevels->push($currentLevel);
        $currentLevel = [$iterator, $childContext];
        // Move to the child iterator.
        /** @var \RecursiveIterator */
        $currentIterator = $childIterator;
        continue;
      }
      
      // Move 2: Try to move the iterator forward.
      $iterator->next();
      if ($iterator->valid()) {
        // We moved successfully.
        continue;
      }

      if ($parentIterator !== NULL) {
        // Move 3. We have reached the end of the current set of children. We
        // need to move to the parent's next sibling, if possible. First, we
        // must move up a level.
        /** @var \RecursiveIterator */
        $currentIterator = $parentIterator;
        // Try to move to the next sibling of the parent.
        $currentIterator->next();
        if ($currentIterator->valid()) {
          // Our move was successful. Pop off the top context and use it to
          // replace the current level information.
          $currentLevel = $parentLevels->pop();
          continue;
        }
      }
    } while (FALSE);

    return TRUE;
  }

  /**
   * Resets and returns child iterator at current position of parent iterator.
   *
   * @param \RecursiveIterator $parent
   *   Parent iterator.
   *
   * @return \RecursiveIterator|null
   *   Child iterator, or 'NULL' if the iterator had no child iterator, or the
   *   child iterator was empty.
   */
  private static function prepareChildIterator(\RecursiveIterator $parent) : ?\RecursiveIterator {
    if (!$parent->hasChildren()) {
      return NULL;
    }
    else {
      $childIterator = $parent->getChildren();
      $childIterator->rewind();
      return $childIterator->valid() ? $childIterator : NULL;
    }
  }

}
