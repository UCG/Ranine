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
   *   indicates whether iteration should be continued (TRUE to continue,
   *   FALSE to halt).
   * @param callable|null $drillDown
   *   Of the form ($key, $value, &$context) : bool, this is called before
   *   moving down a level, and allows one to prevent the drill-down operation
   *   (by returning FALSE). The context is passed by reference, so that it can
   *   be changed for the lower level. If NULL is passed for this parameter, the
   *   function ($k, $v, &$c) { $c = NULL; return TRUE; } is used.
   * @param callable|null $levelFinish
   *   Of the form ($context) : bool, this is called after there are no more
   *   siblings left at a given level. $context is the context of the current
   *   level, and the return value indicates whether iteration should be
   *   continued (TRUE to continue, FALSE to halt). If NULL is passed for this
   *   parameter, the function ($c) => TRUE is used.
   * @param mixed $initialContext
   *   The context information to be stored at the root level.
   *
   * @return bool
   *   Returns FALSE if $operation or $levelFinish returned FALSE at some point;
   *   otherwise returns TRUE.
   */
  public static function walkRecursiveIterator(\RecursiveIterator $iterator, callable $operation, ?callable $drillDown = NULL, ?callable $levelFinish = NULL, $initialContext = NULL) : bool {
    $drillDown ??= function($k, $v, &$c) {
      $c = NULL;
      return TRUE;
    };
    $levelFinish ??= fn() => TRUE;

    // Prepare the iterator.
    $iterator->rewind();
    if (!$iterator->valid()) {
      return TRUE;
    }

    // Stores information associated with each parent level. This is a
    // collection of two-element arrays, the first element of which is a
    // ?\RecursiveIterator object indicating the parent \RecursiveIterator for
    // this level (NULL indicates no parent), and the second is the user
    // context information.
    $parentLevels = new Stack();

    // Keep track of the current iterator.
    $currentIterator = $iterator;
    // Keep track of the parent iterator and the current context. These are set
    // *when we move up or down a level*.
    /** @var \RecursiveIterator|null */
    $parentIterator = NULL;
    $currentContext = $initialContext;
    // Loop until we run out of elements.
    do {
      // The key and value are set at the beginning of the loop, not when we
      // move up or down a level.
      $key = $currentIterator->key();
      $value = $currentIterator->current();

      if (!$operation($key, $value, $currentContext)) {
        return FALSE;
      }

      // Move. Try the following moves, in this order:
      // 1) Move down a level, to the first child element, if possible.
      // 2) Otherwise, move to the next sibling of this element.
      // 3) Otherwise, move to the next sibling of the closest (grand)parent
      // that has a next sibling.

      // Move 1:
      if (($childIterator = static::prepareChildIterator($currentIterator)) !== NULL) {
        // Decide whether or not to move to the lower level, and set the context
        // for that level.
        $lowerLevelContext = $currentContext;
        if ($drillDown($key, $value, $lowerLevelContext)) {
          // Push the current level onto $parentLevels, and create and store the
          // current level information.
          $parentLevels->push([$parentIterator, $currentContext]);
          $parentIterator = $currentIterator;
          $currentContext = $lowerLevelContext;
          // Move to the child iterator.
          /** @var \RecursiveIterator */
          $currentIterator = $childIterator;
          continue;
        }
      }

      // Move 2: Try to move the iterator forward.
      $currentIterator->next();

      // Move 3. Work our way back up the tree, if necessary.
      while (!$currentIterator->valid() && $parentIterator !== NULL) {
        // Since we're done one level, call the level finish function.
        $levelFinish($currentContext);

        // Try to move up a level.
        /** @var \RecursiveIterator */
        $currentIterator = $parentIterator;
        list($parentIterator, $currentContext) = $parentLevels->pop();
        // Try to move to the next sibling.
        $currentIterator->next();
      }
    } while ($currentIterator->valid());

    return $levelFinish($initialContext);
  }

  /**
   * Resets and returns child iterator at current position of parent iterator.
   *
   * @param \RecursiveIterator $parent
   *   Parent iterator.
   *
   * @return \RecursiveIterator|null
   *   Child iterator, or NULL if the iterator had no child iterator, or the
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
