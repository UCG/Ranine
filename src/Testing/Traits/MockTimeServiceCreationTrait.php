<?php

declare(strict_types = 1);

namespace Ranine\Testing\Traits;

use Drupal\Component\Datetime\TimeInterface;
use PHPUnit\Framework\TestCase;

/**
 * For mocking \Drupal\Component\Datetime\TimeInterface objects.
 *
 * This trait is only for use in test classes.
 */
trait MockTimeServiceCreationTrait {

  use MockObjectCreationTrait;

  /**
   * Creates a mock time service object.
   *
   * @return \PHPUnit\Framework\MockObject\MockObject|\Drupal\Component\Datetime\TimeInterface
   *   Mock time interface.
   *
   * @throws \LogicException
   *   Thrown if current object is not a \PHPUnit\Framework\TestCase object.
   */
  private function getMockTimeServiceObject() : TimeInterface {
    if (!($this instanceof TestCase)) {
      throw new \LogicException('The object this method is called upon must be a \\PHPUnit\\Framework\\TestCase instance.');
    }

    /** @var \PHPUnit\Framework\MockObject\MockObject|\Drupal\Component\Datetime\TimeInterface */
    $mockTimeObtainer = $this->createMockNoAutoMethodConfig('\\Drupal\\Component\\Datetime\\TimeInterface');
    $mockTimeObtainer->method('getCurrentMicroTime')->willReturnCallback(fn() : float => microtime(TRUE));
    $mockTimeObtainer->method('getCurrentTime')->willReturnCallback(fn() : int => time());
    $mockTimeObtainer->method('getRequestMicroTime')->willReturnCallback(fn() : float => $_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(TRUE));
    $mockTimeObtainer->method('getRequestTime')->willReturnCallback(fn() : int => $_SERVER['REQUEST_TIME'] ? (int) $_SERVER['REQUEST_TIME'] : time());

    return $mockTimeObtainer;
  }

}
