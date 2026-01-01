<?php

declare(strict_types = 1);

namespace Ranine\Tests\Helper\Drupal;

use Drupal\user\Entity\User;
use Drupal\user\UserInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;
use Ranine\Exception\InvalidOperationException;
use Ranine\Helper\Drupal\UserHelpers;
use Ranine\Iteration\ExtendableIterable;
use Ranine\Testing\Drupal\Traits\MockEntityTypeManagerCreationTrait;

#[TestDox('Tests the UserHelpers class.')]
#[CoversClass(UserHelpers::class)]
#[Group('ranine')]
class UserHelpersTest extends TestCase {

  use MockEntityTypeManagerCreationTrait;

  #[TestDox('Tests the getAttributeHash() method.')]
  #[CoversFunction('getAttributeHash')]
  public function testGetAttributeHash() : void {
    // Create a binary representation retrieval that handles the email, user
    // status, and user timezone attributes.
    $binaryRepresentationRetrieval = function (UserInterface $user, string $attributeType) {
      assert($user instanceof User);
      return match ($attributeType) {
        'email' => $user->getEmail() ?? '\0',
        'status' => $user->isActive() ? 'A' : 'B',
        'timezone' => $user->getTimeZone(),
        default => throw new \InvalidArgumentException('Invalid attribute type requested.'),
      };
    };

    // Create a few mock users.
    $users = new \SplFixedArray(3);
    $users[0] = $this->getMockUser(2, 'eve@example.com', TRUE, "\eE\x1Dv\x1E\e\ee");
    $users[1] = $this->getMockUser(3, 'cain@example.com', FALSE, 'pst');
    $users[2] = $this->getMockUser(5, 'abel@example.com', FALSE, 'pst');
    /** @var \ArrayAccess<int, \Drupal\user\Entity\User>&iterable<int, \Drupal\user\Entity\User> $users */

    // Create a mock entity type manager for the three users.
    $mockEntityTypeManager = $this->getMockEntityTypeManager(['user' => [
      'storage_interface' => '\\Drupal\\user\\UserStorageInterface',
      'entities' => ExtendableIterable::from($users)
        ->map(fn($i, $u) => $u, fn($i, User $u) => (int) $u->id())
        ->toArray(),
    ]]);
    // We also want to define the getEntityType() method on the mock storage to
    // handle static caching stuff.
    $mockEntityType = $this->createMock('\\Drupal\\Core\\Entity\\EntityTypeInterface');
    $mockEntityType->method('isStaticallyCacheable')->willReturn(TRUE);
    /** @var \PHPUnit\Framework\MockObject\MockObject&\Drupal\user\UserStorageInterface */
    $mockStorage = $mockEntityTypeManager->getStorage('user');
    $mockStorage->method('getEntityType')->willReturn($mockEntityType);
    $mockStorage->method('resetCache')->willReturnCallback(function () : void {});

    // Compute the expected binary representation with the email, status, and
    // timezone. Skip UID = 3. Note:
    // User separator = ASCII group sep = 0x1D
    // Attribute separator = ASCII record sep = 0x1E
    $binaryRepresentation =
      'eve@example.com' . "\x1E" . 'A' . "\x1E" . "\e\eE\e\x1Dv\e\x1E\e\e\e\ee" . "\x1D" .
      "\x1D" .
      'abel@example.com' . "\x1E" . 'B' . "\x1E" . 'pst';
    $expectedHash = hash('sha1', $binaryRepresentation, TRUE);

    $hash = UserHelpers::getAttributeHash($mockStorage,
      [2, 3, 5],
      ['email', 'status', 'timezone'],
      $binaryRepresentationRetrieval,
      fn(UserInterface $user) => $user->id() === 3 ? FALSE : TRUE);

    $this->assertTrue($hash === $expectedHash);
  }

  /**
   * Creates and returns a mock \Drupal\user\Entity\User object.
   *
   * The returned object defins the following methods:
   * - id() -- returns $uid
   * - getEmail() -- returns $email
   * - isActive() -- returns $isActive
   * - isBlocked() -- returns !$isActive
   * - isAuthenticated() -- returns TRUE
   * - isAnonymous() -- returns FALSE
   * - getTimeZone() -- returns $timezone
   * - uuid() - returns $uuid
   * - getDisplayName() - returns $displayName
   * - getPreferredLangcode(FALSE) - returns $preferredLangcode
   * - getPreferredAdminLangcode(FALSE) - returns $preferredAdminLangcode
   *
   * @param int $uid
   *   User ID (positive number).
   * @param string|null $email
   *   Email, or NULL for no email.
   * @param bool $isActive
   *   User status.
   * @param string $timezone
   *   User timezone.
   * @param string $uuid
   *   UUID.
   * @param string $displayName
   *   Display name.
   * @param string $preferredLangcode
   *   Preferred langcode.
   * @param string $preferredAdminLangcode
   *   Preferred admin langcode.
   */
  private function getMockUser(int $uid,
    ?string $email = NULL,
    bool $isActive = TRUE,
    string $timezone = '',
    string $uuid = '',
    string $displayName = '',
    string $preferredLangcode = '',
    string $preferredAdminLangcode = '') : MockObject&User {
    $mockUser = $this->createMockNoAutoMethodConfig('\\Drupal\\user\\Entity\\User');
    $mockUser->method('id')->willReturn($uid);
    $mockUser->method('getEmail')->willReturn($email);
    $mockUser->method('isActive')->willReturn($isActive);
    $mockUser->method('isBlocked')->willReturn(!$isActive);
    $mockUser->method('isAuthenticated')->willReturn(TRUE);
    $mockUser->method('isAnonymous')->willReturn(FALSE);
    $mockUser->method('getTimeZone')->willReturn($timezone);
    $mockUser->method('uuid')->willReturn($uuid);
    $mockUser->method('getDisplayName')->willReturn($displayName);
    $mockUser->method('getPreferredAdminLangcode')->willReturnCallback(function (bool $fallback_to_default) use ($preferredAdminLangcode) : string {
      if ($fallback_to_default) {
        throw new InvalidOperationException('Method functionality not implemented.');
      }
      else {
        return $preferredAdminLangcode;
      }
    });
    $mockUser->method('getPreferredLangcode')->willReturnCallback(function (bool $fallback_to_default) use ($preferredLangcode) : string {
      if ($fallback_to_default) {
        throw new InvalidOperationException('Method functionality not implemented.');
      }
      else {
        return $preferredLangcode;
      }
    });

    return $mockUser;
  }

}
