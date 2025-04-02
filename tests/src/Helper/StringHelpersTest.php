<?php

declare(strict_types = 1);

namespace Ranine\Tests\Helper;

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
   * Tests the assemble() method with escape characters in $items strings.
   *
   * @covers ::assemble
   */
  public function testAssembleEscapeCharacterInStrings() : void {
    // Input:
    // $separator: '?'
    // $items: 'H\eello?' , 'Th\eer\ee!'

    $this->assertEquals("H\e\eello\e??Th\e\eer\e\ee!", StringHelpers::assemble('?', "H\eello?", "Th\eer\ee!"));
  }

  /**
   * Tests the assemble() method with two empty items strings.
   *
   * @covers ::assemble
   */
  public function testAssembleItemsStringsEmpty() : void {
    // Input:
    // $separator: '?'
    // $items: '' , ''

    $this->assertEquals('?', StringHelpers::assemble('?', '', ''));
  }

  /**
   * Tests the assemble() method with just one item string.
   *
   * @covers ::assemble
   */
  public function testAssembleOnlyOneItem() : void {
    // Input:
    // $separator: '?'
    // $items: 'Hello?'

    $this->assertEquals("Hello\e?", StringHelpers::assemble('?', 'Hello?'));
  }

  /**
   * Tests the assemble() method with an empty separator string.
   *
   * @covers ::assemble
   */
  public function testAssembleSeparatorStringEmpty() : void {
    // Input:
    // $separator: ''
    // $items: 'Hello' , 'there!'

    $this->expectException(\InvalidArgumentException::class);
    StringHelpers::assemble('', "H\eello?", " th\eer\ee.");
  }

  /**
   * Tests the assemble() method with ordinary strings and separator.
   *
   * @covers ::assemble
   */
  public function testAssembleOrdinarySeparatorAndItems() : void {
    // Input:
    // $separator: '?'
    // $items: 'Hello? ' , 'There!'

    $this->assertEquals("Hello\e??There!", StringHelpers::assemble('?', 'Hello?', 'There!'));
  }

  /**
   * Tests the assemble() method with a separator that is too long.
   *
   * @covers ::assemble
   */
  public function testAssembleSeparatorTooLong() : void {
    // Input:
    // $separator: '??'
    // $items: 'Hello?' , 'there!'

    $this->expectException(\InvalidArgumentException::class);
    StringHelpers::assemble('??', "H\eello?", " th\eer\ee.");
  }

  /**
   * Tests the emptyToNull() method with an empty string.
   *
   * @covers ::emptyToNull
   */
  public function testEmptyToNullEmptyInput() : void {
    // Input: $str = "".

    $this->assertNull(StringHelpers::emptyToNull(''));
  }

  /**
   * Tests the emptyToNull() method with null input.
   *
   * @covers ::emptyToNull
   */
  public function testEmptyToNullNullInput() : void {
    // Input: $str = NULL.

    $this->assertNull(StringHelpers::emptyToNull(NULL));
  }

  /**
   * Tests the emptyToNull() method with a non-empty string.
   *
   * @covers ::emptyToNull
   */
  public function testEmptyToNullOrdinaryInput() : void {
    // Input: $str = 'Hello, there.'

    $this->assertEquals('Hello, there.', StringHelpers::emptyToNull('Hello, there.'));
  }

  /**
   * Tests the isNonEmptyString() method with an empty value.
   *
   * @covers ::isNonEmptyString
   */
  public function testIsNonEmptyStringEmptyInput() : void {
    // Input: $value = ''.

    $this->assertFalse(StringHelpers::isNonEmptyString(''));
  }

  /**
   * Tests the isNonEmptyString() method with null input.
   *
   * @covers ::isNonEmptyString
   */
  public function testIsNonEmptyStringNullInput() : void {
    // Input: $value = NULL.

    $this->assertFalse(StringHelpers::isNonEmptyString(NULL));
  }

  /**
   * Tests the isNonEmptyString() method with an non-empty string value.
   *
   * @covers ::isNonEmptyString
   */
  public function testIsNonEmptyStringOrdinaryInput() : void {
    // Input: $value = '44'.

    $this->assertTrue(StringHelpers::isNonEmptyString('44'));
  }

  /**
   * Tests the isNonEmptyString() method with a strange non-empty value.
   *
   * @covers ::isNonEmptyString
   */
  public function testIsNonEmptyStringUnordinaryInput() : void {
    // Input: $value = '$%^ Ð &'.

    $this->assertTrue(StringHelpers::isNonEmptyString('$%^ Ð &'));
  }

  /**
   * Tests the isNullOrEmpty() method with an empty input.
   *
   * @covers ::isNullOrEmpty
   */
  public function testIsNullOrEmptyEmptyInput() : void {
    // Input: $value = ''.

    $this->assertTrue(StringHelpers::isNullOrEmpty(''));
  }

  /**
   * Tests the isNullOrEmpty() method with a null input.
   *
   * @covers ::isNullOrEmpty
   */
  public function testIsNullOrEmptyNullInput() : void {
    // Input: $value = NULL.

    $this->assertTrue(StringHelpers::isNullOrEmpty(NULL));
  }

  /**
   * Tests the isNullOrEmpty() method with a non-empty input.
   *
   * @covers ::isNullOrEmpty
   */
  public function testIsNullOrEmptyOrdinaryInput() : void {
    // Input: $value = 'Hello, there.'.

    $this->assertFalse(StringHelpers::isNullOrEmpty('Hello, there.'));
  }
  
  /**
   * Tests the escape() method with an escape character in the input.
   *
   * @covers ::escape
   */
  public function testEscapeEscapeCharacterInInput() : void {
    // Input: 
    // $str = "Hello\e, there.",
    // $otherSpecialCharacters = ['h', 'H'] ,
    // $escapeCharacter = "\e".

    $this->assertEquals("\eHello\e\e, t\ehere.", StringHelpers::escape("Hello\e, there.", ['h', 'H'], "\e"));
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

    $this->expectException(\InvalidArgumentException::class);
    StringHelpers::escape('Hello, there.', ['y','!'], '');
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

    $this->expectException(\InvalidArgumentException::class);
    StringHelpers::escape('Hello, there.', ['y','!'], "\ee");
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

    $this->assertEquals('Hello, there.', StringHelpers::escape('Hello, there.', ['y', '!'], "\e"));
  }

  /**
   * Tests the escape() method with Ordinary input and escape character.
   *
   * @covers ::escape
   */
  public function testEscapeOrdinaryEscape() : void {
    // Input: 
    // $str = 'Hello, there.',
    // $otherSpecialCharacters = ['h', 'H', .] ,
    // $escapeCharacter = "\e".

    $this->assertEquals("\eHello, t\ehere.", StringHelpers::escape('Hello, there.', ['h', 'H'], "\e"));
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

    $this->expectException(\InvalidArgumentException::class);
    StringHelpers::escape('Hello, there.', ['yy','!'], "\e");
  }

  /**
   * Tests the escape() method with a special character that is not a string.
   *
   * @covers ::escape
   */
  public function testEscapeOtherSpecialCharacterNotString() : void {
    // Input: 
    // $str = 'Hello, there.',
    // $otherSpecialCharacters = [1] ,
    // $escapeCharacter = "\e".

    $this->expectException(\InvalidArgumentException::class);
    StringHelpers::escape('Hello, there.', [1], "\e");
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

    $this->assertEquals('Hello, there!', StringHelpers::getAfter('Hello, there!', 'f'));
  }
  
   /**
   * Tests the getAfter() method with normal string values.
   *
   * @covers ::getAfter
   */
  public function testGetAfterOrdinaryInput() : void {
    // Input: 
    // $source: 'Hello, there!'
    // $separator: 'e'

    $this->assertEquals('!', StringHelpers::getAfter('Hello, there!', 'e'));
  }
  
  /**
   * Tests the getAfter() method with a separator that is empty.
   *
   * @covers ::getAfter
   */
  public function testGetAfterSeparatorEmpty() : void {
    // Input: 
    // $source: 'Hello, there!'
    // $separator: ''
  
    $this->expectException(\InvalidArgumentException::class);
    StringHelpers::getAfter('Hello, there.', '');
  }

  /**
   * Tests the getAfter() method with separator length greater than 1.
   *
   * @covers ::getAfter
   */
  public function testGetAfterSeparatorToolong() : void {
    // Input: 
    // $source: 'Hello, there!'
    // $separator: 'ee'

    $this->expectException(\InvalidArgumentException::class);
    StringHelpers::getAfter('Hello, there.', 'ee');  
  }

   /**
   * Tests the getAfter() method with $source[$endIndex] === $separator.
   *
   * @covers ::getAfter
   */
  public function testGetAfterSeparatorAtEndOfSource() : void {
    // Input: 
    // $source: '12344'
    // $separator: '4'

    $this->assertEquals('', StringHelpers::getAfter('12344', '4'));
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

    StringHelpers::getAfter('', 'e');
    $this->assertEquals('', '');
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

    $this->assertEquals('Nothing to see here.', StringHelpers::getValueOrDefault('', 'Nothing to see here.'));
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

    $this->assertEquals('Hello, there!', StringHelpers::getValueOrDefault('Hello, there!', 'Nothing to see here.'));
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

    $this->assertEquals('Nothing to see here.', StringHelpers::getValueOrDefault(NULL, 'Nothing to see here.'));
  }
  
}
