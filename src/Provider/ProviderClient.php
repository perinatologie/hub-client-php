<?php

namespace Hub\Client\Provider;

use Hub\Client\Model\Resource;
use Hub\Client\Model\Property;
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
    }


    public function getResourceData(Resource $resource)
    {
        switch ($resource->getSourceApi()) {
            case 'v1':
                return $this->getResourceDataV1($resource);
                break;
            case 'v3':
                return $this->getResourceDataV3($resource);
            default:
                throw new RuntimeException("Unsupported source API: " . $resource->getSourceApi());
        }
    }
    
    private function getResourceDataV1(Resource $resource)
    {
        if ($resource->getSourceUrl()) {
            $url = $resource->getSourceUrl();
        } else {
            $providerApiUrl = $resource->getPropertyValue('provider_apiurl');
            if (!$providerApiUrl) {
                throw new RuntimeException("No v1 provider_apiurl to get resource data");
            }
            $bsn = $resource->getPropertyValue('client_bsn');
            $ref = $resource->getPropertyValue('reference');
            
            switch ($resource->getType()) {
                case 'hub/dossier':
                    $url = $providerApiUrl . '/' . $bsn . '/' . rawurlencode($ref);
                    break;
                default:
                    throw new RuntimeException("Unsupported resource type: " . $resource->getType());
            }
        }
        
        
        try {
            $hashSource = $url . $this->password;
            
            $headers = array();
            
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
                [ 'headers' => $headers ]
            );
            if ($res->getStatusCode() == 200) {
                $res = (string)$res->getBody();
                return $res;
            }
        } catch (\GuzzleHttp\Exception\RequestException $e) {
             ErrorResponseHandler::handle($e->getResponse());
        }
    }

    private function getResourceDataV3(Resource $resource)
    {
        $url = $resource->getSourceUrl();
        $jwt = $resource->getSourceJwt();
        if (!$url) {
            throw new RuntimeException("No provider url to get resource data");
        }
        //echo "REQUESTING: $url\n";
        $url .= "?jwt=". $jwt;
        
        try {
            $res = $this->httpClient->get(
                $url,
                [
                    'headers' => []
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
