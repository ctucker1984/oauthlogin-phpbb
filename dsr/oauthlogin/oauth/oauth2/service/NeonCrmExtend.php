<?php
/**
 *
 * Extend OAuth login. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2023, DSR! https://github.com/xchwarze
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace OAuth\OAuth2\Service;

use OAuth\OAuth2\Token\StdOAuth2Token;
use OAuth\Common\Http\Exception\TokenResponseException;
use OAuth\Common\Http\Uri\Uri;
use OAuth\Common\Consumer\CredentialsInterface;
use OAuth\Common\Http\Client\ClientInterface;
use OAuth\Common\Storage\TokenStorageInterface;
use OAuth\Common\Http\Uri\UriInterface;

class NeonCrmExtend extends AbstractService
{
    /**
     * Scope list
     * @see https://developer.wordpress.com/docs/oauth2/
     */
    const SCOPE_AUTH= 'auth';

    public function __construct(
        CredentialsInterface  $credentials,
        ClientInterface       $httpClient,
        TokenStorageInterface $storage,
                              $scopes = array(),
        UriInterface          $baseApiUri = null
    ) {
        parent::__construct($credentials, $httpClient, $storage, $scopes, $baseApiUri, true);

        if (null === $baseApiUri) {
            $this->baseApiUri = new Uri('https://api.neoncrm.com/v2/');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function service()
    {
        return 'Wordpress';
    }

    /**
     * {@inheritdoc}
     */
    public function getAuthorizationEndpoint()
    {
        /**
        * https://{{org_id}}.app.neoncrm.com/np/oauth/auth?response_type=code&client_id={{client_id}}&redirect_uri={{redirect_uri}}
        */
        $uri    =   'https://' . $this->config['auth_oauth_neoncrm_key'];
        $uri    .=  '.app.neoncrm.com/np/oauth/auth?response_type=code';
        $uri    .=  '&client_id=' . $this->config['auth_oauth_neoncrm_secret'];
        $uri    .=  '&redirect_uri=' . 'https://phpbb.teamautomation.com/ucp.php?mode=login&redirect=index.php';
        return new Uri( $uri );
    }

    /**
     * {@inheritdoc}
     */
    public function getAccessTokenEndpoint()
    {
        return new Uri('https://app.neoncrm.com/np/oauth/token/');
    }

    /**
     * {@inheritdoc}
     */
    protected function getAuthorizationMethod()
    {
        return static::AUTHORIZATION_METHOD_HEADER_BEARER;
    }

    /**
     * {@inheritdoc}
     */
    protected function parseAccessTokenResponse($responseBody)
    {
        // all logic from: src/OAuth/OAuth2/Service/DeviantArt.php
        $data = json_decode($responseBody, true);

        if (null === $data || !is_array($data)) {
            throw new TokenResponseException('Unable to parse response.');
        } elseif (isset($data['error'])) {
            throw new TokenResponseException('Error in retrieving token: "' . $data['error'] . '"');
        }

        $token = new StdOAuth2Token();
        $token->setAccessToken($data['access_token']);
        unset($data['access_token']);

        if (isset($data['expires_in'])) {
            $token->setLifeTime($data['expires_in']);
            unset($data['expires_in']);
        }

        if (isset($data['refresh_token'])) {
            $token->setRefreshToken($data['refresh_token']);
            unset($data['refresh_token']);
        }

        $token->setExtraParams($data);

        return $token;
    }
}
