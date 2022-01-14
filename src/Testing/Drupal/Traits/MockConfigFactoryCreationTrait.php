<?php

declare(strict_types = 1);

namespace Ranine\Testing\Drupal\Traits;

use Drupal\Core\Config\ConfigFactoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Ranine\Testing\Traits\MockObjectCreationTrait;

/**
 * For mocking ConfigFactoryInterface objects.
 *
 * This trait is only for use in test classes.
 */
trait MockConfigFactoryCreationTrait {

  use MockObjectCreationTrait;

  /**
   * Creates and returns a a mock configuration factory.
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
   * @throws \LogicException
   *   Thrown if current object is not a \PHPUnit\Framework\TestCase object.
   */
  private function getMockConfigFactory(string $configObjectName, array $settings) : MockObject&ConfigFactoryInterface {
    if (!($this instanceof TestCase)) {
      throw new \LogicException('The object this method is called upon must be a \\PHPUnit\\Framework\\TestCase instance.');
    }

    // Mock a configuration object and factory.
    /** @var \PHPUnit\Framework\MockObject\MockObject|\Drupal\Core\Config\ImmutableConfig */
    $mockConfiguration = $this->createMockNoAutoMethodConfig('\\Drupal\\Core\\Config\\ImmutableConfig');
    $mockConfiguration->method('get')->willReturnCallback(function ($setting) use ($settings) {
      if (array_key_exists($setting, $settings)) {
        return $settings[$setting];
      }
      else {
        throw new \InvalidArgumentException('Unexpected config setting requested.');
      }
    });
    /** @var \PHPUnit\Framework\MockObject\MockObject|\Drupal\Core\Config\ConfigFactoryInterface */
    $mockConfigFactory = $this->createMockNoAutoMethodConfig('\\Drupal\\Core\\Config\\ConfigFactoryInterface');
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
