<?php

declare(strict_types = 1);

namespace Ranine\Exception;

use Ranine\Helper\StringHelpers;

/**
 * Used to consolidate multiple exceptions into one object.
 */
class AggregateException extends InvalidArraySchemaException {

  /**
   * Exceptions that are the proximate cause of this exception.
   *
   * @var \Exception[]
   */
  private array $innerExceptions;

  /**
   * Creates a new AggregateException object.
   *
   * @param string $message
   *   Message pertaining to exception.
   * @param int $code
   *   Exception code.
   * @param \Exception[] $innerExceptions
   *   Exceptions that are the proximate cause of this exception, and which
   *   should be consolidated herein.
   * @param \Throwable|null $previous
   *   Previous exception/error which triggered this exception. Can be NULL to
   *   indicate no such error.
   */
  public function __construct(string $message, int $code = 0, array $innerExceptions = [], ?\Throwable $previous = NULL) {
    $this->innerExceptions = $innerExceptions;
    parent::__construct($message, $code, $previous);
  }

  /**
   * Gets the exceptions which are the proximate cause of this exception.
   *
   * @return \Exception[]
   */
  public function getInnerExceptions() : array {
    return $this->innerExceptions;
  }

}
