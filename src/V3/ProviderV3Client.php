<?php

namespace Hub\Client\V3;

use Hub\Client\Model\Resource;
use Hub\Client\Model\Property;
use Hub\Client\Common\ErrorResponseHandler;
use GuzzleHttp\Client as GuzzleClient;
use RuntimeException;
use SimpleXMLElement;

class ProviderV3Client
{
    private $httpClient;
    
    public function __construct()
    {
        $this->httpClient = new GuzzleClient();
    }
    
    public function getResourceData(Resource $resource)
    {
        $url = $resource->getPropertyValue('provider_apiurl');
        if (!$url) {
            throw new RuntimeException("No provider url to get resource data");
        }
        
        
        switch ($resource->getType()) {
            case 'perinatologie/dossier':
                $bsn = $resource->getPropertyValue('client_bsn');
                $ref = $resource->getPropertyValue('reference');
                $fullUrl = $url . '/' . $bsn . '/' . rawurlencode($ref);
                break;
            default:
                throw new RuntimeException("Unsupported resource type: " . $resource->getType());
        }
        
        try {
            $res = $this->httpClient->get(
                $fullUrl,
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
