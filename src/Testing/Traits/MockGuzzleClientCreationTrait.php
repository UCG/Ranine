<?php

declare(strict_types = 1);

namespace Ranine\Testing\Traits;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Promise\Promise;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\RequestOptions;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

/**
 * For mocking \GuzzleHttp\ClientInterface objects.
 *
 * This trait is only for use in test classes.
 */
trait MockGuzzleClientCreationTrait {

  /**
   * Creates a mock Guzzle HTTP client object.
   *
   * Only the requestAsync() method will be properly defined on the mock client.
   *
   * @param callable $preProcessing
   *   Pre-processor for the requestAsync() method. Any processing necessary
   *   before the response production callback is executed. Any exceptions
   *   thrown from this callback will be thrown again in the parent
   *   requestAsync() method.
   * @param callable $responseProduction
   *   Produces the HTTP response object. Any default headers will be added
   *   automatically. Exceptions thrown from this method will force the return
   *   promise to be rejected, and will be associated with that rejection.
   *
   * @return \PHPUnit\Framework\MockObject\MockObject|\GuzzleHttp\ClientInterface
   *   Mocked Guzzle HTTP client.
   */
  private function getMockGuzzleHttpClient(callable $preProcessing = function (string $method, string $uri, array $options) : void {},
    callable $responseProduction = fn(Request $request) : ResponseInterface => new Response()) : ClientInterface {
    if (!($this instanceof TestCase)) {
      throw new \LogicException('The object this method is called upon must be a \\PHPUnit\\Framework\\TestCase instance.');
    }

    /** @var \PHPUnit\Framework\MockObject\MockObject|\GuzzleHttp\ClientInterface */
    $mockClient = $this->createMock('\\GuzzleHttp\\ClientInterface');
    $mockClient->method('requestAsync')->willReturnCallback(function (string $method, string $uri, array $options) use ($preProcessing, $responseProduction) {
      $preProcessing($method, $uri, $options);
      $promise = new Promise();
      $request = new Request($method, $uri, $options[RequestOptions::HEADERS] ?? [], $options[RequestOptions::BODY] ?? NULL);
      try {
        /** @var \Psr\Http\Message\ResponseInterface */
        $response = $responseProduction($request);
      }
      catch (\Exception $e) {
        $promise->reject($e);
      }
      $promise->resolve($response);

      return $promise;
    });

    return $mockClient;
  }

}
