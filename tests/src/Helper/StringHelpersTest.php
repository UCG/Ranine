<?php

declare(strict_types = 1);

namespace Ranine\Tests\Helper;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;
use Ranine\Helper\StringHelpers;

#[TestDox('Tests the StringHelpers class.')]
#[CoversClass(StringHelpers::class)]
#[Group('ranine')]
class StringHelpersTest extends TestCase {

  #[TestDox('Tests the assemble() method with escape characters in $items strings.')]
  #[CoversFunction('assemble')]
  public function testAssembleEscapeCharacterInStrings() : void {
    // Input:
    // $separator: '?'
    // $items: 'H\eello?' , 'Th\eer\ee!'

    $this->assertEquals("H\e\eello\e??Th\e\eer\e\ee!", StringHelpers::assemble('?', "H\eello?", "Th\eer\ee!"));
  }

  #[TestDox('Tests the assemble() method with two empty items strings.')]
  #[CoversFunction('assemble')]
  public function testAssembleItemsStringsEmpty() : void {
    // Input:
    // $separator: '?'
    // $items: '' , ''

    $this->assertEquals('?', StringHelpers::assemble('?', '', ''));
  }

  #[TestDox('Tests the assemble() method with just one item string.')]
  #[CoversFunction('assemble')]
  public function testAssembleOnlyOneItem() : void {
    // Input:
    // $separator: '?'
    // $items: 'Hello?'

    $this->assertEquals("Hello\e?", StringHelpers::assemble('?', 'Hello?'));
  }

  #[TestDox('Tests the assemble() method with ordinary strings and separator.')]
  #[CoversFunction('assemble')]
  public function testAssembleOrdinarySeparatorAndItems() : void {
    // Input:
    // $separator: '?'
    // $items: 'Hello? ' , 'There!'

    $this->assertEquals("Hello\e??There!", StringHelpers::assemble('?', 'Hello?', 'There!'));
  }

  #[TestDox('Tests the assemble() method with an empty separator string.')]
  #[CoversFunction('assemble')]
  public function testAssembleSeparatorStringEmpty() : void {
    // Input:
    // $separator: ''
    // $items: 'Hello' , 'there!'

    $this->expectException(\InvalidArgumentException::class);
    StringHelpers::assemble('', "H\eello?", " th\eer\ee.");
  }

  #[TestDox('Tests the assemble() method with a separator that is too long.')]
  #[CoversFunction('assemble')]
  public function testAssembleSeparatorTooLong() : void {
    // Input:
    // $separator: '??'
    // $items: 'Hello?' , 'there!'

    $this->expectException(\InvalidArgumentException::class);
    StringHelpers::assemble('??', "H\eello?", " th\eer\ee.");
  }

  #[TestDox('Tests the emptyToNull() method with an empty string.')]
  #[CoversFunction('emptyToNull')]
  public function testEmptyToNullEmptyInput() : void {
    // Input: $str = "".

    $this->assertNull(StringHelpers::emptyToNull(''));
  }

  #[TestDox('Tests the emptyToNull() method with null input.')]
  #[CoversFunction('emptyToNull')]
  public function testEmptyToNullNullInput() : void {
    // Input: $str = NULL.

    $this->assertNull(StringHelpers::emptyToNull(NULL));
  }

  #[TestDox('Tests the emptyToNull() method with a non-empty string.')]
  #[CoversFunction('emptyToNull')]
  public function testEmptyToNullOrdinaryInput() : void {
    // Input: $str = 'Hello, there.'

    $this->assertEquals('Hello, there.', StringHelpers::emptyToNull('Hello, there.'));
  }

  #[TestDox('Tests the isNonEmptyString() method with an empty value.')]
  #[CoversFunction('isNonEmptyString')]
  public function testIsNonEmptyStringEmptyInput() : void {
    // Input: $value = ''.

    $this->assertFalse(StringHelpers::isNonEmptyString(''));
  }

  #[TestDox('Tests the isNonEmptyString() method with null input.')]
  #[CoversFunction('isNonEmptyString')]
  public function testIsNonEmptyStringNullInput() : void {
    // Input: $value = NULL.

    $this->assertFalse(StringHelpers::isNonEmptyString(NULL));
  }

  #[TestDox('Tests the isNonEmptyString() method with an non-empty string value.')]
  #[CoversFunction('isNonEmptyString')]
  public function testIsNonEmptyStringOrdinaryInput() : void {
    // Input: $value = '44'.

    $this->assertTrue(StringHelpers::isNonEmptyString('44'));
  }

  #[TestDox('Tests the isNonEmptyString() method with a strange non-empty value.')]
  #[CoversFunction('isNonEmptyString')]
  public function testIsNonEmptyStringUnordinaryInput() : void {
    // Input: $value = '$%^ Ð &'.

    $this->assertTrue(StringHelpers::isNonEmptyString('$%^ Ð &'));
  }

  #[TestDox('Tests the isNullOrEmpty() method with an empty input.')]
  #[CoversFunction('isNullOrEmpty')]
  public function testIsNullOrEmptyEmptyInput() : void {
    // Input: $value = ''.

    $this->assertTrue(StringHelpers::isNullOrEmpty(''));
  }

  #[TestDox('Tests the isNullOrEmpty() method with a null input.')]
  #[CoversFunction('isNullOrEmpty')]
  public function testIsNullOrEmptyNullInput() : void {
    // Input: $value = NULL.

    $this->assertTrue(StringHelpers::isNullOrEmpty(NULL));
  }

  #[TestDox('Tests the isNullOrEmpty() method with a non-empty input.')]
  #[CoversFunction('isNullOrEmpty')]
  public function testIsNullOrEmptyOrdinaryInput() : void {
    // Input: $value = 'Hello, there.'.

    $this->assertFalse(StringHelpers::isNullOrEmpty('Hello, there.'));
  }

  #[TestDox('Tests the escape() method with an escape character in the input.')]
  #[CoversFunction('escape')]
  public function testEscapeEscapeCharacterInInput() : void {
    // Input: 
    // $str = "Hello\e, there.",
    // $otherSpecialCharacters = ['h', 'H'] ,
    // $escapeCharacter = "\e".

    $this->assertEquals("\eHello\e\e, t\ehere.", StringHelpers::escape("Hello\e, there.", ['h', 'H'], "\e"));
  }

  #[TestDox('Tests the escape() method with an escape character that is empty.')]
  #[CoversFunction('escape')]
  public function testEscapeEscapeCharacterLengthEmpty() : void {
    // Input: 
    // $str = 'Hello, there.',
    // $otherSpecialCharacters = ['y','!'] ,
    // $escapeCharacter = ''.

    $this->expectException(\InvalidArgumentException::class);
    StringHelpers::escape('Hello, there.', ['y','!'], '');
  }

  #[TestDox('Tests the escape() method with an escape character that is too long.')]
  #[CoversFunction('escape')]
  public function testEscapeEscapeCharacterLengthTooLong() : void {
    // Input: 
    // $str = 'Hello, there.',
    // $otherSpecialCharacters = ['y','!'] ,
    // $escapeCharacter = "\ee".

    $this->expectException(\InvalidArgumentException::class);
    StringHelpers::escape('Hello, there.', ['y','!'], "\ee");
  }

  #[TestDox('Tests the escape() method with nothing to escape.')]
  #[CoversFunction('escape')]
  public function testEscapeNoEscape() : void {
    // Input: 
    // $str = 'Hello, there.',
    // $otherSpecialCharacters = ['y', '!'] ,
    // $escapeCharacter = "\e".

    $this->assertEquals('Hello, there.', StringHelpers::escape('Hello, there.', ['y', '!'], "\e"));
  }

  #[TestDox('Tests the escape() method with Ordinary input and escape character.')]
  #[CoversFunction('escape')]
  public function testEscapeOrdinaryEscape() : void {
    // Input: 
    // $str = 'Hello, there.',
    // $otherSpecialCharacters = ['h', 'H', .] ,
    // $escapeCharacter = "\e".

    $this->assertEquals("\eHello, t\ehere.", StringHelpers::escape('Hello, there.', ['h', 'H'], "\e"));
  }

  #[TestDox('Tests the escape() method with a special character that is not a string.')]
  #[CoversFunction('escape')]
  public function testEscapeOtherSpecialCharacterNotString() : void {
    // Input: 
    // $str = 'Hello, there.',
    // $otherSpecialCharacters = [1] ,
    // $escapeCharacter = "\e".

    $this->expectException(\InvalidArgumentException::class);
    StringHelpers::escape('Hello, there.', [1], "\e");
  }

  #[TestDox('Tests the escape() method with a special character that is too long.')]
  #[CoversFunction('escape')]
  public function testEscapeOtherSpecialCharactersLengthTooLong() : void{
    // Input: 
    // $str = 'Hello, there.',
    // $otherSpecialCharacters = ['yy', '!'] ,
    // $escapeCharacter = "\e".

    $this->expectException(\InvalidArgumentException::class);
    StringHelpers::escape('Hello, there.', ['yy','!'], "\e");
  }

  #[TestDox('Tests the getAfter() method with separator not found in source.')]
  #[CoversFunction('getAfter')]
  public function testGetAfterNoSeparatorInSource() : void {
    // Input: 
    // $source: 'Hello, there!'
    // $separator: 'f'

    $this->assertEquals('Hello, there!', StringHelpers::getAfter('Hello, there!', 'f'));
  }

  #[TestDox('Tests the getAfter() method with normal string values.')]
  #[CoversFunction('getAfter')]
  public function testGetAfterOrdinaryInput() : void {
    // Input: 
    // $source: 'Hello, there!'
    // $separator: 'e'

    $this->assertEquals('!', StringHelpers::getAfter('Hello, there!', 'e'));
  }

  #[TestDox('Tests the getAfter() method with $source[$endIndex] === $separator.')]
  #[CoversFunction('getAfter')]
  public function testGetAfterSeparatorAtEndOfSource() : void {
    // Input: 
    // $source: '12344'
    // $separator: '4'

    $this->assertEquals('', StringHelpers::getAfter('12344', '4'));
  }

  #[TestDox('Tests the getAfter() method with a separator that is empty.')]
  #[CoversFunction('getAfter')]
  public function testGetAfterSeparatorEmpty() : void {
    // Input: 
    // $source: 'Hello, there!'
    // $separator: ''

    $this->expectException(\InvalidArgumentException::class);
    StringHelpers::getAfter('Hello, there.', '');
  }

  #[TestDox('Tests the getAfter() method with separator length greater than 1.')]
  #[CoversFunction('getAfter')]
  public function testGetAfterSeparatorTooLong() : void {
    // Input: 
    // $source: 'Hello, there!'
    // $separator: 'ee'

    $this->expectException(\InvalidArgumentException::class);
    StringHelpers::getAfter('Hello, there.', 'ee');  
  }

  #[TestDox('Tests the getAfter() method with an empty source.')]
  #[CoversFunction('getAfter')]
  public function testGetAfterSourceEmpty() : void {
    // Input: 
    // $source: ''
    // $separator: 'e'

    StringHelpers::getAfter('', 'e');
    $this->assertEquals('', '');
  }

  #[TestDox('Tests the getValueOrDefault() method with empty string input.')]
  #[CoversFunction('getValueOrDefault')]
  public function testGetValueOrDefaultEmptyInput() : void {
    // Input: 
    // $str: ''
    // $defaultMessage: 'Nothing to see here.'

    $this->assertEquals('Nothing to see here.', StringHelpers::getValueOrDefault('', 'Nothing to see here.'));
  }

  #[TestDox('Tests the getValueOrDefault() method with normal string input.')]
  #[CoversFunction('getValueOrDefault')]
  public function testGetValueOrDefaultNormalInput() : void {
    // Input: 
    // $str: 'Hello, there!'
    // $defaultMessage: 'Nothing to see here.'

    $this->assertEquals('Hello, there!', StringHelpers::getValueOrDefault('Hello, there!', 'Nothing to see here.'));
  }

  #[TestDox('Tests the getValueOrDefault() method with NULL string input.')]
  #[CoversFunction('getValueOrDefault')]
  public function testGetValueOrDefaultNullInput() : void {
    // Input: 
    // $str: 'NULL'
    // $defaultMessage: 'Nothing to see here.'

    $this->assertEquals('Nothing to see here.', StringHelpers::getValueOrDefault(NULL, 'Nothing to see here.'));
  }

}
