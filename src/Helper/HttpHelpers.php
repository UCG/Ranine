<?php

declare(strict_types = 1);

namespace Ranine\Helper;

/**
 * Indicates the result of checking if a Content-Type header indicates JSON.
 */
enum ContentTypeJsonCheckResult {

  /**
   * Indicates a valid Content-Type header indicating JSON content.
   */
  case Json;

  /**
   * Indicates a malformed Content-Type header.
   */
  case Malformed;

  /**
   * Indicates a Content-Type header indicating non-JSON data.
   */
  case NonJson;

}

/**
 * Static helper methods to deal with HTTP stuff.
 *
 * @static
 */
final class HttpHelpers {

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
   * @return \Ranine\Helper\ContentTypeJsonCheckResult
   *   Whether header indicates JSON data, is malformed, or does not indicate
   *   JSON data.
   */
  public static function checkJsonContentTypeHeader(string $contentTypeHeader) : ContentTypeJsonCheckResult {
    $contentTypeParts = explode(';', $contentTypeHeader);
    /** @phpstan-ignore-next-line */
    assert(is_array($contentTypeParts) && $contentTypeParts !== []);
    $numContentTypeParts = count($contentTypeParts);
    if ($numContentTypeParts > 2) {
      return ContentTypeJsonCheckResult::Malformed;
    }
    if (strcasecmp(trim($contentTypeParts[0]), 'application/json') !== 0) {
      return ContentTypeJsonCheckResult::NonJson;
    }
    if ($numContentTypeParts > 1) {
      $secondContentTypePart = trim($contentTypeParts[1]);
      if ($secondContentTypePart !== '') {
        $charsetParts = explode('=', $secondContentTypePart);
        /** @phpstan-ignore-next-line */
        assert(is_array($charsetParts) && $charsetParts !== []);
        // In this case, this parameter should indicate a UTF-8 or US-ASCII
        // character set.
        if (count($charsetParts) !== 2) {
          return ContentTypeJsonCheckResult::Malformed;
        }
        if (strcasecmp(trim($charsetParts[0]), 'charset') !== 0) {
          return ContentTypeJsonCheckResult::Malformed;
        }
        $characterSet = trim($charsetParts[1]);
        if (strcasecmp($characterSet, 'utf-8') !== 0 && strcasecmp($characterSet, 'us-ascii') !== 0) {
          return ContentTypeJsonCheckResult::NonJson;
        }
      }
    }

    return ContentTypeJsonCheckResult::Json;
  }

  /**
   * Tries to get "Basic" HTTP auth credentials from corresponding header.
   *
   * @param string $authorizationHeader
   *   The contents of the "Authorization" header.
   *
   * @param string $username
   *   (output) Non-empty username (undefined if header parsing
   *   failed).
   * @param string $password
   *   (output) Password (undefined if header parsing failed).
   * @phpstan-param non-empty-string $username
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
