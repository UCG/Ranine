<?php

declare(strict_types = 1);

namespace Ranine\Validation;

use Ranine\Exception\ExtraElementsArraySchemaException;
use Ranine\Exception\InvalidTypeArraySchemaException;
use Ranine\Exception\MissingElementArraySchemaException;
use Ranine\Helper\IterationHelpers;
use RecursiveIterator;

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

    // Create a recursive iterator for iterating through the validation tree.
    $iterator = new class($this->rules) implements RecursiveIterator {
      private array $arr;
      private $key;
      private $value;

      public function __construct(array $arr) {
        $this->arr = $arr;
        $this->key = key($arr);
        $this->value = current($arr);
      }

      public function current() {
        return $this->value;
      }

      public function getChildren() {
        return $this->valid() ? new self($this->value->getChildren()) : [];
      }

      public function hasChildren() : bool {
        return ($this->valid() && $this->value->getChildren() !== []) ? TRUE : FALSE;
      }

      public function key() {
        return $this->key;
      }

      public function next() : void {
        $this->value = next($this->arr);
        $this->key = key($this->arr);
      }

      public function rewind() : void {
        $this->value = reset($this->arr);
        $this->key = key($this->arr);
      }

      public function valid() : bool {
        return $this->key === NULL ? FALSE : TRUE;
      }
    };


    // Perform top-level validation (no sub-tree analysis) for the root level.
    static::validateTopLevel($this->rules, $data);
    // Validate the descendents.
    IterationHelpers::walkRecursiveIterator($iterator, function ($key, ArraySchemaRule $value, array $context) : bool {
      if ($value->shouldValidateChildren()) {
        static::validateTopLevel($value->getChildren(), $context[$key]);
      }
      return TRUE;
    },
    fn($k, $v, $c) => $c[$k],
    $data);
  }

  /**
   * Validates the top-level (no drilling) down of $data against $rules.
   *
   * @param \Ranine\Validation\ArraySchemaRule[] $rules
   *   Rules.
   * @param array $data
   *   Data.
   * 
   * @throws \Ranine\Exception\InvalidArraySchemaException
   *   Thrown if the schema of the root level of $data is invalid.
   */
  private static function validateTopLevel(array $rules, array $data) : void {
    $numDataElements = count($data);
    // Every data element must have a corresponding rule, so the number of data
    // elements cannot be greater than the number of rules.
    if ($numDataElements > count($rules)) {
      throw new ExtraElementsArraySchemaException();
    }

    $maxElementsInData = 0;
    foreach ($rules as $key => $rule) {
      $keyExistsInData = array_key_exists($key, $data);

      // Required elements must exist.
      if ($rule->isElementRequired() && !array_key_exists($key, $data)) {
        throw new MissingElementArraySchemaException();
      }
      if ($keyExistsInData) {
        // Otherwise, perform validation of the element.
        $element = $data[$key];
        $rule->validate($element);
        if ($rule->shouldValidateChildren() && !is_array($element)) {
          throw new InvalidTypeArraySchemaException('Array key should be an array and it is not.');
        }
        $maxElementsInData++;
      }
    }
    if ($numDataElements > $maxElementsInData) {
      throw new ExtraElementsArraySchemaException();
    }
  }

}
