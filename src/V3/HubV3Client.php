<?php

namespace Hub\Client\V3;

use Hub\Client\Model\Resource;
use Hub\Client\Model\Property;
use Hub\Client\Common\ErrorResponseHandler;
use GuzzleHttp\Client as GuzzleClient;
use RuntimeException;
use SimpleXMLElement;

class HubV3Client
{
    private $username;
    private $password;
    private $url;
    private $httpClient;
    
    public function __construct($username, $password, $url)
    {
        $this->username = $username;
        $this->password = $password;
        $this->url = rtrim($url, '/');
        $this->httpClient = new GuzzleClient();
    }
    

    private function sendRequest($uri, $postData = null)
    {
        try {
            $fullUrl = $this->url . '/v3' . $uri;
            
            $headers = array();
            //echo "Requesting: " . $fullUrl;
            if ($postData) {
                $stream = \GuzzleHttp\Stream\Stream::factory($postData);
                $res = $this->httpClient->post(
                    $fullUrl,
                    [
                        'headers' => $headers,
                        'body' => $stream,
                        'auth' => [
                            $this->username,
                            $this->password
                        ]
                    ]
                );
            } else {
                $res = $this->httpClient->get(
                    $fullUrl,
                    [
                        'headers' => $headers,
                        'auth' => [
                            $this->username,
                            $this->password
                        ]
                    ]
                );
            }
            if ($res->getStatusCode() == 200) {
                return (string)$res->getBody();
            }
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            ErrorResponseHandler::handle($e->getResponse());
        }
    }


    private function parseResourcesXmlToResources($xml)
    {
        $rootNode = @simplexml_load_string($xml);
        if (!$rootNode) {
            echo $xml;
            throw new RuntimeException("Failed to parse response as XML...\n");
        }
        $resources = array();
        foreach ($rootNode->resource as $resourceNode) {
            $resource = new Resource();
            $resource->setType('perinatologie/dossier');
            foreach ($resourceNode->property as $propertyNode) {
                $resource->addPropertyValue($propertyNode['name'], (string)$propertyNode);
            }
            $sourceNode = $resourceNode->source;
            if (!$sourceNode) {
                throw new RuntimeException("Resource node does not contain source element");
            }
            $resource->setSourceUrl((string)$sourceNode->url);
            $resource->setSourceApi((string)$sourceNode->api);
            if (!$resource->getSourceApi()) {
                throw new RuntimeException("No source api returned");
            }
            if ($sourceNode->jwt) {
                $resource->setSourceJwt((string)$sourceNode->jwt);
            }
            
            $resources[] = $resource;
        }
        return $resources;
    }

    public function findResources($filters = array())
    {
        $resources = array();
        $uri = '/resources';
        $first = true;
        foreach ($filters as $key => $value) {
            if ($first) {
                $uri .= '?';
            } else {
                $uri .= '&';
            }
            $uri .= $key . '=' . $value;
            $first = false;
        }
        $body = $this->sendRequest($uri, null);
        //echo($body);
        
        return $this->parseResourcesXmlToResources((string)$body);
    }
    
    public function register(Resource $resource, $agb = null)
    {
        $resources = array();
        $xml = $this->buildRegisterXml($resource, $agb);
        //exit($xml);
    
        $body = $this->sendRequest('/register', $xml);
        
        $rootNode = @simplexml_load_string($body);
        if (!$rootNode || ($rootNode->getName() != 'status') || ((string)$rootNode != 'OK')) {
            throw new RuntimeException("Did not receive OK status: " . $body);
        }
        return true;
    }
    
    private function buildRegisterXml(Resource $resource, $agb = null)
    {
        $resourceNode = new SimpleXMLElement('<resource />');
        foreach ($resource->getProperties() as $property) {
            $resourceNode->addChild('property', $property->getValue())->addAttribute('name', $property->getName());
        }
        
        foreach ($resource->getShares() as $share) {
            $shareNode = $resourceNode->addChild('share');
            $shareNode->addChild('name', $share->getName());
            $shareNode->addChild('identifier', $share->getIdentifier())->addAttribute('type', $share->getIdentifierType());
            $shareNode->addChild('permission', $share->getPermission());
        }
        
        //echo $clientNode->asXML();
        $dom = dom_import_simplexml($resourceNode)->ownerDocument;
        $dom->formatOutput = true;
        return $dom->saveXML();
    }
}
