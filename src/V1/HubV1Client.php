<?php

namespace Hub\Client\V1;

use Hub\Client\Model\Resource;
use Hub\Client\Model\Source;
use Hub\Client\Model\Property;
use Hub\Client\Common\ErrorResponseHandler;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Psr7\HttpFactory;
use RuntimeException;
use SimpleXMLElement;

class HubV1Client
{
    private $username;
    private $password;
    private $url;
    private $httpClient;
    private $httpFactory;

    public function __construct($username, $password, $url)
    {
        $this->username = $username;
        $this->password = $password;
        $this->url = rtrim($url, '/');
        $this->httpClient = new GuzzleClient();
        $this->httpFactory = new HttpFactory();
    }

    private function sendRequest($uri, $postData = null)
    {
        try {
            $fullUrl = $this->url . '/v1' . $uri;
            // stripping http(s) because of load-balancer. Hub sees http only
            //$fullUrl = str_replace('https', 'http', $fullUrl);
            $hashSource = $fullUrl . $postData . $this->password;
            //echo "HASHING: $hashSource\n";

            $securityHash = sha1($hashSource);

            //echo "Requesting: $fullUrl\n";

            $headers = [
                'uuid' => $this->username,
                'securityhash' => $securityHash
            ];

            if ($postData) {
                $stream = $this->httpFactory->createStream($postData);
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
             ErrorResponseHandler::handle($e->getResponse());
        }
    }

    private function parseDossiersXmlToResources($xml)
    {
        $rootNode = @simplexml_load_string($xml);
        if (!$rootNode) {
            echo $xml;
            throw new RuntimeException("Failed to parse response as XML...\n");
        }
        $resources = array();
        foreach ($rootNode->dossier as $dossierNode) {
            if ($dossierNode->client && $dossierNode->client->eocs) {
                $clientNode = $dossierNode->client;
                $providerNode = $dossierNode->provider;
                foreach ($dossierNode->client->eocs->eoc as $eocNode) {
                    $resource = new Resource();
                    $resource->setType('perinatologie/dossier');
                    $resource->addPropertyValue('reference', $eocNode->reference);
                    $resource->addPropertyValue('client_bsn', $clientNode->bsn);
                    $resource->addPropertyValue('client_birthdate', $clientNode->birthdate);
                    $resource->addPropertyValue('client_displayname', $clientNode->displayname);
                    $resource->addPropertyValue('client_zisnr', $clientNode->zisnr);
                    $resource->addPropertyValue('gravida', $eocNode->gravida);
                    $resource->addPropertyValue('para', $eocNode->para);
                    $resource->addPropertyValue('provider_reference', $providerNode->dbname);
                    $resource->addPropertyValue('provider_apiurl', $providerNode->apiurl);
                    $source = new Source();
                    $source->setApi('v1');
                    $resource->setSource($source);
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

    public function updateClientInfo(Resource $resource, $providerAgb = null)
    {
        $resources = array();
        $xml = $this->buildUpdateClientInfoXml($resource, $providerAgb);

        $body = $this->sendRequest('/updateclientinfo', $xml);

        return $body;
    }

    private function buildUpdateClientInfoXml(Resource $resource, $providerAgb = null)
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
        if ($providerAgb) {
            $providerNode->addChild('agb', $providerAgb);
        }

        //echo $clientNode->asXML();

        $dom = dom_import_simplexml($clientNode)->ownerDocument;
        $dom->formatOutput = true;

        return $dom->saveXML();
    }
}
