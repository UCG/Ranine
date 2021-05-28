<?php

declare(strict_types = 1);

namespace Ranine\Tests\Validation;

use Ranine\Validation\ArraySchema;
use Ranine\Validation\ArraySchemaRule;
use Ranine\Exception\ExtraElementsArraySchemaException;
use Ranine\Exception\InvalidArraySchemaException;
use Ranine\Exception\MissingElementArraySchemaException;
use PHPUnit\Framework\TestCase;

/**
 * Tests the ArraySchema class.
 *
 * @coversDefaultClass \Ranine\Validation\ArraySchema
 * @group ranine
 */
class ArraySchemaTest extends TestCase {

  private const BAD_SCHEMA_MESSAGE_1_1_1 = 'Value must be the integer "two."';
  private const BAD_SCHEMA_MESSAGE_1_2_1 = 'Value must be the integer "three."';
  private const BAD_SCHEMA_MESSAGE_1_2_2 = 'Value must be "c".';
  private const BAD_SCHEMA_MESSAGE_1_3 = 'Value must be "a".';
  private const BAD_SCHEMA_MESSAGE_2 = 'Value must be a boolean.';

  /**
   * Array schema under test.
   */
  private ArraySchema $schema;

  /**
   * Provides valid arrays for testValidateValidSchema().
   *
   * @return array
   *   Arrays.
   */
  public function provideValidArrays() : array {
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

  /**
   * Tests the validate() method with an array with a missing element.
   *
   * @covers ::validate
   */
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

  /**
   * Tests the validate() method with an array with an invalid optional element.
   *
   * @covers ::validate
   */
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
    $this->expectExceptionMessage(static::BAD_SCHEMA_MESSAGE_1_1_1);
    $this->schema->validate($arr);
  }

  /**
   * Tests the validate() method with an array with an extra element.
   *
   * @covers ::validate
   */
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

  /**
   * Tests the validate() method with an array with a wrong value.
   *
   * @covers ::validate
   */
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
    $this->expectExceptionMessage(static::BAD_SCHEMA_MESSAGE_1_3);
    $this->schema->validate($arr);
  }

  /**
   * Tests the validate() method with array(s) with valid schemas.
   *
   * @covers ::validate
   * @dataProvider provideValidArrays
   * @doesNotPerformAssertions
   *
   * @param array $arr
   *   Array to test with.
   */
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
      '1' => new ArraySchemaRule(fn($x) => NULL, TRUE, [
        '1_1' => new ArraySchemaRule(fn($x) => NULL, FALSE, [
          '1_1_1' => new ArraySchemaRule(fn($x) => $x === 2 ? NULL : new InvalidArraySchemaException(static::BAD_SCHEMA_MESSAGE_1_1_1), TRUE),
        ]),
        '1_2' => new ArraySchemaRule(fn($x) => NULL, TRUE, [
          '1_2_1' => new ArraySchemaRule(fn($x) => $x === 3 ? NULL : new InvalidArraySchemaException(static::BAD_SCHEMA_MESSAGE_1_2_1)),
          '1_2_2' => new ArraySchemaRule(fn($x) => $x === 'c' ? NULL : new InvalidArraySchemaException(static::BAD_SCHEMA_MESSAGE_1_2_2)),
        ]),
        '1_3' => new ArraySchemaRule(fn($x) => $x === 'a' ? NULL : new InvalidArraySchemaException(static::BAD_SCHEMA_MESSAGE_1_3)),
      ]),
      '2' => new ArraySchemaRule(fn($x) => is_bool($x) ? NULL : new InvalidArraySchemaException(static::BAD_SCHEMA_MESSAGE_2)),
    ]);
  }

}
