<?php

namespace Hub\Client\V1;

use Hub\Client\Model\Resource;
use Hub\Client\Model\Property;
use Hub\Client\Common\ErrorResponseHandler;
use GuzzleHttp\Client as GuzzleClient;
use RuntimeException;
use SimpleXMLElement;

class ProviderV1Client
{
    private $username;
    private $password;
    private $httpClient;
    
    public function __construct($username, $password)
    {
        $this->username = $username;
        $this->password = $password;
        $this->httpClient = new GuzzleClient();
    }
    
    public function getResourceData(Resource $resource)
    {
        if ($resource->getSourceUrl()) {
            $fullUrl = $resource->getSourceUrl();
        } else {
            $url = $resource->getPropertyValue('provider_apiurl');
            if (!$url) {
                throw new RuntimeException("No provider url to get resource data");
            }
            $bsn = $resource->getPropertyValue('client_bsn');
            $ref = $resource->getPropertyValue('reference');
            
            switch ($resource->getType()) {
                case 'hub/dossier':
                    $fullUrl = $url . '/' . $bsn . '/' . rawurlencode($ref);
                    break;
                default:
                    throw new RuntimeException("Unsupported resource type: " . $resource->getType());
            }
        }
        
        
        try {
            $hashSource = $fullUrl . $this->password;
            
            // stripping http(s) because of load-balancer. Hub sees http only
            $securityHash = sha1(str_replace('https', 'http', $hashSource));
            //echo "REQUESTING: " . $fullUrl . "\n";
            $res = $this->httpClient->get(
                $fullUrl,
                [
                    'headers' => [
                        'uuid' => $this->username,
                        'securityhash' => $securityHash
                    ]
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
