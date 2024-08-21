<?php

declare(strict_types = 1);

namespace Ranine\Validation;

use Ranine\Exception\ExtraElementsArraySchemaException;
use Ranine\Exception\InvalidOperationException;
use Ranine\Exception\InvalidTypeArraySchemaException;
use Ranine\Exception\MissingElementArraySchemaException;
use Ranine\Helper\IterationHelpers;

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
  private readonly array $rules;

  /**
   * Creates a new \Ranine\Validation\ArraySchema object.
   *
   * @param \Ranine\Validation\ArraySchemaRule[] $rules
   *   The rules of the schema. Each value must be a validation rule (a
   *   \Ranine\Validation\ArraySchemaRule object), and the corresponding
   *   key is the array key for the rule.
   *
   * @throws \InvalidArgumentException
   *   Thrown if $rules doesn't meet the requirements given above.
   */
  public function __construct(array $rules) {
    foreach ($rules as $k => $rule) {
      if (!($rule instanceof ArraySchemaRule)) {
        throw new \InvalidArgumentException('$rules contains an invalid value.');
      }
    }

    $this->rules = $rules;
  }

  /**
   * Validates the given data against this schema, throwing on failure.
   *
   * @param array $data
   *   Data to validate.
   *
   * @throws \Ranine\Exception\InvalidArraySchemaException
   *   Thrown if $data has an invalid schema.
   *
   * @phpstan-pure
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
    $iterator = new
      /**
       * @implements \RecursiveIterator<string|int, \Ranine\Validation\ArraySchemaRule>
       */
      class($this->rules) implements \RecursiveIterator {
        private string|int|null $key;
        /** @var \Ranine\Validation\ArraySchemaRule[] */
        private array $rules;
        private ArraySchemaRule|bool $value;

        /**
         * @param \Ranine\Validation\ArraySchemaRule[] $rules
         */
        public function __construct(array $rules) {
          $this->rules = $rules;
          $this->key = key($rules);
          $this->value = current($rules);
        }

        public function current() : ArraySchemaRule {
          if (!$this->valid()) {
            throw new InvalidOperationException();
          }
          return $this->value;
        }

        public function getChildren() : self {
          if (!$this->valid()) {
            throw new InvalidOperationException();
          }
          return new self($this->value->getChildren());
        }

        public function hasChildren() : bool {
          if (!$this->valid()) {
            throw new InvalidOperationException();
          }
          return ($this->value->shouldValidateChildren() && count($this->value->getChildren()) > 0) ? TRUE : FALSE;
        }

        public function key() : string|int {
          if (!$this->valid()) {
            throw new InvalidOperationException();
          }
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

        /**
         * @phpstan-assert-if-true !bool $this->value
         * @phpstan-assert-if-true !null $this->key
         */
        public function valid() : bool {
          return $this->key === NULL ? FALSE : TRUE;
        }
      };

    // Validate the descendents. As a context for each level, store the parent
    // data array and current number of data elements found associated with a
    // rule.
    $context = new class($data) {
      private array $data;
      /** @phpstan-var int<0, max> */
      private int $numRuleAssociatedElements;

      public function __construct(array $data) {
        $this->data = $data;
        $this->numRuleAssociatedElements = 0;
      }

      public function getData() : array {
        return $this->data;
      }

      public function getNumRuleAssociatedElements() : int {
        return $this->numRuleAssociatedElements;
      }

      public function getSubContext(string|int $key) : self {
        assert(is_array($this->data[$key]));
        return new self($this->data[$key]);
      }

      public function incrementNumRuleAssociatedElements() : void {
        $this->numRuleAssociatedElements++;
      }
    };

    IterationHelpers::walkRecursiveIterator($iterator, function(string|int $key, ArraySchemaRule $rule, object &$context) : bool {
      $data = $context->getData();

      $keyExistsInData = array_key_exists($key, $data);
      // Required elements must exist.
      if ($rule->isElementRequired() && !array_key_exists($key, $data)) {
        throw new MissingElementArraySchemaException();
      }
      if ($keyExistsInData) {
        // Perform validation of the element.
        $element = $data[$key];
        if ($rule->shouldValidateChildren()) {
          // If we have children we should validate, this needs to be an array
          // without too many elements.
          if (is_array($element)) {
            if (count($element) > count($rule->getChildren())) {
              throw new ExtraElementsArraySchemaException();
            }
          }
          else {
            throw new InvalidTypeArraySchemaException('Array key should be an array and it is not.');
          }
        }
        $rule->validate($element);
        $context->incrementNumRuleAssociatedElements();
      }
      return TRUE;
    }, function (string|int $key, $value, &$context, &$newContext) : bool {
      $data = $context->getData();
      if (array_key_exists($key, $data)) {
        $newContext = $context->getSubContext($key);
        return TRUE;
      }
      else {
        return FALSE;
      }
     }, function ($context) {
      // Ensure that the level doesn't have too many elements (all elements must
      // have associated rules, so it can't have more elements than it did
      // elements that were associated with rules).
      if (count($context->getData()) > $context->getNumRuleAssociatedElements()) {
        throw new ExtraElementsArraySchemaException();
      }
      return TRUE;
    }, $context);
  }

}
