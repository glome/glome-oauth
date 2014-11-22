<?php

namespace Glome\ApiBundle\Lib;

use OAuth2\OAuth2;
use OAuth2\Model\IOAuth2Client;
use OAuth2\Model\IOAuth2AuthCode;
use OAuth2\OAuth2ServerException as OAuth2ServerException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Glome\ApiBundle\Controller\SecurityController;

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
     * Returns HTTP headers for JSON.
     *
     * @see http://tools.ietf.org/html/draft-ietf-oauth-v2-20#section-5.1
     * @see http://tools.ietf.org/html/draft-ietf-oauth-v2-20#section-5.2
     *
     * @ingroup oauth2_section_5
     */
    public function getJsonHeaders() {
        return array(
            'Content-Type' => 'application/json',
            'Cache-Control' => 'no-store',
            'Pragma' => 'no-cache',
        );
    }
    
    // Access token granting (Section 4).

    /**
     * Grant or deny a requested access token.
     * This would be called from the "/token" endpoint as defined in the spec.
     * Obviously, you can call your endpoint whatever you want.
     *
     * @param $inputData - The draft specifies that the parameters should be
     * retrieved from POST, but you can override to whatever method you like.
     * @throws OAuth2ServerException
     *
     * @see http://tools.ietf.org/html/draft-ietf-oauth-v2-20#section-4
     * @see http://tools.ietf.org/html/draft-ietf-oauth-v2-21#section-10.6
     * @see http://tools.ietf.org/html/draft-ietf-oauth-v2-21#section-4.1.3
     *
     * @ingroup oauth2_section_4
     */
    public function grantAccessToken(Request $request = NULL) {
        $filters = array(
            "grant_type" => array("filter" => FILTER_VALIDATE_REGEXP, "options" => array("regexp" => self::GRANT_TYPE_REGEXP), "flags" => FILTER_REQUIRE_SCALAR),
            "scope" => array("flags" => FILTER_REQUIRE_SCALAR),
            "code" => array("flags" => FILTER_REQUIRE_SCALAR),
            "redirect_uri" => array("filter" => FILTER_SANITIZE_URL),
            "username" => array("flags" => FILTER_REQUIRE_SCALAR),
            "password" => array("flags" => FILTER_REQUIRE_SCALAR),
            "refresh_token" => array("flags" => FILTER_REQUIRE_SCALAR),
        );

        if ($request === NULL) {
            $request = Request::createFromGlobals();
        }

        // Input data by default can be either POST or GET
        if ($request->getMethod() === 'POST') {
            $inputData = $request->request->all();
        } else {
            $inputData = $request->query->all();
        }

        // Basic authorization header
        $authHeaders = $this->getAuthorizationHeader($request);

        // Filter input data
        $input = filter_var_array($inputData, $filters);

        // Grant Type must be specified.
        if (!$input["grant_type"]) {
            throw new OAuth2ServerException(self::HTTP_BAD_REQUEST, self::ERROR_INVALID_REQUEST, 'Invalid grant_type parameter or parameter missing');
        }

        // Authorize the client
        $clientCreds = $this->getClientCredentials($inputData, $authHeaders);

        $client = $this->storage->getClient($clientCreds[0]);

        if (!$client) {
            throw new OAuth2ServerException(self::HTTP_BAD_REQUEST, self::ERROR_INVALID_CLIENT, 'The client credentials are invalid');
        }

        if ($this->storage->checkClientCredentials($client, $clientCreds[1]) === FALSE) {
            throw new OAuth2ServerException(self::HTTP_BAD_REQUEST, self::ERROR_INVALID_CLIENT, 'The client credentials are invalid');
        }

        if (!$this->storage->checkRestrictedGrantType($client, $input["grant_type"])) {
            throw new OAuth2ServerException(self::HTTP_BAD_REQUEST, self::ERROR_UNAUTHORIZED_CLIENT, 'The grant type is unauthorized for this client_id');
        }

        // Do the granting
        switch ($input["grant_type"]) {
            case self::GRANT_TYPE_AUTH_CODE:
                $stored = $this->grantAccessTokenAuthCode($client, $input); // returns array('data' => data, 'scope' => scope)
                break;
            case self::GRANT_TYPE_USER_CREDENTIALS:
                $stored = $this->grantAccessTokenUserCredentials($client, $input); // returns: true || array('scope' => scope)
                break;
            case self::GRANT_TYPE_CLIENT_CREDENTIALS:
                $stored = $this->grantAccessTokenClientCredentials($client, $input, $clientCreds); // returns: true || array('scope' => scope)
                break;
            case self::GRANT_TYPE_REFRESH_TOKEN:
                $stored = $this->grantAccessTokenRefreshToken($client, $input); // returns array('data' => data, 'scope' => scope)
                break;
            default:
                if (filter_var($input["grant_type"], FILTER_VALIDATE_URL)) {
                    $stored = $this->grantAccessTokenExtension($client, $inputData, $authHeaders); // returns: true || array('scope' => scope)
                } else {
                    throw new OAuth2ServerException(self::HTTP_BAD_REQUEST, self::ERROR_INVALID_REQUEST, 'Invalid grant_type parameter or parameter missing');
                }
        }

        if (!is_array($stored)) {
            $stored = array();
        }

        // if no scope provided to check against $input['scope'] then application defaults are set
        // if no data is provided than null is set
        $stored += array('scope' => $this->getVariable(self::CONFIG_SUPPORTED_SCOPES, null), 'data' => null);

        // Check scope, if provided
        if ($input["scope"] && (!isset($stored["scope"]) || !$this->checkScope($input["scope"], $stored["scope"]))) {
            throw new OAuth2ServerException(self::HTTP_BAD_REQUEST, self::ERROR_INVALID_SCOPE, 'An unsupported scope was requested.');
        }

        $token = $this->createAccessToken($client, $stored['data'], $stored['scope']);

        return new Response(json_encode($token), 200, $this->getJsonHeaders());
    }

    protected function grantAccessTokenAuthCode(IOAuth2Client $client, array $input) {

        if (!($this->storage instanceof IOAuth2GrantCode)) {
            throw new OAuth2ServerException(self::HTTP_BAD_REQUEST, self::ERROR_UNSUPPORTED_GRANT_TYPE);
        }

        if (!$input["code"]) {
            throw new OAuth2ServerException(self::HTTP_BAD_REQUEST, self::ERROR_INVALID_REQUEST, 'Missing parameter. "code" is required');
        }

        if ($this->getVariable(self::CONFIG_ENFORCE_INPUT_REDIRECT) && !$input["redirect_uri"]) {
            throw new OAuth2ServerException(self::HTTP_BAD_REQUEST, self::ERROR_INVALID_REQUEST, "The redirect URI parameter is required.");
        }

        $authCode = $this->storage->getAuthCode($input["code"]);

        // Check the code exists
        if ($authCode === NULL || $client->getPublicId() !== $authCode->getClientId()) {
            throw new OAuth2ServerException(self::HTTP_BAD_REQUEST, self::ERROR_INVALID_GRANT, "Code doesn't exist or is invalid for the client");
        }

        // Validate the redirect URI. If a redirect URI has been provided on input, it must be validated
        if ($input["redirect_uri"] && !$this->validateRedirectUri($input["redirect_uri"], $authCode->getRedirectUri())) {
            throw new OAuth2ServerException(self::HTTP_BAD_REQUEST, self::ERROR_REDIRECT_URI_MISMATCH, "The redirect URI is missing or do not match");
        }

        if ($authCode->hasExpired()) {
            throw new OAuth2ServerException(self::HTTP_BAD_REQUEST, self::ERROR_INVALID_GRANT, "The authorization code has expired");
        }

        $this->usedAuthCode = $authCode;

        return array(
            'scope' => $authCode->getScope(),
            'data' => $authCode->getData(),
        );
    }

    protected function grantAccessTokenUserCredentials(IOAuth2Client $client, array $input) {
        /*
         * N/A
        if (!($this->storage instanceof IOAuth2GrantUser)) {
            throw new OAuth2ServerException(self::HTTP_BAD_REQUEST, self::ERROR_UNSUPPORTED_GRANT_TYPE);
        }
        */
        if (!$input["username"] || !$input["password"]) {
            //throw new OAuth2ServerException(self::HTTP_BAD_REQUEST, self::ERROR_INVALID_REQUEST, 'Missing parameters. "username" and "password" required');

        }

        // TODO: Documentation
        $stored = $this->storage->checkUserCredentials($client, $input["username"], $input["password"]);

        if ($stored === FALSE) {
            throw new OAuth2ServerException(self::HTTP_BAD_REQUEST, self::ERROR_INVALID_GRANT);
        }

        return $stored;
    }

    protected function grantAccessTokenClientCredentials(IOAuth2Client $client, array $input, array $clientCredentials) {
        if (!($this->storage instanceof IOAuth2GrantClient)) {
            throw new OAuth2ServerException(self::HTTP_BAD_REQUEST, self::ERROR_UNSUPPORTED_GRANT_TYPE);
        }

        if (empty($clientCredentials[1])) {
            throw new OAuth2ServerException(self::HTTP_BAD_REQUEST, self::ERROR_INVALID_CLIENT, 'The client_secret is mandatory for the "client_credentials" grant type');
        }

        $stored = $this->storage->checkClientCredentialsGrant($client, $clientCredentials[1]);

        if ($stored === FALSE) {
            throw new OAuth2ServerException(self::HTTP_BAD_REQUEST, self::ERROR_INVALID_GRANT);
        }

        return $stored;
    }

    protected function grantAccessTokenRefreshToken(IOAuth2Client $client, array $input) {
        if (!($this->storage instanceof IOAuth2RefreshTokens)) {
            throw new OAuth2ServerException(self::HTTP_BAD_REQUEST, self::ERROR_UNSUPPORTED_GRANT_TYPE);
        }

        if (!$input["refresh_token"]) {
            throw new OAuth2ServerException(self::HTTP_BAD_REQUEST, self::ERROR_INVALID_REQUEST, 'No "refresh_token" parameter found');
        }

        $token = $this->storage->getRefreshToken($input["refresh_token"]);

        if ($token === NULL || $client->getPublicId() !== $token->getClientId()) {
            throw new OAuth2ServerException(self::HTTP_BAD_REQUEST, self::ERROR_INVALID_GRANT, 'Invalid refresh token');
        }

        if ($token->hasExpired()) {
            throw new OAuth2ServerException(self::HTTP_BAD_REQUEST, self::ERROR_INVALID_GRANT, 'Refresh token has expired');
        }

        // store the refresh token locally so we can delete it when a new refresh token is generated
        $this->oldRefreshToken = $token->getToken();

        return array(
            'scope' => $token->getScope(),
            'data' => $token->getData(),
        );
    }

    protected function grantAccessTokenExtension(IOAuth2Client $client, array $inputData, array $authHeaders) {
        if (!($this->storage instanceof IOAuth2GrantExtension)) {
            throw new OAuth2ServerException(self::HTTP_BAD_REQUEST, self::ERROR_UNSUPPORTED_GRANT_TYPE);
        }
        $uri = filter_var($inputData["grant_type"], FILTER_VALIDATE_URL);
        $stored = $this->storage->checkGrantExtension($client, $uri, $inputData, $authHeaders);

        if ($stored === FALSE) {
            throw new OAuth2ServerException(self::HTTP_BAD_REQUEST, self::ERROR_INVALID_GRANT);
        }

        return $stored;
    }


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
