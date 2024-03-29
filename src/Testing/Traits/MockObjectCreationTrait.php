<?php

declare(strict_types = 1);

namespace Ranine\Testing\Traits;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * For mocking objects.
 *
 * This trait is only for use in test classes.
 */
trait MockObjectCreationTrait {

  /**
   * Returns a mock object which will fail on calling an unconfigured method.
   *
   * @template T of object
   *
   * @param string $type
   *   Fully qualified name of class or interface we are mocking.
   *
   * @phpstan-param class-string<T> $type
   *
   * @return \PHPUnit\Framework\MockObject\MockObject&T
   *
   * @throws \Exception
   *   Thrown under circumstances found in PHPUnit documentation for
   *   \PHPUnit\Framework\MockObject\MockBuilder::getMock().
   * @throws \LogicException
   *   Thrown if current object is not a \PHPUnit\Framework\TestCase object.
   */
  protected function createMockNoAutoMethodConfig(string $type) : MockObject {
    if (!($this instanceof TestCase)) {
      throw new \LogicException('The object this method is called upon must be a \\PHPUnit\\Framework\\TestCase instance.');
    }

    $mock = $this->getMockBuilder($type)
      ->disableOriginalConstructor()
      ->disableOriginalClone()
      ->disableArgumentCloning()
      ->disallowMockingUnknownTypes()
      ->disableAutoReturnValueGeneration()
      ->getMock();
    assert($mock instanceof $type);
    return $mock;
  }

}
