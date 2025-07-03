<?php

declare (strict_types = 1);

namespace Ranine;

use Ranine\Exception\BadCsvException;
use Ranine\Exception\IoException;
use Ranine\Helper\ThrowHelpers;

/**
 * Extracts data from CSV files.
 */
class CsvParser {

  /** @var array<int, callable(string $value) : void> */
  private array $fieldGatherers;
  /** @var resource */
  private $file;
  private int $numFields;

  /**
   * Creates a new CSV parser object.
   *
   * @param resource $file
   *   File, opened for reading and positioned just past the first (header) row.
   * @param array<int, callable(string $value) : void> $fieldGatherers
   *   Field gatherers (by column ID).
   */
  private function __construct($file, array $fieldGatherers) {
    $this->file = $file;
    $this->fieldGatherers = $fieldGatherers;
    $this->numFields = count($fieldGatherers);
  }

  public function __destruct() {
    // @todo Should this be done?
    fclose($this->file);
  }

  public function readRow() : bool {
    $row = static::getAndCheckCsvRow($this->file);
    if ($row === NULL) return FALSE;
    else {
      if (count($row) !== $this->numFields) {
        throw new BadCsvException('Invalid row in CSV data.');
      }
      foreach ($this->fieldGatherers as $column => $gatherer) {
        $gatherer((string) $row[$column]);
      }

      return TRUE;
    }
  }

  /**
   * Constructs and returns a new CSV parser from the given CSV file.
   *
   * @param string $csvFilename
   *   Name of CSV file.
   * @param array<int|string, callable(string $value) : void> $fieldGatherers
   *   Table whose keys are field names (all of which will be considered
   *   required fields) and whose values are functions taking a field value
   *   (corresponding to the named field) and doing with this value as the
   *   consumer desires. No fields are expected except those in $fieldGatherers.
   *
   * @throws \InvalidArgumentException
   *   Thrown if $csvFilename is empty.
   * @throws \InvalidArgumentException
   *   Thrown if a value in $fieldGatherers is not callable, or if
   *   $fieldGatherers is empty.
   * @throws \Ranine\Exception\IoException
   *   Thrown if the CSV file couldn't be opened.
   * @throws \Ranine\Exception\BadCsvException
   *   Thrown if the CSV file is malformed.
   */
  public static function fromFile(string $csvFilename, array $fieldGatherers) : self {
    ThrowHelpers::throwIfEmptyString($csvFilename, 'csvFilename');
    if ($fieldGatherers === []) throw new \InvalidArgumentException('$fieldGatherers is empty().');
    foreach ($fieldGatherers as $g) {
      if (!is_callable($g)) throw new \InvalidArgumentException('A field gatherer in $fieldGatherers is not callable.');
    }

    $file = fopen($csvFilename, 'r');
    if (!is_resource($file)) {
      throw new IoException('CSV file could not be opened.');
    }

    return self::fromResourceInternal($file, $fieldGatherers);
  }

  /**
   * Constructs and returns a new CSV parser from the given CSV resource.
   *
   * @param resource $res
   *   Already opened resource.
   * @param array<int|string, callable(string $value) : void> $fieldGatherers
   *   Table whose keys are field names (all of which will be considered
   *   required fields) and whose values are functions taking a field value
   *   (corresponding to the named field) and doing with this value as the
   *   consumer desires. No fields are expected except those in $fieldGatherers.
   *
   * @throws \InvalidArgumentException
   *   Thrown if $res is not a resource.
   * @throws \InvalidArgumentException
   *   Thrown if a value in $fieldGatherers is not callable, or if
   *   $fieldGatherers is empty.
   * @throws \Ranine\Exception\BadCsvException
   *   Thrown if the CSV data is malformed.
   */
  public static function fromResource($res, array $fieldGatherers) : self {
    if (!is_resource($res)) throw new \InvalidArgumentException('$res is not a resource.');
    if ($fieldGatherers === []) throw new \InvalidArgumentException('$fieldGatherers is empty().');
    foreach ($fieldGatherers as $g) {
      if (!is_callable($g)) throw new \InvalidArgumentException('A field gatherer in $fieldGatherers is not callable.');
    }

    return self::fromResourceInternal($res, $fieldGatherers);
  }

  /**
   * Creates a CSV parser from an already-opened, valid resource.
   *
   * @param resource $res
   *   Resource, positioned at very beginning of file.
   * @param array<int|string, callable(string $value) : void> $fieldGatherers
   *   Field gatherers, in the format used by self::fromResource() and
   *   self::fromFile().
   *
   * @throws \Ranine\Exception\BadCsvException
   *   Thrown if the CSV data is malformed.
   */
  private static function fromResourceInternal($res, array $fieldGatherers) : self {
    $headers = static::getAndCheckCsvRow($res);
    if ($headers === NULL) {
      throw new BadCsvException('Empty input CSV data.');
    }
    if (count($headers) !== count($fieldGatherers)) {
      throw new BadCsvException('Invalid headers in CSV data.');
    }

    /** @var array<int, callable(string $value) : void> */
    $fieldGatherersByColumnId = [];
    foreach ($headers as $columnId => $fieldName) {
      $fieldName = (string) $fieldName;
      if (!isset($fieldGatherers[$fieldName])) {
        throw new BadCsvException('Spurious header in CSV data.');
      }
      $fieldGatherersByColumnId[(int) $columnId] = $fieldGatherers[$fieldName];
    }
    assert(count($fieldGatherersByColumnId) === count($fieldGatherers));

    return new self($res, $fieldGatherersByColumnId);
  }

  /**
   * @param resource $file
   */
  private static function getAndCheckCsvRow($file) : ?array {
    $row = fgetcsv($file, escape: '\\');
    if (is_array($row)) return $row;
    return NULL;
  }

}
