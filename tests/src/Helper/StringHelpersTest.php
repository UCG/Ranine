<?php

declare(strict_types = 1);

namespace Ranine\Tests\Helper;

require_once '../../../src/Helper/StringHelpers.php';

use PHPUnit\Framework\TestCase;
use Ranine\Helper\StringHelpers;

/**
 * Tests the StringHelpers class.
 *
 * @coversDefaultClass \Ranine\Helper\StringHelpers
 * @group ranine
 */
class StringHelpersTest extends TestCase {

  /**
   * Tests the emptyToNull() method with null input.
   *
   * @covers ::emptyToNull
   */
  public function testEmptyToNullNullInput() : void {
    // Input: $str = NULL.
    // Expected output: NULL.

    $this->assertNull(StringHelpers::emptyToNull(NULL));
  }

  /**
   * Tests the emptyToNull() method with an empty string.
   *
   * @covers ::emptyToNull
   */
  public function testEmptyToNullEmptyInput() : void {
    // Input: $str = "".
    // Expected output: NULL.

    $this->assertNull(StringHelpers::emptyToNull(''));
  }

  /**
   * Tests the emptyToNull() method with a non-empty string.
   *
   * @covers ::emptyToNull
   */
  public function testEmptyToNullOrdinaryInput() : void {
    // Input: $str = 'Hello, there.'
    // Expected output: 'Hello, there.'

    $this->assertEquals('Hello, there.', StringHelpers::emptyToNull('Hello, there.'));
  }

  /**
   * Tests the isNonEmptyString() method with null input.
   *
   * @covers ::isNonEmptyString
   */
  public function testIsNonEmptyStringNullInput() : void {
    // Input: $value = NULL.
    // Expected output: FALSE.

    $this->assertFalse(StringHelpers::isNonEmptyString(NULL));
  }

  /**
   * Tests the isNonEmptyString() method with an empty value.
   *
   * @covers ::isNonEmptyString
   */
  public function testIsNonEmptyStringEmptyInput() : void {
    // Input: $value = ''.
    // Expected output: FALSE.

    $this->assertFalse(StringHelpers::isNonEmptyString(''));
  }

  /**
   * Tests the isNonEmptyString() method with an non-empty string value.
   *
   * @covers ::isNonEmptyString
   */
  public function testIsNonEmptyStringOrdinaryInput() : void {
    // Input: $value = '44'.
    // Expected output: TRUE.

    $this->assertTrue(StringHelpers::isNonEmptyString('44'));
  }

  /**
   * Tests the isNonEmptyString() method with a strange non-empty value.
   *
   * @covers ::isNonEmptyString
   */
  public function testIsNonEmptyStringUnordinaryInput() : void {
    // Input: $value = '$%^ &'.
    // Expected output: TRUE.

    $this->assertTrue(StringHelpers::isNonEmptyString('$%^ &'));
  }

  /**
   * Tests the isNullOrEmpty() method with a null input.
   *
   * @covers ::isNullOrEmpty
   */
  public function testIsNullOrEmptyNullInput() : void {
    // Input: $value = NULL.
    // Expected output: TRUE.

    $this->assertTrue(StringHelpers::isNullOrEmpty(NULL));
  }

  /**
   * Tests the isNullOrEmpty() method with an empty input.
   *
   * @covers ::isNullOrEmpty
   */
  public function testIsNullOrEmptyEmptyInput() : void {
    // Input: $value = ''.
    // Expected output: TRUE.

    $this->assertTrue(StringHelpers::isNullOrEmpty(''));
  }

  /**
   * Tests the isNullOrEmpty() method with a non-empty input.
   *
   * @covers ::isNullOrEmpty
   */
  public function testIsNullOrEmptyOrdinaryInput() : void {
    // Input: $value = 'Hello, there.'.
    // Expected output: FALSE.

    $this->assertFalse(StringHelpers::isNullOrEmpty('Hello, there.'));
  }

  /**
   * Tests the escape() method with an escape character that is too long.
   *
   * @covers ::escape
   */
  public function testEscapeEscapeCharacterLengthTooLong() : void {
    // Input: 
    // $str = 'Hello, there.',
    // $otherSpecialCharacters = ['y','!'] ,
    // $escapeCharacter = "\ee".
    // Expected output: '$escapeCharacter is not of unit length.'.

    $this->expectException(\InvalidArgumentException::class);
    StringHelpers::escape('Hello, there.', ['y','!'], "\ee");
  }

  /**
   * Tests the escape() method with an escape character that is empty.
   *
   * @covers ::escape
   */
  public function testEscapeEscapeCharacterLengthEmpty() : void {
    // Input: 
    // $str = 'Hello, there.',
    // $otherSpecialCharacters = ['y','!'] ,
    // $escapeCharacter = ''.
    // Expected output: '$escapeCharacter is not of unit length.'.

    $this->expectException(\InvalidArgumentException::class);
    StringHelpers::escape('Hello, there.', ['y','!'], '');
  }
  
  /**
   * Tests the escape() method with nothing to escape.
   *
   * @covers ::escape
   */
  public function testEscapeNoEscape() : void {
    // Input: 
    // $str = 'Hello, there.',
    // $otherSpecialCharacters = ['y', '!'] ,
    // $escapeCharacter = "\e".
    // Expected output: 'Hello, there.'.

    $this->assertEquals('Hello, there.', StringHelpers::escape('Hello, there.', ['y', '!'], "\e"));
  }

  /**
   * Tests the escape() method with a special character that is too long.
   *
   * @covers ::escape
   */
  public function testEscapeOtherSpecialCharactersLengthTooLong() : void{
    // Input: 
    // $str = 'Hello, there.',
    // $otherSpecialCharacters = ['yy', '!'] ,
    // $escapeCharacter = "\e".
    // Expected output: 'An element of $otherSpecialCharacters is not a string of unit length.'.

    $this->expectException(\InvalidArgumentException::class);
    StringHelpers::escape('Hello, there.', ['yy','!'], "\e");
  }

  /**
   * Tests the escape() method with a special character that is not a string.
   *
   * @covers ::escape
   */
  public function testEscapeOtherSpecialCharactersNotChar() : void {
    // Input: 
    // $str = 'Hello, there.',
    // $otherSpecialCharacters = [1] ,
    // $escapeCharacter = "\e".
    // Expected output: 'An element of $otherSpecialCharacters is not a string of unit length.'.

    $this->expectException(\InvalidArgumentException::class);
    StringHelpers::escape('Hello, there.', [1], "\e");
  }

  /**
   * Tests the escape() method with the default escape character.
   *
   * @covers ::escape
   */
  public function testEscapeOrdinaryEscape() : void {
    // Input: 
    // $str = 'Hello, there.',
    // $otherSpecialCharacters = ['h', 'H', .] ,
    // $escapeCharacter = "\e".
    // Expected output: "\eHello, t\ehere.".

    $this->assertEquals("\eHello, t\ehere.", StringHelpers::escape('Hello, there.', ['h', 'H'], "\e"));
  }

  /**
   * Tests the escape() method with the default escape character.
   *
   * @covers ::escape
   */
  public function testEscapeEscapeCharacterInInput() : void {
    // Input: 
    // $str = "Hello\e, there.",
    // $otherSpecialCharacters = ['h', 'H'] ,
    // $escapeCharacter = "\e".
    // Expected output: "\eHello\e\e, t\ehere.".

    $this->assertEquals("\eHello\e\e, t\ehere.", StringHelpers::escape("Hello\e, there.", ['h', 'H'], "\e"));
  }

  /**
   * Tests the getAfter() method with a seperator greater than 1.
   *
   * @covers ::getAfter
   */
  public function testGetAfterSeparatorToolong() : void {
    // Input: 
    // $source: 'Hello, there!'
    // $separator: 'ee'
    // Expected Output: '$separator must be of unit length.'

    $this->expectException(\InvalidArgumentException::class);
    StringHelpers::getAfter('Hello, there.', 'ee');  
  }

  /**
   * Tests the getAfter() method with a seperator that is empty.
   *
   * @covers ::getAfter
   */
  public function testGetAfterSeparatorEmpty() : void {
    // Input: 
    // $source: 'Hello, there!'
    // $separator: ''
    // Expected Output: '$separator must be of unit length.'
  
    $this->expectException(\InvalidArgumentException::class);
    StringHelpers::getAfter('Hello, there.', '');
  }

   /**
   * Tests the getAfter() method with an empty source.
   *
   * @covers ::getAfter
   */
  public function testGetAfterSourceEmpty() : void {
    // Input: 
    // $source: ''
    // $separator: 'e'
    // Expected Output: ''

    StringHelpers::getAfter('', 'e');
    $this->assertEquals('', '');
  }

   /**
   * Tests the getAfter() method with source === separator.
   *
   * @covers ::getAfter
   */
  public function testGetAfterSourceAndSeparatorIdentical() : void {
    // Input: 
    // $source: '12344'
    // $separator: '4'
    // Expected Output: ''

    $this->assertEquals('', StringHelpers::getAfter('12344', '4'));
  }

   /**
   * Tests the getAfter() method with normal string values.
   *
   * @covers ::getAfter
   */
  public function testGetAfter() : void {
    // Input: 
    // $source: 'Hello, there!'
    // $separator: 'e'
    // Expected Output: '!'

    $this->assertEquals('!', StringHelpers::getAfter('Hello, there!', 'e'));
  }

  /**
   * Tests the getAfter() method with separator not found in source.
   *
   * @covers ::getAfter
   */
  public function testGetAfterNoSeparatorInSource() : void {
    // Input: 
    // $source: 'Hello, there!'
    // $separator: 'f'
    // Expected Output: 'Hello, there!'

    $this->assertEquals('Hello, there!', StringHelpers::getAfter('Hello, there!', 'f'));
  }

  /**
   * Tests the getValueOrDefault() method with empty string input.
   *
   * @covers ::getValueOrDefault
   */
  public function testGetValueOrDefaultEmptyInput() : void {
    // Input: 
    // $str: ''
    // $defaultMessage: 'Nothing to see here.'
    // Expected Output: 'Nothing to see here.'

    $this->assertEquals('Nothing to see here.', StringHelpers::getValueOrDefault('', 'Nothing to see here.'));
  }

  /**
   * Tests the getValueOrDefault() method with NULL string input.
   *
   * @covers ::getValueOrDefault
   */
  public function testGetValueOrDefaultNullInput() : void {
    // Input: 
    // $str: 'NULL'
    // $defaultMessage: 'Nothing to see here.'
    // Expected Output: 'Nothing to see here.'

    $this->assertEquals('Nothing to see here.', StringHelpers::getValueOrDefault(NULL, 'Nothing to see here.'));
  }

  /**
   * Tests the getValueOrDefault() method with normal string input.
   *
   * @covers ::getValueOrDefault
   */
  public function testGetValueOrDefaultNormalInput() : void {
    // Input: 
    // $str: 'Hello, there!'
    // $defaultMessage: 'Nothing to see here.'
    // Expected Output: 'Hello, there!'

    $this->assertEquals('Hello, there!', StringHelpers::getValueOrDefault('Hello, there!', 'Nothing to see here.'));
  }
  
}
