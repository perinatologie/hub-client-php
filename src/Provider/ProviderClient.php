<?php

namespace Hub\Client\Provider;

use Hub\Client\Model\Resource;
use Hub\Client\Model\Property;
use Hub\Client\Model\Source;
use Hub\Client\Common\ErrorResponseHandler;
use GuzzleHttp\Client as GuzzleClient;
use RuntimeException;
use SimpleXMLElement;

class ProviderClient
{
    private $username;
    private $password;
    private $httpClient;

    public function __construct($username = null, $password = null)
    {
        $this->username = $username;
        $this->password = $password;
        $this->httpClient = new GuzzleClient();

        $this->verify = __DIR__ . '/../../cacert.pem';
        if (!file_exists($this->verify)) {
            throw new RuntimeException('cacert.pem not found: ' . $this->verify);
        }
    }

    public function getResourceData(Resource $resource, Source $source, $accept = null)
    {
        switch ($source->getApi()) {
            case 'v1':
                return $this->getResourceDataV1($resource, $source);
                break;
            case 'v3':
                return $this->getResourceDataV3($source, $accept);
            default:
                throw new RuntimeException("Unsupported source API: " . $source->getApi());
        }
    }

    private function getResourceDataV1(Resource $resource, Source $source)
    {
        if ($source->getUrl()) {
            $url = $source->getUrl();
        } else {
            $providerApiUrl = $resource->getPropertyValue('provider_apiurl');
            if (!$providerApiUrl) {
                throw new RuntimeException("No v1 provider_apiurl to get resource data");
            }
            $bsn = $resource->getPropertyValue('client_bsn');
            $ref = $resource->getPropertyValue('reference');

            switch ($resource->getType()) {
                case 'perinatologie/dossier':
                    $url = $providerApiUrl . '/' . $bsn . '/' . rawurlencode($ref);
                    break;
                default:
                    throw new RuntimeException("Unsupported resource type: " . $resource->getType());
            }
        }

        try {
            $hashSource = $url . $this->password;

            $headers = array();
            //echo "REQUESTING PROVIDER URL: " . $url . "\n";
            if ($this->username || $this->password) {
                // stripping http(s) because of load-balancer. Hub sees http only
                $securityHash = sha1(str_replace('https', 'http', $hashSource));
                $headers = [
                    'uuid' => $this->username,
                    'securityhash' => $securityHash
                ];
            }
            //echo "REQUESTING: " . $fullUrl . "\n";
            $res = $this->httpClient->get(
                $url,
                [
                    'headers' => $headers,
                    'verify' => $this->verify
                ]
            );
            if ($res->getStatusCode() == 200) {
                $res = (string)$res->getBody();
                return $res;
            }
        } catch (\GuzzleHttp\Exception\RequestException $e) {
             ErrorResponseHandler::handle($e->getResponse());
        }
    }

    private function getResourceDataV3(Source $source, $accept = null)
    {
        $url = $source->getUrl();
        $jwt = $source->getJwt();
        if (!$url) {
            throw new RuntimeException("No source URL to get resource data");
        }
        if (!$jwt) {
            //throw new RuntimeException("No source JWT to get resource data");
        }
        if ($jwt) {
            $url .= "?jwt=". $jwt;
        }
        if ($accept) {
            $url .= '&accept=' . $accept;
        }
        //echo "REQUESTING: $url\n";

        try {
            $res = $this->httpClient->get(
                $url,
                [
                    'headers' => [],
                    'verify' => $this->verify
                ]
            );
            if ($res->getStatusCode() == 200) {
                $res = (string)$res->getBody();
                return $res;
            }
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            ErrorResponseHandler::handle($e->getResponse());
        }
    }
}
