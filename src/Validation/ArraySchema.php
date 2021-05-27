<?php

declare(strict_types = 1);

namespace Ranine\Validation;

use Ranine\Collection\StackOfArrays;
use Ranine\Exception\ExtraElementsArraySchemaException;
use Ranine\Exception\InvalidTypeArraySchemaException;
use Ranine\Exception\MissingElementArraySchemaException;

/**
 * Represents a schema (specification) for an array.
 */
class ArraySchema {

  /**
   * Schema rules.
   *
   * Each value in the array is a validation rule, and the corresponding key is
   * the array key corresponding to that validation rule.
   *
   * @var \Ranine\Validation\ArraySchemaRule[]
   */
  private array $rules = [];

  /**
   * Creates a new \Ranine\Validation\ArraySchema object.
   *
   * @param iterable $rules
   *   The rules of the schema. Each value must be a validation rule (a
   *   \Ranine\Validation\ArraySchemaRule object), and the corresponding
   *   key is the array key for the rule.
   *
   * @throws \InvalidArgumentException
   *   Thrown if $rules doesn't meet the requirements given above.
   */
  public function __construct(iterable $rules) {
    foreach ($rules as $key => $rule) {
      if (!($rule instanceof ArraySchemaRule)) {
        throw new \InvalidArgumentException('$rules contains an invalid value.');
      }
      $this->rules[$key] = $rule;
    }
  }

  /**
   * Validates the given data against this schema, throwing on failure.
   *
   * @param array $data
   *   Data to validate.
   *
   * @throws \Ranine\Exception\InvalidArraySchemaException
   *   Thrown if $data has an invalid schema.
   */
  public function validate(array $data) : void {
    // Performing the validation recursively would be simplest, but instead we
    // "unwind" the tree manually. This avoids overflowing the stack if $data is
    // very deep.
    
    // Perform top-level validation (no sub-tree analysis) for the root level.
    static::validateTopLevel($this->rules, $data);
    // Validate the descendents.
    foreach ($data as $key => $element) {
      $rule = $this->rules[$key];
      if ($rule->shouldValidateChildren()) {
        assert(is_array($element));
        static::validateSubElements($element, $rule->getChildren(), $key);
      }
    }
  }

  /**
   * Validates the sub-elements array $elements.
   *
   * @param array $elements
   *   Elements to validate.
   * @param \Ranine\Validation\ArraySchemaRule[] $rules
   *   Keyed rules used to perform validation.
   * @param string|int $parentKey
   *   The key of $elements.
   */
  private static function validateSubElements(array $elements, array $rules, $parentKey) : void {
    // Store the current set of elements we are working on.
    $currentElements = $elements;
    // Store the current set of rules we are working on.
    $currentRules = $rules;
    // Initialize a "stack" of parent elements that we'll use as we drill down.
    // Each element is an array whose internal pointer is at the position of the
    // parent element represented. Think of $parents as a stack of pointers.
    $parents = new StackOfArrays();
    do {
      // Create a string representation of the parent element.
      $currentParentRepresentation = (string) $parentKey;
      foreach ($parents as $parent) {
        $currentParentRepresentation .= ' -> ' . (string) key($parent);
      }

      // Performs top-level validation on the current elements.
      static::validateTopLevel($currentRules, $currentElements, $currentParentRepresentation);

      // Keep track of whether we moved to the next element yet.
      $didMove = FALSE;

      // Drill down a level if possible.
      $lastKey = array_key_last($currentElements);
      // Check to ensure the array isn't empty.
      if ($lastKey !== NULL) {
        // Grab the first element of $currentElements.
        $element = reset($currentElements);
        // Loop through $currentElements to find an array with validatable
        // children.
        do {
          // Get the key and rule associated with the current element.
          $key = key($currentElements);
          $rule = $currentRules[$key];

          if ($rule->shouldValidateChildren()) {
            assert(is_array($element));
            // Drill down.
            // Push the $currentElements array, with its internal pointer, onto
            // the stack of "parents."
            $parents->push($currentElements);
            // Set up the new set of elements and rules.
            $currentElements = $element;
            $currentRules = $rule->getChildren();

            $didMove = TRUE;
            break;
          }
          // Move to the next element.
          $element = next($currentElements);
        } while ($key !== $lastKey);
      }

      // If we can't drill down, move to the next sibling of the parent of
      // $currentElements. If there is no next sibling, move to the next sibling
      // of the grandparent, etc.
      if (!$didMove) {
        while (!$parents->isEmpty() && !$didMove) {
          // Pop off the top parent.
          $parent = $parents->pop();
          // Try to move to a sibling. Loop through the siblings until we find
          // one with validatable children.
          $lastKey = array_key_last($parent);
          assert($lastKey !== NULL);
          $key = key($parent);
          while ($key !== $lastKey && !$didMove) {
            // Move to the next sibling and grab its key and rule.
            $element = next($parent);
            $key = key($parent);
            $rule = $rules[$key];

            if ($rule->shouldValidateChildren()) {
              // We found a valid sibling. Push on what will be
              // the new parent (or, more properly, the array of
              // parent/aunts/uncles with its internal pointer set to the new
              // parent).
              $parents->push($parent);
              // Set the current element to the set of children pointed to by
              // the $parent pointer, and grab the new set of rules.
              $currentElements = $element;
              $currentRules = $rule->getChildren();
              $didMove = TRUE;
            }
          }
        }
      }
    } while ($didMove);
  }

  /**
   * Validates the top-level (no drilling) down of $data against $rules.
   *
   * @param \Ranine\Validation\ArraySchemaRule[] $rules
   *   Rules.
   * @param array $data
   *   Data.
   * @param string $parentRepresentation
   *   String representation of the parent key (e.g. "key_1 -> key_2"), or
   *   an empty string if no such representation
   * 
   * @throws \Ranine\Exception\InvalidArraySchemaException
   *   Thrown if the schema of the root level of $data is invalid.
   */
  private static function validateTopLevel(array $rules, array $data, string $parentRepresentation = '') : void {
    $numDataElements = count($data);
    // Every data element must have a corresponding rule, so the number of data
    // elements cannot be greater than the number of rules.
    if ($numDataElements > count($rules)) {
      throw new ExtraElementsArraySchemaException('The data contains extra elements at ' . ($parentRepresentation === '' ? 'the root level.' : '"' . $parentRepresentation . '".'));
    }

    $maxElementsInData = 0;
    foreach ($rules as $key => $rule) {
      $keyExistsInData = array_key_exists($key, $data);

      // Required elements must exist.
      if ($rule->isElementRequired() && !array_key_exists($key, $data)) {
        $qualifiedArrayKeyName = $parentRepresentation === '' ? $key : $parentRepresentation . ' -> ' . $key;
        throw new MissingElementArraySchemaException('Array key "' . $qualifiedArrayKeyName. '" is missing from data.');
      }
      if ($keyExistsInData) {
        // Otherwise, perform validation of the element.
        $element = $data[$key];
        $rule->validate($element);
        if ($rule->shouldValidateChildren() && !is_array($element)) {
          $qualifiedArrayKeyName = $parentRepresentation === '' ? $key : $parentRepresentation . ' -> ' . $key;
          throw new InvalidTypeArraySchemaException('Array key "' . $qualifiedArrayKeyName . '" is not an array.');
        }
        $maxElementsInData++;
      }
    }
    if ($numDataElements > $maxElementsInData) {
      throw new ExtraElementsArraySchemaException('The data contains extra elements at ' . ($parentRepresentation === '' ? 'the root level.' : '"' . $parentRepresentation . '".'));
    }
  }

}
