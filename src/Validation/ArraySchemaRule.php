<?php

declare(strict_types = 1);

namespace Ranine\Validation;

use Ranine\Exception\InvalidArraySchemaException;

/**
 * Represents a rule for array schema validation.
 */
class ArraySchemaRule {

  /**
   * Child validation rules, or NULL if children aren't to be validated.
   *
   * @var \Ranine\Validation\ArraySchemaRule[]|null
   */
  private ?array $children;

  /**
   * Whether the element corresponding to this rule is required.
   */
  private bool $isElementRequired;

  /**
   * The callable representing the actual validation.
   *
   * @var callable
   */
  private $validation;

  /**
   * Creates a new array schema validation rule.
   *
   * @param callable $validation
   *   Validation rule. It should take one parameter. If validation fails, it
   *   should return a
   *   \Ranine\Exception\InvalidArraySchemaException
   *   exception to be thrown. If validation succeeds, it should return NULL.
   * @param bool $isElementRequired
   *   Whether the element corresponding to this rule is required as part of the
   *   schema definition.
   * @param \Ranine\Validation\ArraySchemaRule[]|null $children
   *   Collection of schema rule children, each rule keyed on the corresponding
   *   key in an array under validation. NULL indicates that the children are
   *   not to be validated.
   *
   * @throws \InvalidArgumentException
   *   Thrown if an element of $children is not of type
   *   \Ranine\Validation\ArraySchemaRule.
   */
  public function __construct(callable $validation, bool $isElementRequired = TRUE, ?array $children = NULL) {
    if ($children !== NULL) {
      foreach ($children as $rule) {
        if (!($rule instanceof ArraySchemaRule)) {
          throw new \InvalidArgumentException('$children contained an invalid value type.');
        }
      }
    }

    $this->children = $children;
    $this->isElementRequired = $isElementRequired;
    $this->validation = $validation;
  }

  /**
   * Gets all the child validation rules.
   *
   * @return array
   *   Child rules.
   */
  public function getChildren() : array {
    return $this->children ?? [];
  }

  /**
   * Determines whether element corresponding to this rule is required,
   *
   * @return bool
   *   Returns TRUE if element is required; else returns FALSE.
   */
  public function isElementRequired() : bool {
    return $this->isElementRequired;
  }

  /**
   * Checks if children corresponding to this rule should be validated.
   *
   * @return bool
   *   Returns TRUE if children should be validated; else returns FALSE.
   */
  public function shouldValidateChildren() : bool {
    return $this->children === NULL ? FALSE : TRUE;
  }

  /**
   * Validates the given element.
   *
   * @param mixed $element
   *   Element to validate.
   *
   * @throws \Ranine\Exception\InvalidArraySchemaException
   *   Thrown if validation fails.
   */
  public function validate($element) : void {
    $exception = ($this->validation)($element);
    if ($exception !== NULL) {
      if (!($exception instanceof InvalidArraySchemaException)) {
        throw new \LogicException('The validation handler failed to return a valid exception type.');
      }
      throw $exception;
    }
  }

}
