<?php

namespace Hub\Client\V1;

use Hub\Client\Model\Resource;
use Hub\Client\Model\Property;
use Hub\Client\Exception\BadRequestException;
use Hub\Client\Exception\NotFoundException;
use GuzzleHttp\Client as GuzzleClient;
use RuntimeException;
use SimpleXMLElement;

class Client
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
    
    private function processErrorResponse($xml)
    {
        $rootNode = @simplexml_load_string($xml);
        if ($rootNode) {
            if ($rootNode->getName()=='error') {
                switch ((string)$rootNode->status) {
                    case 400:
                        throw new BadRequestException((string)$rootNode->code, (string)$rootNode->message);
                        break;
                    case 404:
                        throw new NotFoundException((string)$rootNode->code, (string)$rootNode->message);
                        break;
                    default:
                        throw new RuntimeException(
                            "Unsupported response status code returned: " .
                            (string)$rootNode->status . ' / ' . (string)$rootNode->code
                        );
                        break;
                }
            }
            throw new RuntimeException(
                "Failed to parse response. It's valid XML, but not the expected error root element"
            );
        }

        throw new RuntimeException("Failed to parse response: " . $xml);
    }
    
    private function sendRequest($uri, $postData = null)
    {
        try {
            $fullUrl = $this->url . '/v1' . $uri;
            $hashSource = $fullUrl . $postData . $this->password;
            
            // stripping http(s) because of load-balancer. Hub sees http only
            $securityHash = sha1(str_replace('https', 'http', $hashSource));
            //echo "Requesting: $fullUrl\n";
            
            $headers = [
                'uuid' => $this->username,
                'securityhash' => $securityHash
            ];
            
            if ($postData) {
                $stream = \GuzzleHttp\Stream\Stream::factory($postData);
                $res = $this->httpClient->post(
                    $fullUrl,
                    ['headers' => $headers, 'body' => $stream]
                );
            } else {
                $res = $this->httpClient->get(
                    $fullUrl,
                    ['headers' => $headers]
                );
            }
            if ($res->getStatusCode() == 200) {
                return $res->getBody();
            }
            
        } catch (\GuzzleHttp\Exception\RequestException $e) {
             $xml = (string)$e->getResponse()->getBody();
             $this->processErrorResponse($xml);
        }
    }


    private function parseDossiersXmlToResources($xml)
    {
        $rootNode = @simplexml_load_string($xml);
        if (!$rootNode) {
            //echo $xml;
            throw new RuntimeException("Failed to parse response as XML...\n");
        }
        $resources = array();
        foreach ($rootNode->dossier as $dossierNode) {
            if ($dossierNode->client && $dossierNode->client->eocs) {
                $clientNode = $dossierNode->client;
                $providerNode = $dossierNode->provider;
                foreach ($dossierNode->client->eocs->eoc as $eocNode) {
                    $resource = new Resource();
                    $resource->setType('hub/dossier');
                    $resource->addPropertyValue('reference', $eocNode->reference);
                    $resource->addPropertyValue('client_bsn', $clientNode->bsn);
                    $resource->addPropertyValue('client_birthdate', $clientNode->birthdate);
                    $resource->addPropertyValue('client_displayname', $clientNode->displayname);
                    $resource->addPropertyValue('client_zisnr', $clientNode->zisnr);
                    $resource->addPropertyValue('gravida', $eocNode->gravida);
                    $resource->addPropertyValue('para', $eocNode->para);
                    $resource->addPropertyValue('provider_reference', $providerNode->dbname);
                    $resource->addPropertyValue('provider_apiurl', $providerNode->apiurl);
                    
                    $resources[] = $resource;
                }
            }
        }
        return $resources;
    }
    public function getDossierInfo($bsn)
    {
        $resources = array();
        $body = $this->sendRequest('/getdossierinfo/' . $bsn, null);
        
        return $this->parseDossiersXmlToResources((string)$body);
    }
    
    public function getResourceData(Resource $resource)
    {
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
             $xml = (string)$e->getResponse()->getBody();
             $this->processErrorResponse($xml);
        }
    }
    
    public function updateClientInfo(Resource $resource, $agb = null)
    {
        $resources = array();
        $xml = $this->buildUpdateClientInfoXml($resource, $agb);
    
        $body = $this->sendRequest('/updateclientinfo', $xml);
        return $body;
    }
    
    private function buildUpdateClientInfoXml(Resource $resource, $agb = null)
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
