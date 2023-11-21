<?php

declare (strict_types = 1);

namespace Ranine\Helper\Drupal;

use Drupal\user\UserStorageInterface;
use Ranine\Helper\StringHelpers;
use Ranine\Helper\ThrowHelpers;
use Ranine\Iteration\ExtendableIterable;

/**
 * Contains Drupal-related helper methods dealing with Drupal users.
 */
final class UserHelpers {

  /**
   * Empty private constructor to ensure no one instantiates this class.
   */
  private function __construct() {
  }

  /**
   * Gets the SHA-1 hash of a given set of attributes for a given set of users.
   *
   * @param \Drupal\user\UserStorageInterface $userStorage
   *   User storage.
   * @param iterable<int> $sortedUids
   *   UIDs in sorted (low to high) order with no duplicates.
   * @param string[] $sortedAttributeTypes
   *   Attribute types in sorted (low to high) binary order with no duplicates.
   * @param callable(\Drupal\user\UserInterface $user, string $attributeType) : string $binaryAttributeRepresentationRetrieval
   *   Gets the binary attribute representation for a given user.
   * @param callable(\Drupal\user\UserInterface) : bool $userFilter
   *   Filters users out of the hash. Returns TRUE if a user should be included
   *   in the normal hash computation, or FALSE if the user's binary
   *   representation should just be the empty string.
   * @param int $userLoadPageSize
   *   The maximum number of users to try to load at once. Can be used to ease
   *   memory burdens.
   * @phpstan-param positive-int $userLoadPageSize
   *
   * @return string
   *   SHA-1 hash of a string of the following form:
   *   {serialization of first attribute type of first user}:
   *   {serialization of second attribute type of first user}:...
   *   |{serialization of first attribute type of second user}:...
   *   Here ":" represents the ASCII record separator and "|" represents the
   *   ASCII group separator. These separators are escaped from the
   *   serializations with the ASCII ESC character (which is also used to escape
   *   the ESC character itself). The attribute type serializations are ordered
   *   in an ascending sorted fashion (higher binary strings later on), with
   *   duplicate attribute types removed. The users are also ordered in an
   *   ascending fashion in the string above, with duplicate users removed.
   *   If a user does not exist or it doesn't pass through the fitler, its
   *   serialization string is the empty string.
   *
   * @throws \InvalidArgumentException
   *   Thrown if $sortedAttributeTypes contains a value that is not a string.
   * @throws \InvalidArgumentException
   *   Thrown if $sortedAttributeTypes is not in sorted order without
   *   duplicates.
   * @throws \InvalidArgumentException
   *   Thrown if $sortedUids is not in sorted order without duplicates.
   * @throws \InvalidArgumentException
   *   Thrown if $sortedUids contains a value that is not a non-negative
   *   integer.
   * @throws \InvalidArgumentException
   *   Thrown if $userLoadPageSize is less than or equal to zero.
   * @throws \LogicException
   *   Thrown if $binaryAttributeRepresentationRetrieval does not return a
   *   string.
   */
  public static function getAttributeHash(UserStorageInterface $userStorage,
    iterable $sortedUids,
    array $sortedAttributeTypes,
    callable $binaryAttributeRepresentationRetrieval,
    callable $userFilter,
    int $userLoadPageSize = 10000) : string {
    ThrowHelpers::throwIfLessThanOrEqualToZero($userLoadPageSize, 'userLoadPageSize');
    $lastAttributeType = NULL;
    foreach ($sortedAttributeTypes as $attributeType) {
      if (!is_string($attributeType)) {
        throw new \InvalidArgumentException('$sortedAttributeTypes contains a value that is not a string.');
      }
      if ($lastAttributeType !== NULL && strcmp($lastAttributeType, $attributeType) >= 0) {
        throw new \InvalidArgumentException('$sortedAttributeTypes is not in sorted ascending order without duplicates.');
      }
      $lastAttributeType = $attributeType;
    }

    // Collect "pages" of UIDs to be loaded at once.
    /** @var \Ranine\Iteration\ExtendableIterable<int, int[]> */
    $pages = ExtendableIterable::from((function () use ($sortedUids, $userLoadPageSize) {
      $pageId = 0;
      $page = [];
      foreach ($sortedUids as $uid) {
        if (count($page) >= $userLoadPageSize) {
          yield $pageId => $page;
          $page = [];
          $pageId++;
        }
        $page[] = $uid;
      }
      yield $pageId => $page;
    })());

    // Create the hash context, used to compute a hash of a long string
    // without first loading the entire string into memory.
    $hashContext = hash_init('sha1');
    // We use the record and group separator to divide attributes and users,
    // respectively, so we want to escape these from attribute serializations.
    /** @var string[] */
    $specialCharacters = [StringHelpers::ASCII_RECORD_SEPARATOR, StringHelpers::ASCII_GROUP_SEPARATOR];

    // Loop through all the users and form the hash.
    $isFirstUser = TRUE;
    foreach ($pages as $page) {
      // Load all entities for this page.
      /** @var \Drupal\user\UserInterface[] */
      $users = $userStorage->loadMultiple($page);
      // Loop through the UIDs.
      foreach ($page as $uid) {
        if (!$isFirstUser) {
          hash_update($hashContext, StringHelpers::ASCII_GROUP_SEPARATOR);
        }

        if (!isset($users[$uid])) {
          goto next_user;            
        }
        $user = $users[$uid];
        if (!$userFilter($user)) {
          goto next_user;
        }

        $isFirstAttribute = TRUE;
        foreach ($sortedAttributeTypes as $attributeType) {
          if (!$isFirstAttribute) {
            hash_update($hashContext, StringHelpers::ASCII_RECORD_SEPARATOR);
          }
          $attributeSerialization = $binaryAttributeRepresentationRetrieval($user, $attributeType);
          if (!is_string($attributeSerialization)) {
            throw new \LogicException('The $binaryAttributeRepresentationRetrieval returned a non-string attribute serialization.');
          }
          $escapedAttributeSerialization = StringHelpers::escape($attributeSerialization, $specialCharacters);
          hash_update($hashContext, $escapedAttributeSerialization);
          $isFirstAttribute = FALSE;
        }

next_user:
        $isFirstUser = FALSE;
      }
    }

    return hash_final($hashContext, TRUE);
  }

}
