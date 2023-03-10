<?php

namespace Hub\Client\Factory;

use GuzzleHttp\Client as GuzzleClient;
use Hub\Client\Exception\AuthenticationFailureException;
use Hub\Client\Exception\ClientCreationException;
use Hub\Client\Security\UserbaseJwtAuthenticatorClient;
use Hub\Client\V3\HubV3Client;
use Hub\Client\V4\HubV4Client;
use RuntimeException;

class ApiClientFactory
{
    private $hubUrl;
    private $requestHeaders;
    private $tlsCertVerification;
    private $userbaseUrl;

    /**
     * @param string $hubUrl Base URL of Hub, e.g. http://hub.example.com
     * @param bool|string $tlsCertVerification as \GuzzleHttp\RequestOptions::VERIFY option
     *
     * @paran array $requestHeaders default set of headers to be sent in requests
     *
     * @see \GuzzleHttp\RequestOptions::VERIFY
     *
     * @throws RuntimeException
     */
    public function __construct(
        $hubUrl,
        $tlsCertVerification = true,
        array $requestHeaders = []
    ) {
        $this->hubUrl = $hubUrl;
        if (is_string($tlsCertVerification)
            && (!file_exists($tlsCertVerification) || !is_file($tlsCertVerification))
        ) {
            throw new RuntimeException(
                "Cannot read TLS certificate bundle from '{$tlsCertVerification}'."
            );
        }
        $this->tlsCertVerification = $tlsCertVerification;
        $this->requestHeaders = $requestHeaders;
    }

    /**
     * Get a client for the v3 Hub API.
     *
     * @param string $username
     * @param string $password
     *
     * @return \Hub\Client\V3\HubV3Client
     */
    public function createV3Client($username, $password)
    {
        return new HubV3Client(
            $username,
            $password,
            $this->hubUrl,
            $this->requestHeaders,
            $this->tlsCertVerification
        );
    }

    /**
     * Get a client for the v4 Hub API.
     *
     * @param string $username
     * @param string $password
     *
     * @throws RuntimeException if setUserbaseJwtAuthenticatorUrl was not called
     * @throws \Hub\Client\Exception\ClientCreationException
     */
    public function createV4Client($username, $password)
    {
        if (!$this->userbaseUrl) {
            throw new RuntimeException(
                'In order to createV4Client you need to have previsously called ApiClientFactory::setUserbaseJwtAuthenticatorUrl'
            );
        }
        $authenticator = new UserbaseJwtAuthenticatorClient(
            $this->userbaseUrl,
            $this->tlsCertVerification
        );
        try {
            $token = $authenticator->authenticate($username, $password);
        } catch (AuthenticationFailureException $e) {
            throw new ClientCreationException(
                'Failed to create a v4 API client because of a failure during authentication with UserBase.',
                null,
                $e
            );
        }
        $httpClient = new GuzzleClient(
            [
                'base_uri' => $this->hubUrl,
                'verify' => $this->tlsCertVerification,
                'headers' => array_replace(
                    ['User-Agent' => 'HubV4Client (Guzzle)'],
                    $this->requestHeaders,
                    ['X-Authorization' => "Bearer {$token}"]
                ),
            ]
        );

        return new HubV4Client($httpClient);
    }

    /**
     * Set the URL of the UserBase JWT authentication endpoint.
     *
     * @param string $userbaseUrl URL, e.g. http://userbase.example.com/auth
     */
    public function setUserbaseJwtAuthenticatorUrl($userbaseUrl)
    {
        $this->userbaseUrl = $userbaseUrl;
    }
}
