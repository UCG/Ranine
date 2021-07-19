<?php

declare(strict_types = 1);

namespace Ranine\Drupal\Database\Traits;

use Drupal\Core\Database\Connection;
use Ranine\Helper\ThrowHelpers;

/**
 * Adds core database functionality to a class responsible for managing a table.
 */
trait TableManagerTrait {

  /**
   * Database connection.
   */
  private Connection $databaseConnection;

  /**
   * Name of the underlying database table.
   */
  private string $tableName;

  /**
   * Gets the table name for this object wrapped (in {}) and escaped.
   */
  private function getCurrentWrappedEscapedTableName() : string {
    return $this->getWrappedEscapedTableName($this->tableName);
  }

  /**
   * Gets a wrapped (in {}) and escaped name for a given raw table name.
   *
   * @param string $rawTableName
   *   Raw (un-escaped and non-wrapped) table name.
   */
  private function getWrappedEscapedTableName(string $rawTableName) : string {
    return '{' . $this->databaseConnection->escapeTable($rawTableName) . '}';
  }

  /**
   * Initializes the $databaseConnection and $tableName properties.
   *
   * @param \Drupal\Core\Database\Connection $databaseConnection
   *   Database connection.
   * @param string $tableName
   *   Table name.
   * @param string $tableNameVariableNameOnException
   *   Variable name to use when throwing an exception when $tableName is
   *   invalid.
   *
   * @throws \InvalidArgumentException
   *   Thrown if $tableName is empty.
   */
  private function initializeConnectionAndTableName(Connection $databaseConnection, string $tableName, string $tableNameVariableNameOnException) {
    ThrowHelpers::throwIfEmptyString($tableName, $tableNameVariableNameOnException);

    $this->databaseConnection = $databaseConnection;
    $this->tableName = $tableName;
  }

}
