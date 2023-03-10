<?php

namespace Hub\Client\Security;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Request;
use Hub\Client\Exception\AuthenticationFailureException;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;

/**
 * Obtain from UserBase a Json Web Token with which to authenticate with the Hub.
 */
class UserbaseJwtAuthenticatorClient
{
    private $tlsCertVerification;
    private $userbaseUrl;

    /**
     * @param string $userbaseUrl URL of UserBase JWT auth endpoint,
     *                            e.g. http://userbase.example.com
     * @param bool|string $tlsCertVerification as \GuzzleHttp\RequestOptions::VERIFY option
     *
     * @see \GuzzleHttp\RequestOptions::VERIFY
     *
     * @throws RuntimeException
     */
    public function __construct(
        $userbaseUrl,
        $tlsCertVerification = true
    ) {
        $this->userbaseUrl = $userbaseUrl;

        if (!is_bool($tlsCertVerification)
            && (!file_exists($tlsCertVerification) || !is_file($tlsCertVerification))
        ) {
            throw new RuntimeException(
                "Cannot read TLS certificate bundle from '{$tlsCertVerification}'."
            );
        }
        $this->tlsCertVerification = $tlsCertVerification;
    }

    /**
     * Authenticate with UserBase.
     *
     * The credentials given here will be used to request a Json Web Token,
     * claiming the username, from UserBase.
     *
     * @param string $username
     * @param string $password
     *
     * @return string
     *
     * @throws \Hub\Client\Exception\AuthenticationFailureException
     */
    public function authenticate($username, $password)
    {
        $authRequest = new Request('GET', $this->userbaseUrl);
        $client = new GuzzleClient(
            [
                'verify' => $this->tlsCertVerification,
                'headers' => [
                    'User-Agent' => 'HubUserbaseClient (Guzzle)',
                ],
                'auth' => [$username, $password],
            ]
        );
        try {
            $response = $client->send($authRequest);
        } catch (BadResponseException $e) {
            $this->handleBadResponseException($e);
        } catch (GuzzleException $e) {
            throw new AuthenticationFailureException(
                null,
                "The authentication request failed unexpectedly.",
                null,
                $e
            );
        }
        $json = $this->getResponseData($response);
        if (!$json) {
            throw new AuthenticationFailureException(
                $response,
                "Authentication failed because the authentication request did not result in the expected response data."
            );
        }
        if (!isset($json['jwt'])) {
            throw new AuthenticationFailureException(
                $response,
                "Authentication failed because an authentication token was not obtained."
            );
        }
        return $json['jwt'];
    }

    private function getResponseData(ResponseInterface $response)
    {
        try {
            $data = $response->getBody()->read(8196);
        } catch (RuntimeException $e) {
            return null;
        }
        if (empty($data)) {
            return null;
        }
        $json = json_decode($data, true);
        if (!$json) {
            return null;
        }

        return $json;
    }

    private function handleBadResponseException(BadResponseException $e)
    {
        if (!$e->hasResponse()) {
            throw new AuthenticationFailureException(
                null,
                "The authentication request failed with no response received.",
                null,
                $e
            );
        }
        $status = (string) $e->getResponse()->getStatusCode();
        $error = $this->getResponseBodyError($e->getResponse());
        if ($error) {
            $message = "The authentication request failed, resulting in an HTTP {$status} response with error '{$error}'.";
        } else {
            $message = "The authentication request failed, resulting in an HTTP {$status} response with no indication of the type of error.";
        }
        throw new AuthenticationFailureException(
            $e->getResponse(),
            $message,
            null,
            $e
        );
    }

    private function getResponseBodyError(ResponseInterface $response)
    {
        $data = $this->getResponseData($response);
        if (!isset($data['status']) || 'error' !== $data['status'] || !isset($data['code'])) {
            return null;
        }

        return $data['code'];
    }
}
