<?php

declare(strict_types = 1);

namespace Ranine\Testing\Traits;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Promise\Promise;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\RequestOptions;
use PHPUnit\Framework\TestCase;

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
   * @param callable|null $preProcessing
   *   Pre-processor for the requestAsync() method, of the form
   *   (string $method, string $uri, array $options) : void (the parameters are
   *   those passed to requestAsync()). Any processing necessary before the
   *   response production callback is executed here. Any exceptions thrown from
   *   this callback will be thrown again in the parent requestAsync() method.
   *   Passing 'NULL' for this callback will result in an empty pre-processing
   *   method being used.
   * @param callable|null $responseProduction
   *   Of the form
   *   (\Psr\Http\Message\RequestInterface $request) : \Psr\Http\Message\ResponseInterface.
   *   This produces the HTTP response object from the request. Any default
   *   headers will be added automatically, replacing any headers of the same
   *   name. Exceptions thrown from this method will force the return promise to
   *   be rejected, and will be associated with that rejection. Passing 'NULL'
   *   for this callback will result in an empty 200 response (with appropriate
   *   default headers) being returned.
   *
   * @return \PHPUnit\Framework\MockObject\MockObject|\GuzzleHttp\ClientInterface
   *   Mocked Guzzle HTTP client.
   */
  private function getMockGuzzleHttpClient(?callable $preProcessing = NULL, ?callable $responseProduction = NULL) : ClientInterface {
    if (!($this instanceof TestCase)) {
      throw new \LogicException('The object this method is called upon must be a \\PHPUnit\\Framework\\TestCase instance.');
    }
    if ($preProcessing === NULL) {
      $preProcessing = function () {};
    }
    if ($responseProduction === NULL) {
      $responseProduction = fn() => new Response();
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

        // Add standard headers.
        $headers = $response->getHeaders();
        $body = (string) $response->getBody();
        if ($body !== '') {
          $headers['Content-Length'] = [strlen($body)];
        }
        elseif (array_key_exists('Content-Length', $headers)) {
          unset($headers['Content-Length']);
        }
        $date = gmdate('D, d M Y H:i:s') . ' GMT';
        $headers['Date'] = [$date];
        $headers['Server'] = ['Server'];

        $reasonPhrase = $response->getReasonPhrase();
        $promise->resolve(new Response($response->getStatusCode(), $headers, $body, $response->getProtocolVersion(), $reasonPhrase === '' ? NULL : $reasonPhrase));
      }
      catch (\Exception $e) {
        $promise->reject($e);
      }

      return $promise;
    });

    return $mockClient;
  }

}
