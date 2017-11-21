<?php

namespace Hub\Client\Factory;

use RuntimeException;

use Hub\Client\V3\HubV3Client;

class ApiClientFactory
{
    private $hubUrl;
    private $requestHeaders;
    private $tlsCertVerification;

    /**
     * @param string $hubUrl Base URL of Hub, e.g. http://hub.example.com
     * @param bool|string $tlsCertVerification as \GuzzleHttp\RequestOptions::VERIFY option
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
}
