<?php

declare (strict_types = 1);

namespace Ranine\Helper\Drupal;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\DatabaseExceptionWrapper;
use Ranine\Helper\ThrowHelpers;

/**
 * Contains Drupal-related helper methods dealing with databases stuff.
 */
final class DatabaseHelpers {

  /**
   * MySQL deadlock error code.
   */
  private const MYSQL_ERROR_CODE_DEADLOCK_DETECTED = '1213';

  /**
   * MySQL lock acquire timeout error code.
   */
  private const MYSQL_ERROR_LOCK_TIMEOUT = '1205';

  /**
   * Empty private constructor to ensure no one instantiates this class.
   */
  private function __construct() {
  }

  /**
   * Executes a transaction, retrying as requested to and after a deadlock.
   *
   * Transaction re-execution takes place automatically after a deadlock, but
   * the caller can force re-execution (if the maximum repeat count hasn't been
   * reached) manually by returning FALSE from $transactionExecution().
   *
   * @param callable() : bool $transactionExecution
   *   Function executing transaction statements. Should return TRUE if
   *   transaction was executed successfully, and FALSE if transaction failed
   *   (for a non-deadlock, non-lock timeout reason) and should be re-tried if 
   *   possible. Otherwise, this function should throw an exception.
   * @param \Drupal\Core\Database\Connection $databaseConnection
   *   Database connection with which to execute the transaction.
   * @param int $numberOfRetryAttempts
   *   Maximum number of times to retry the transaction.
   * @phpstan-param int<0, max> $numberOfRetryAttempts
   *
   * @return bool
   *   Returns TRUE if the transaction was completed successfully; returns FALSE
   *   if $transactionExecution() returned FALSE on the last retry attempt.
   *
   * @throws \Drupal\Core\Database\DatabaseExceptionWrapper
   *   Thrown if a database error occurs.
   * @throws \InvalidArgumentException
   *   Thrown if $numberOfRetryAttempts is negative.
   */
  public static function executeDatabaseTransaction(callable $transactionExecution, Connection $databaseConnection, int $numberOfRetryAttempts = 10) : bool {
    ThrowHelpers::throwIfLessThanZero($numberOfRetryAttempts, 'numberOfRetryAttempts');

    // Start the transaction, and repeat for up to $numberOfRetryAttempts times
    // if the transaction deadlocks, a lock times out, or $transactionExecution
    // returns FALSE.
    for ($i = 0; $i <= $numberOfRetryAttempts; $i++) {
      $wasLockError = FALSE;
      // Start the transaction.
      $transaction = $databaseConnection->startTransaction();
      try {
        // Execute transaction code.
        if ($transactionExecution()) {
          // Transaction execution was successful -- manually destruct the
          // transaction to pop it or commit it. Normally this is done by
          // unsetting the object or allowing it to go out of scope to invoke
          // the destructor, but, if XDebug is enabled, this might not work
          // properly: the destructor might not be called, which can lead to
          // out-of-order destruction during shutdown later. See
          // https://bugs.xdebug.org/view.php?id=2222, https://www.drupal.org/project/drupal/issues/3405976
          // and https://www.drupal.org/project/drupal/issues/3406985, and
          // https://www.drupal.org/project/drupal/issues/3398767. Once that
          // last issue is resolved, probably in Drupal 11.3, we can use
          // $transaction->commitOrRelease() here and remove this hack.
          //
          // The hack does raise a
          // potential issue: will there be problems when the destructor is
          // called as the object is destroyed. Fortunately, the destructor
          // seems to be idempotent, but this is certainly not optimal...
          $transaction->__destruct();
          // Return to caller -- transaction is finished.
          return TRUE;
        }
        else {
          // Transaction execution failed. Rollback.
          /** @phpstan-ignore-next-line */
          if (isset($transaction)) {
            $transaction->rollBack();
          }
        }
      }
      catch (DatabaseExceptionWrapper $e) {
        // Rollback transaction (should happen automatically for a detected
        // deadlock error code, but we do it here no matter what just to be
        // safe).
        /** @phpstan-ignore-next-line */
        if (isset($transaction)) {
          $transaction->rollBack();
        }

        // Check the MySQL error code -- if it corresponds to a detected
        // deadlock state, or a lock acquire timeout, let the transaction be
        // retried, as both those codes could be caused by deadlocks. Otherwise,
        // rethrow the exception.
        $mysqlErrorCode = self::getMySqlErrorCode($e);
        if ($mysqlErrorCode === self::MYSQL_ERROR_CODE_DEADLOCK_DETECTED || $mysqlErrorCode === self::MYSQL_ERROR_LOCK_TIMEOUT) {
          $wasLockError = TRUE;
        }
        else {
          throw $e;
        }
      }
      catch (\Throwable $e) {
        // Rollback and rethrow.
        /** @phpstan-ignore-next-line */
        if (isset($transaction)) {
          $transaction->rollBack();
        }
        throw $e;
      }
    }

    // If we got this far, we must have retried $numberOfRetryAttempts times
    // and still encountered a problem. We'll just fail, in this case...

    // Throw the locking exception, if applicable.
    if ($wasLockError) {
      assert(isset($e));
      throw $e;
    }

    return FALSE;
  }

  /**
   * Gets MySQL error code from the given exception, or NULL if code not found.
   */
  public static function getMySqlErrorCode(DatabaseExceptionWrapper $exception) : ?string {
    // Grab the inner PDO exception.
    $pdoException = $exception->getPrevious();
    assert(isset($pdoException) && is_object($pdoException) && $pdoException instanceof \PDOException);
    return $pdoException->errorInfo ? (string) $pdoException->errorInfo[1] : NULL;
  }

}
