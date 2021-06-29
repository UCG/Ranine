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
   * reached) manually by returning FALSE from $transactionExecution.
   *
   * @param callable $transactionExecution
   *   Function executing transaction statements, of form () : bool. Should
   *   return TRUE if transaction was executed successfully, and FALSE if
   *   transaction failed (for a non-deadlock, non-lock timeout reason) and
   *   should be re-tried if possible. Otherwise, this function should throw an
   *   exception.
   * @param \Drupal\Core\Database\Connection $databaseConnection
   *   Database connection with which to execute the transaction.
   * @param int $numberOfRetryAttempts
   *   Maximum number of times to retry the transaction.
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
    $wasLockError = FALSE;
    for ($i = 0; $i <= $numberOfRetryAttempts; $i++) {
      $wasLockError = FALSE;
      // Start the transaction.
      $transaction = $databaseConnection->startTransaction();
      try {
        // Execute transaction code.
        if ($transactionExecution()) {
          // Transaction execution was successful -- unset transaction
          // explicitly to force commit.
          unset($transaction);
          // Return to caller -- transaction is finished.
          return TRUE;
        }
        else {
          // Transaction execution failed. Rollback.
          if (isset($transaction)) {
            $transaction->rollBack();
          }
        }
      }
      catch (DatabaseExceptionWrapper $e) {
        // Rollback transaction (should happen automatically for a detected
        // deadlock error code, but we do it here no matter what just to be
        // safe).
        if (isset($transaction)) {
          $transaction->rollBack();
        }

        // Check the MySQL error code -- if it corresponds to a detected
        // deadlock state, or a lock acquire timeout, let the transaction be
        // retried, as both those codes could be caused by deadlocks. Otherwise,
        // rethrow the exception.
        $mysqlErrorCode = static::getMySqlErrorCode($e);
        if ($mysqlErrorCode === static::MYSQL_ERROR_CODE_DEADLOCK_DETECTED || $mysqlErrorCode === static::MYSQL_ERROR_LOCK_TIMEOUT) {
          $wasLockError = TRUE;
        }
        else {
          throw $e;
        }
      }
      catch (\Throwable $e) {
        // Rollback and rethrow.
        if (isset($transaction)) {
          $transaction->rollBack();
        }
        throw $e;
      }
    }

    // If we got this far, we must have retried $numberOfRetryAttempts times
    // and still encountered a problem. We'll just fail, in this case...
    // Also, roll back the transaction.
    if (isset($transaction)) {
      $transaction->rollBack();
    }

    // Throw the locking exception, if applicable.
    if ($wasLockError) {
      assert(isset($e));
      throw $e;
    }

    return FALSE;
  }

  /**
   * Gets the MySQL error code from the given exception.
   */
  public static function getMySqlErrorCode(DatabaseExceptionWrapper $exception) : string {
    // Grab the inner PDO exception.
    $pdoException = $exception->getPrevious();
    assert(isset($pdoException) && is_object($pdoException) && $pdoException instanceof \PDOException);
    return (string) $pdoException->errorInfo[1];
  }

}
