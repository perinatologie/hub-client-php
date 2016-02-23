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
            //echo $xml;
            throw new RuntimeException("Failed to parse response as XML...\n");
        }
        $resources = array();
        foreach ($rootNode->resource as $resourceNode) {
            $resource = new Resource();
            $resource->setType('perinatologie/dossier');
            foreach ($resourceNode->property as $propertyNode) {
                $resource->addPropertyValue($propertyNode['name'], (string)$propertyNode);
            }
            if (!$resourceNode->source) {
                throw new RuntimeException("Resource node does not contain source element");
            }
            $resource->setSourceUrl((string)$resourceNode->source->url);
            $resource->setSourceJwt((string)$resourceNode->source->jwt);
            
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
        $xml = $this->buildUpdateClientInfoXml($resource, $agb);
    
        $body = $this->sendRequest('/register', $xml);
        return $body;
    }
    
    private function buildRegisterXml(Resource $resource, $agb = null)
    {
        $clientNode = new SimpleXMLElement('<client />');
        $clientNode->addChild('bsn', $resource->getPropertyValue('bsn'));
        $clientNode->addChild('birthdate', $resource->getPropertyValue('birthdate'));
        $clientNode->addChild('zisnummer', $resource->getPropertyValue('zisnummer'));
        $clientNode->addChild('firstname', $resource->getPropertyValue('firstname'));
        $clientNode->addChild('lastname', $resource->getPropertyValue('lastname'));
        $eocsNode = $clientNode->addChild('eocs');
        $eocNode = $eocsNode->addChild('eoc');
        $eocNode->addChild('reference', $resource->getPropertyValue('reference'));
        $eocNode->addChild('gravida', $resource->getPropertyValue('gravida'));
        $eocNode->addChild('para', $resource->getPropertyValue('para'));
        $eocNode->addChild('starttimestamp', $resource->getPropertyValue('starttimestamp'));
        $eocNode->addChild('edd', $resource->getPropertyValue('edd'));
        
        foreach ($resource->getShares() as $share) {
            $shareNode = $eocNode->addChild('teammember');
            $shareNode->addChild('name', $share->getName());
            if ($share->getIdentifierType()!='agb') {
                throw new RuntimeException('Identifier types other than AGB not supported in v1 api');
            }
            $shareNode->addChild('agb', $share->getIdentifier());
            $shareNode->addChild('permission', $share->getPermission());
        }
        $providersNode = $eocsNode->addChild('providers');
        $providerNode = $providersNode->addChild('provider');
        $providerNode->addChild('uuid', $this->username);
        if ($agb) {
            $providerNode->addChild('agb', $agb);
        }
        
        //echo $clientNode->asXML();
        $dom = dom_import_simplexml($clientNode)->ownerDocument;
        $dom->formatOutput = true;
        return $dom->saveXML();
    }
}
