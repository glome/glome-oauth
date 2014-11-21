<?php

namespace Glome\ApiBundle\Lib;

use OAuth2\OAuth2;
use OAuth2\Model\IOAuth2Client;
use OAuth2\Model\IOAuth2AuthCode;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @mainpage
 * OAuth 2.0 server in PHP, originally written for
 * <a href="http://www.opendining.net/"> Open Dining</a>. Supports
 * <a href="http://tools.ietf.org/html/draft-ietf-oauth-v2-20">IETF draft v20</a>.
 *
 * Source repo has sample servers implementations for
 * <a href="http://php.net/manual/en/book.pdo.php"> PHP Data Objects</a> and
 * <a href="http://www.mongodb.org/">MongoDB</a>. Easily adaptable to other
 * storage engines.
 *
 * PHP Data Objects supports a variety of databases, including MySQL,
 * Microsoft SQL Server, SQLite, and Oracle, so you can try out the sample
 * to see how it all works.
 *
 * We're expanding the wiki to include more helpful documentation, but for
 * now, your best bet is to view the oauth.php source - it has lots of
 * comments.
 *
 * @author Tim Ridgely <tim.ridgely@gmail.com>
 * @author Aaron Parecki <aaron@parecki.com>
 * @author Edison Wong <hswong3i@pantarei-design.com>
 * @author David Rochwerger <catch.dave@gmail.com>
 *
 * @see http://code.google.com/p/oauth2-php/
 * @see https://github.com/quizlet/oauth2-php
 */

/**
 * OAuth2.0 draft v20 server-side implementation.
 *
 * @todo Add support for Message Authentication Code (MAC) token type.
 *
 * @author Originally written by Tim Ridgely <tim.ridgely@gmail.com>.
 * @author Updated to draft v10 by Aaron Parecki <aaron@parecki.com>.
 * @author Debug, coding style clean up and documented by Edison Wong <hswong3i@pantarei-design.com>.
 * @author Refactored (including separating from raw POST/GET) and updated to draft v20 by David Rochwerger <catch.dave@gmail.com>.
 */
class GlomeOAuth2 extends OAuth2{
    /**
     * Internal function used to get the client credentials from HTTP basic
     * auth or POST data.
     *
     * According to the spec (draft 20), the client_id can be provided in
     * the Basic Authorization header (recommended) or via GET/POST.
     *
     * @return
     *   A list containing the client identifier and password, for example
     * @code
     * return array(
     *   CLIENT_ID,
     *   CLIENT_SECRET
     * );
     * @endcode
     *
     * @see http://tools.ietf.org/html/draft-ietf-oauth-v2-20#section-2.4.1
     *
     * @ingroup oauth2_section_2
     */
    protected function getClientCredentials(array $inputData, array $authHeaders) {

        // Basic Authentication is used
        //if (!empty($authHeaders['PHP_AUTH_USER'])) {
        //    return array($authHeaders['PHP_AUTH_USER'], $authHeaders['PHP_AUTH_PW']);
        //}
        if (empty($inputData['client_id'])) { // No credentials were specified
            throw new OAuth2ServerException(self::HTTP_BAD_REQUEST, self::ERROR_INVALID_CLIENT, 'Client id was not found in the headers or body');
        }
        else {
            // This method is not recommended, but is supported by specification
            $client_id = $inputData['client_id'];
            $client_secret = isset($inputData['client_secret']) ? $inputData['client_secret'] : NULL;
            return array($client_id, $client_secret);
        }
    }
}
