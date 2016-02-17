<?php

namespace Hub\Client\V1;

use Hub\Client\Model\Resource;
use Hub\Client\Model\Property;
use GuzzleHttp\Client as GuzzleClient;
use RuntimeException;

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
        $this->url = $url;
        $this->httpClient = new GuzzleClient();
    }
    
    private function processErrorResponse($xml)
    {
        throw new RuntimeException("Error response: " . $xml);
    }
    
    private function sendRequest($uri, $postData = null)
    {
        try {
            $fullUrl = $this->url . $uri;
            $hashSource = $fullUrl . $postData . $this->password;
            
            // stripping http(s) because of load-balancer. Hub sees http only
            $securityHash = sha1(str_replace('https', 'http', $hashSource));
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
                return $res->getBody();
            }
            
        } catch (\GuzzleHttp\Exception\ServerException $e) {
             $xml = (string)$e->getResponse()->getBody();
             $this->processErrorResponse($xml);
        }
    }
    
    public function getDossierInfo($bsn)
    {
        $resources = array();
        $body = $this->sendRequest('/getdossierinfo/' . $bsn, null);
        echo $body;
        $rootNode = simplexml_load_string($body);
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
                    
                    if ($resource->getPropertyValue('provider_reference')!='') {
                        $resources[] = $resource;
                    }
                }
            }
        }
        return $resources;

    }
    
    public function getResourceData(Resource $resource)
    {
        $url = $resource->getPropertyValue('provider_apiurl');
        $bsn = $resource->getPropertyValue('client_bsn');
        $fullUrl = $url . $bsn;
        
        try {
            $hashSource = $fullUrl . $this->password;
            
            // stripping http(s) because of load-balancer. Hub sees http only
            $securityHash = sha1(str_replace('https', 'http', $hashSource));
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
                $res = $res->getBody();
                echo $res;
                exit();
            }
            
        } catch (\GuzzleHttp\Exception\ServerException $e) {
             $xml = (string)$e->getResponse()->getBody();
             $this->processErrorResponse($xml);
        }
    }
}
