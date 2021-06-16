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
    // Ensure the top-level array doesn't clearly have too many items (every
    // data element must have a corresponding rule, so the number of data
    // elements cannot be greater than the number of rules).
    if (count($data) > count($this->rules)) {
      throw new ExtraElementsArraySchemaException();
    }

    // Performing the validation recursively would be simplest, but instead we
    // "unwind" the tree manually. This avoids overflowing the stack if $data is
    // very deep.

    // Create a recursive iterator for iterating through the validation tree.
    $iterator = new class($this->rules) implements RecursiveIterator {
      /** @var string|int */
      private $key;
      /** @var \Ranine\Validation\ArraySchemaRule[] */
      private array $rules;
      private ArraySchemaRule $value;

      /**
       * @param \Ranine\Validation\ArraySchemaRule[] $rules
       */
      public function __construct(array $rules) {
        $this->rules = $rules;
        $this->key = key($rules);
        $this->value = current($rules);
      }

      public function current() : ArraySchemaRule {
        return $this->value;
      }

      /**
       * @return self
       */
      public function getChildren() {
        return new self($this->value->getChildren());
      }

      public function hasChildren() : bool {
        return ($this->value->shouldValidateChildren() && count($this->value->getChildren()) > 0) ? TRUE : FALSE;
      }

      /**
       * @return string|int
       */
      public function key() {
        return $this->key;
      }

      public function next() : void {
        $this->value = next($this->rules);
        $this->key = key($this->rules);
      }

      public function rewind() : void {
        $this->value = reset($this->rules);
        $this->key = key($this->rules);
      }

      public function valid() : bool {
        return $this->key === NULL ? FALSE : TRUE;
      }
    };

    // Validate the descendents. As a context for each level, store the parent
    // data array and current number of data elements found associated with a
    // rule.
    $context = new class($this->data, 0) {
      private array $data;
      private int $numRuleAssociatedElements;

      public function __construct(array $data) {
        $this->data = $data;
      }

      public function getData() : array {
        return $this->data;
      }

      public function getNumRuleAssociatedElements() : int {
        return $this->numRuleAssociatedElements;
      }

      /**
       * @param string|int $key
       *
       * @return self
       */
      public function getSubContext($key) {
        return new self($this->data[$key]);
      }

      /**
       * @return self
       */
      public function incrementNumRuleAssociatedElements() {
        $this->numRuleAssociatedElements++;
        return $this;
      }

    };

    // @todo: Finish.
    IterationHelpers::walkRecursiveIterator($iterator, function($key, array $value) {
      /** @var \Ranine\Validation\ArraySchemaRule */
      $rule = $value[0];
      /** @var array */
      $data = $value[1][$key];
      static::validateTopLevel($rule->getChildren(), $data);
      return NULL;
    }, fn($k, $v, $c) => $c->getSubContext($k), $context);
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
