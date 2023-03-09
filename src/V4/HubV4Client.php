<?php

namespace Hub\Client\V4;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Stream\Stream;
use Hub\Client\Common\ErrorResponseHandler;
use Hub\Client\Exception\NoResponseException;
use Hub\Client\V3\HubV3Client;
use RuntimeException;

/**
 * Hub v4 API client extends the v3 client and uses different means of
 * authentication.
 */
class HubV4Client extends HubV3Client
{
    /**
     * @param \GuzzleHttp\ClientInterface $httpClient
     */
    public function __construct(ClientInterface $httpClient)
    {
        $this->url = '/v4';
        $this->httpClient = $httpClient;
    }

    protected function sendRequest($uri, $postData = null)
    {
        $requestOpts = [];
        if ($postData) {
            $requestOpts['body'] = Stream::factory($postData);
        }

        try {
            $response = $this->httpClient->get($this->url . $uri, $requestOpts);
        } catch (BadResponseException $e) {
            if (!$e->hasResponse()) {
                throw new NoResponseException(
                    'NO_RESPONSE',
                    "Hub v4 API did not respond to a request for '{$this->url}{$uri}'."
                );
            }
            ErrorResponseHandler::handle($e->getResponse());
        } catch (GuzzleException $e) {
            throw new RuntimeException(
                "Hub v4 API responded in an unexpected way to a request for '{$this->url}{$uri}'.",
                null,
                $e
            );
        }

        return (string) $response->getBody();
    }
}
