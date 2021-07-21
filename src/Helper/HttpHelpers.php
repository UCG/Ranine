<?php

declare(strict_types = 1);

namespace Ranine\Helper;

/**
 * Static helper methods to deal with HTTP stuff.
 *
 * @static
 */
final class HttpHelpers {

  /**
   * Indicates a malformed Content-Type header.
   */
  public const CONTENT_TYPE_HEADER_MALFORMED = 1;

  /**
   * Indicates a Content-Type header indicating non-JSON data.
   */
  public const CONTENT_TYPE_HEADER_NON_JSON = 2;

  /**
   * Indicates a valid Content-Type header.
   */
  public const CONTENT_TYPE_HEADER_VALID = 3;

  /**
   * Empty private constructor to ensure no one instantiates this class.
   */
  private function __construct() {
  }

  /**
   * Checks if the given Content-Type header indicates JSON data.
   *
   * @param string $contentTypeHeader
   *   Content type header value to check.
   *
   * @return int
   *   Status code; one of static::CONTENT_TYPE_HEADER_MALFORMED,
   *   static::CONTENT_TYPE_HEADER_NOT_JSON, or
   *   static::CONTENT_TYPE_HEADER_VALID. The meanings of the codes should be
   *   self-evident :)
   */
  public static function checkJsonContentTypeHeader(string $contentTypeHeader) : int {
    $contentTypeParts = explode(';', $contentTypeHeader);
    assert(is_array($contentTypeParts));
    if (empty($contentTypeParts)) {
      return static::CONTENT_TYPE_HEADER_MALFORMED;
    }
    $numContentTypeParts = count($contentTypeParts);
    if ($numContentTypeParts > 2) {
      return static::CONTENT_TYPE_HEADER_MALFORMED;
    }
    if (strcasecmp(trim($contentTypeParts[0]), 'application/json') !== 0) {
      return static::CONTENT_TYPE_HEADER_NON_JSON;
    }
    if ($numContentTypeParts > 1) {
      $secondContentTypePart = trim($contentTypeParts[1]);
      if ($secondContentTypePart !== '') {
        $charsetParts = explode('=', $secondContentTypePart);
        if (!empty($charsetParts)) {
          // In this case, this parameter should indicate a UTF-8 or US-ASCII
          // character set.
          if (count($charsetParts) !== 2) {
            return static::CONTENT_TYPE_HEADER_MALFORMED;
          }
          if (strcasecmp(trim($charsetParts[0]), 'charset') !== 0) {
            return static::CONTENT_TYPE_HEADER_MALFORMED;
          }
          $characterSet = trim($charsetParts[1]);
          if (strcasecmp($characterSet, 'utf-8') !== 0 && strcasecmp($characterSet, 'us-ascii') !== 0) {
            return static::CONTENT_TYPE_HEADER_NON_JSON;
          }
        }
      }
    }

    return static::CONTENT_TYPE_HEADER_VALID;
  }

  /**
   * Tries to get "Basic" HTTP auth credentials from corresponding header.
   *
   * @param string $authorizationHeader
   *   The contents of the "Authorization" header.
   *
   * @param string $username
   *   Non-empty username (undefined if header parsing failed).
   * @param string $password
   *   Password (undefined if header parsing failed).
   *
   * @return bool
   *   Returns TRUE if the username and password were successfully parsed;
   *   returns FALSE if the header was malformed.
   */
  public static function tryGetBasicAuthCredentials(string $authorizationHeader, string &$username, string &$password) : bool {
     // Trim the header, and ensure it is long enough and starts with "Basic".
    $authorizationInfo = trim($authorizationHeader);
    if (strlen($authorizationHeader) < 7) {
      return FALSE;
    }
    if (substr_compare($authorizationInfo, 'Basic ', 0, 6) !== 0) {
      return FALSE;
    }

    // Grab the part of the header after "Basic ".
    $encodedCredentials = trim(substr($authorizationInfo, 6));
    if ($encodedCredentials === '') {
      return FALSE;
    }
    // Decode the credentials
    $credentials = base64_decode($encodedCredentials, TRUE);
    if (!StringHelpers::isNonEmptyString($credentials)) {
      return FALSE;
    }
    // Grab the username and password.
    $credentialsParts = explode(':', $credentials, 2);
    if (count($credentialsParts) !== 2) {
      return FALSE;
    }
    $username = $credentialsParts[0];
    if ($username === '') {
      return FALSE;
    }
    $password = $credentialsParts[1];

    return TRUE;
  }

}
