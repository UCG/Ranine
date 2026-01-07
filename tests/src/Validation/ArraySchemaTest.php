<?php

declare(strict_types = 1);

namespace Ranine\Tests\Validation;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;
use Ranine\Validation\ArraySchema;
use Ranine\Validation\ArraySchemaRule;
use Ranine\Exception\ExtraElementsArraySchemaException;
use Ranine\Exception\InvalidArraySchemaException;
use Ranine\Exception\MissingElementArraySchemaException;
use Ranine\Exception\InvalidTypeArraySchemaException;

#[CoversClass(ArraySchema::class)]
#[CoversMethod('ArraySchema','validate')]
#[Group('ranine')]
#[TestDox('Tests the ArraySchema class.')]
class ArraySchemaTest extends TestCase {

  private const BAD_SCHEMA_MESSAGE_1_1_1 = 'Value must be the integer "two."';
  private const BAD_SCHEMA_MESSAGE_1_2_1 = 'Value must be the integer "three."';
  private const BAD_SCHEMA_MESSAGE_1_2_2 = 'Value must be "c".';
  private const BAD_SCHEMA_MESSAGE_1_3 = 'Value must be "a".';
  private const BAD_SCHEMA_MESSAGE_2 = 'Value must be a boolean.';

  #[TestDox('Array schema under test.')]
  private ArraySchema $schema;

  #[TestDox('Tests the validate() method with an array with a missing element.')]
  public function testValidateInvalidSchemaMissingElement() : void {
    $arr = [
      '1' => [
        '1_1' => [
          '1_1_1' => 2,
        ],
        '1_3' => 'a',
      ],
      '2' => FALSE,
    ];

    $this->expectException(MissingElementArraySchemaException::class);
    $this->schema->validate($arr);
  }

  #[TestDox('Tests the validate() method with an array with an invalid optional element.')]
  public function testValidateInvalidSchemaInvalidOptionalElement() : void {
    $arr = [
      '1' => [
        '1_1' => [
          '1_1_1' => 3,
        ],
        '1_2' => [
          '1_2_1' => 3,
          '1_2_2' => 'c',
        ],
        '1_3' => 'a',
      ],
      '2' => FALSE,
    ];

    $this->expectException(InvalidArraySchemaException::class);
    $this->expectExceptionMessage(self::BAD_SCHEMA_MESSAGE_1_1_1);
    $this->schema->validate($arr);
  }

  #[TestDox('Tests the validate() method with an array with an extra element.')]
  public function testValidateInvalidSchemaExtraElement() : void {
    $arr = [
      '1' => [
        '1_1' => [
          '1_1_1' => 2,
          '1_1_2' => NULL,
        ],
        '1_2' => [
          '1_2_1' => 3,
          '1_2_2' => 'c',
        ],
        '1_3' => 'a',
      ],
      '2' => FALSE,
    ];

    $this->expectException(ExtraElementsArraySchemaException::class);
    $this->schema->validate($arr);
  }

  #[TestDox('Tests the validate() method with an array with a wrong value.')]
  public function testValidateInvalidSchemaWrongValue() : void {
    $arr = [
      '1' => [
        '1_1' => [
          '1_1_1' => 2,
        ],
        '1_2' => [
          '1_2_1' => 3,
          '1_2_2' => 'c',
        ],
        '1_3' => 'b',
      ],
      '2' => FALSE,
    ];

    $this->expectException(InvalidArraySchemaException::class);
    $this->expectExceptionMessage(self::BAD_SCHEMA_MESSAGE_1_3);
    $this->schema->validate($arr);
  }

  /**
   * @param array $arr
   *   Array to test with.
   */
  #[DataProvider('provideValidArrays')]
  #[DoesNotPerformAssertions]
  #[TestDox('Tests the validate() method with array(s) with valid schemas.')]
  public function testValidateValidSchema(array $arr) : void {
    $this->schema->validate($arr);
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() : void {
    parent::setUp();

    // Set up an array schema.
    $this->schema = new ArraySchema([
      '1' => new ArraySchemaRule(fn() => NULL, TRUE, [
        '1_1' => new ArraySchemaRule(fn() => NULL, FALSE, [
          '1_1_1' => new ArraySchemaRule(fn($x) => $x === 2 ? NULL : new InvalidArraySchemaException(self::BAD_SCHEMA_MESSAGE_1_1_1), TRUE),
        ]),
        '1_2' => new ArraySchemaRule(fn() => NULL, TRUE, [
          '1_2_1' => new ArraySchemaRule(fn($x) => $x === 3 ? NULL : new InvalidArraySchemaException(self::BAD_SCHEMA_MESSAGE_1_2_1)),
          '1_2_2' => new ArraySchemaRule(fn($x) => $x === 'c' ? NULL : new InvalidArraySchemaException(self::BAD_SCHEMA_MESSAGE_1_2_2)),
        ]),
        '1_3' => new ArraySchemaRule(fn($x) => $x === 'a' ? NULL : new InvalidArraySchemaException(self::BAD_SCHEMA_MESSAGE_1_3)),
      ]),
      '2' => new ArraySchemaRule(fn($x) => is_bool($x) ? NULL : new InvalidTypeArraySchemaException(self::BAD_SCHEMA_MESSAGE_2)),
    ]);
  }

  /**
   * Provides valid arrays for testValidateValidSchema().
   * 
   * @return mixed[][][]
   *   Arrays.
   */
  public static function provideValidArrays() : array {
    return [
      [[
        '1' => [
          '1_1' => [
            '1_1_1' => 2,
          ],
          '1_2' => [
            '1_2_1' => 3,
            '1_2_2' => 'c',
          ],
          '1_3' => 'a',
        ],
        '2' => FALSE,
      ]],
      [[
        '1' => [
          '1_2' => [
            '1_2_1' => 3,
            '1_2_2' => 'c',
          ],
          '1_3' => 'a',
        ],
        '2' => FALSE,
      ]],
    ];
  }

}
