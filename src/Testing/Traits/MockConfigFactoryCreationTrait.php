<?php

declare(strict_types = 1);

namespace Ranine\Testing\Traits;

use Drupal\Core\Config\ConfigFactoryInterface;
use PHPUnit\Framework\TestCase;

/**
 * For mocking ConfigFactoryInterface objects.
 *
 * This trait is only for use in test classes.
 */
trait MockConfigFactoryCreationTrait {

  /**
   * Creates a mock configuration factory.
   *
   * The configuration factory will return a settings configuration object if
   * the $configObjectName configuration object is requested with the get()
   * method of the configuration factory. This configuration object will return
   * settings from the $settings array.
   *
   * @param string $configObjectName
   *   Configuration object name.
   * @param array $settings
   *   Settings array.
   *
   * @return \PHPUnit\Framework\MockObject\MockObject|\Drupal\Core\Config\ConfigFactoryInterface
   *   Mocked configuration factory
   *
   * @throws \LogicException
   *   Thrown if current object is not a \PHPUnit\Framework\TestCase object.
   */
  private function getMockConfigFactory(string $configObjectName, array $settings) : ConfigFactoryInterface {
    if (!($this instanceof TestCase)) {
      throw new \LogicException('The object this method is called upon must be a \\PHPUnit\\Framework\\TestCase instance.');
    }

    // Mock a configuration object and factory.
    /** @var \PHPUnit\Framework\MockObject\MockObject|\Drupal\Core\Config\ImmutableConfig */
    $mockConfiguration = $this->getMockBuilder('\\Drupal\\Core\\Config\\ImmutableConfig')
      ->disableOriginalConstructor()
      ->disableOriginalClone()
      ->disableArgumentCloning()
      ->disallowMockingUnknownTypes()
      ->disableAutoReturnValueGeneration()
      ->getMock();
    $mockConfiguration->method('get')->willReturnCallback(function ($setting) use ($settings) {
      if (array_key_exists($setting, $settings)) {
        return $settings[$setting];
      }
      else {
        throw new \InvalidArgumentException('Unexpected config setting requested.');
      }
    });
    /** @var \PHPUnit\Framework\MockObject\MockObject|\Drupal\Core\Config\ConfigFactoryInterface */
    $mockConfigFactory = $this->getMockBuilder('\\Drupal\\Core\\Config\\ConfigFactoryInterface')
      ->disableOriginalConstructor()
      ->disableOriginalClone()
      ->disableArgumentCloning()
      ->disallowMockingUnknownTypes()
      ->disableAutoReturnValueGeneration()
      ->getMock();
    $mockConfigFactory->method('get')->willReturnCallback(function (string $configuration) use ($mockConfiguration, $configObjectName) {
      if ($configuration === $configObjectName) {
        return $mockConfiguration;
      }
      else {
        throw new \InvalidArgumentException('Unexpected configuration requested.');
      }
    });

    return $mockConfigFactory;
  }

}
