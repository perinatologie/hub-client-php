<?php

namespace Hub\Client\V3;

use Hub\Client\Model\Resource;
use Hub\Client\Model\Property;
use Hub\Client\Model\Source;
use Hub\Client\Model\Share;
use Hub\Client\Common\ErrorResponseHandler;
use Hub\Client\Exception\NoResponseException;
use GuzzleHttp\Client as GuzzleClient;
use RuntimeException;
use SimpleXMLElement;

class HubV3Client
{
    private $username;
    private $password;
    private $tlsCertVerification;

    protected $httpClient;
    protected $url;

    public function __construct(
        $username,
        $password,
        $url,
        $headers = [],
        $tlsCertVerification = null
    ) {
        $this->username = $username;
        $this->password = $password;
        $this->url = rtrim($url, '/') . '/v3';
        $this->httpClient = new GuzzleClient(
            [
                'headers' => $headers
            ]
        );
        if (null === $tlsCertVerification) {
            $this->tlsCertVerification = __DIR__ . '/../../cacert.pem';
        } else {
            $this->tlsCertVerification = $tlsCertVerification;
        }
    }

    /**
     * @param string $uri
     * @param string $postData
     * @return string
     * @throws \RuntimeException
     * @throws \Hub\Client\Exception\NoResponseException
     */
    protected function sendRequest($uri, $postData = null)
    {
        try {
            $fullUrl = $this->url . $uri;
            $headers = array();
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
                        ],
                        'verify' => $this->getTlsCertificateVerification()
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
                        ],
                        'verify' => $this->getTlsCertificateVerification()
                    ]
                );
            }
            if ($res->getStatusCode() == 200) {
                return (string)$res->getBody();
            }
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            if (!$e->getResponse()) {
                throw new NoResponseException('NO_RESPONSE', 'No response / connection error requesting ' . $fullUrl);
            }
            ErrorResponseHandler::handle($e->getResponse());
        }
    }


    private function parseXml($xml)
    {
        $node = @simplexml_load_string($xml);
        if (!$node) {
            //exit($xml);
            throw new RuntimeException("Failed to parse response as XML...\n");
        }
        return $node;
    }

    private function parseResourcesXmlToResources($rootNode)
    {
        $resources = array();
        foreach ($rootNode->resource as $resourceNode) {
            $resource = $this->parseResourceXmlToResource($resourceNode);

            /*
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
            */

            $resources[] = $resource;
        }
        return $resources;
    }

    private function parseResourceXmlToResource($resourceNode)
    {
        $resource = new Resource();
        $resource->setType((string)$resourceNode['type']);
        foreach ($resourceNode->property as $propertyNode) {
            $resource->addPropertyValue($propertyNode['name'], (string)$propertyNode);
        }
        return $resource;
    }

    private function parseSource($node)
    {
        $source = new Source();
        $source->setUrl((string)$node->url);
        $source->setApi((string)$node->api);
        if ($node->jwt) {
            $source->setJwt((string)$node->jwt);
        }
        return $source;
    }

    private function parseShares($node)
    {
        $shares = [];
        foreach ($node as $shareNode) {
            $share = new Share();
            $share->setName((string)$shareNode->granteeName);
            $share->setDisplayName((string)$shareNode->granteeDisplayName);
            $share->setPermission((string)$shareNode->permission);
            $shares[] = $share;
        }
        return $shares;
    }

    /**
     * Get a list of Resources which are shared with the current Account.
     *
     * @param array $filters
     *
     * @return \Hub\Client\Model\Resource[]
     */
    public function findResourcesSharedwithAccount($filters = array())
    {
        return $this->listResources($filters, 'shared_with');
    }

    /**
     * Get a list of Resources which are owned by the current Account.
     *
     * The owner is the subject of the resources.
     *
     * @param array $filters
     *
     * @return \Hub\Client\Model\Resource[]
     */
    public function findResourcesOwnedByAccount($filters = array())
    {
        return $this->listResources($filters, 'owned_by');
    }

    /**
     * Get a list of Resources which are provided by the current Account and its
     * associated Organisation Accounts.
     *
     * @param array $filters
     *
     * @return \Hub\Client\Model\Resource[]
     */
    public function findResourcesProvidedByAccountAndOrgAccounts($filters = array())
    {
        return $this->listResources($filters, 'provided_by');
    }

    /**
     * Get a list of Resources which are shared with the current Account and its
     * associated Organisation Accounts.
     *
     * @param array $filters
     *
     * @return \Hub\Client\Model\Resource[]
     */
    public function findResourcesSharedWithAccountAndOrgAccounts($filters = array())
    {
        return $this->listResources($filters);
    }

    /**
     * Get a list of Resources which are shared with the current Account and its
     * associated Organisation Accounts.
     *
     * @param array $filters
     *
     * @return \Hub\Client\Model\Resource[]
     */
    public function findResources($filters = array())
    {
        return $this->findResourcesSharedWithAccountAndOrgAccounts($filters);
    }

    /**
     * Get a list of Resources.
     *
     * @param array $filters
     * @param null|string one of 'shared_with', 'owned_by' and 'provided_by'
     *
     * @return \Hub\Client\Model\Resource[]
     */
    private function listResources($filters = array(), $listType = null)
    {
        $uri = '/resources';
        $query = array();
        if ($listType) {
            $query[] = $listType;
        }
        foreach ($filters as $name => $value) {
            $query[] = "{$name}={$value}";
        }

        if (sizeof($query)) {
            $uri .= '?' . implode('&', $query);
        }
        $body = $this->sendRequest($uri);
        $node = $this->parseXml((string)$body);
        return $this->parseResourcesXmlToResources($node);
    }

    public function getResource($key)
    {
        $resources = array();
        $uri = '/resources/' . $key;
        $body = $this->sendRequest($uri, null);

        $node = $this->parseXml((string)$body);
        return $this->parseResourceXmlToResource($node);
    }

    public function getPicture($key)
    {
        $resources = array();
        $uri = '/resources/' . $key . '/picture';
        $body = $this->sendRequest($uri, null);

        return $body;
    }

    public function getSource($key, $accept = null)
    {
        $resources = array();
        $uri = '/resources/' . $key . '/source';
        if ($accept) {
          $uri .= '?accept=' . $accept;
        }
        $body = $this->sendRequest($uri, null);

        $node = $this->parseXml((string)$body);
        return $this->parseSource($node);
    }

    public function getShares($key)
    {
        $resources = array();
        $uri = '/resources/' . $key . '/shares';
        $body = $this->sendRequest($uri, null);

        $node = $this->parseXml((string)$body);
        return $this->parseShares($node);
    }

    public function addShare($key, $grantee, $permission)
    {
        $resources = array();
        $uri = '/resources/' . $key . '/shares/add/' . $grantee . '/' . $permission;
        $body = $this->sendRequest($uri, null);

        $node = $this->parseXml((string)$body);
        // TODO: Validate status=OK?
        return true;
    }

    public function removeShare($key, $grantee)
    {
        $resources = array();
        $uri = '/resources/' . $key . '/shares/remove/' . $grantee;
        $body = $this->sendRequest($uri, null);

        $node = $this->parseXml((string)$body);
        // TODO: Validate status=OK?
        return true;
    }

    public function register(Resource $resource)
    {
        $resources = array();
        $xml = $this->buildRegisterXml($resource);
        //exit($xml);

        $body = $this->sendRequest('/register', $xml);

        $rootNode = @simplexml_load_string($body);
        if (!$rootNode || ($rootNode->getName() != 'status') || ((string)$rootNode != 'OK')) {
            throw new RuntimeException("Did not receive OK status: " . $body);
        }
        return (string)$rootNode['key'];
    }

    private function buildRegisterXml(Resource $resource)
    {
        $resourceNode = new SimpleXMLElement('<resource type="' .  $resource->getType() . '" />');
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

    /*
     * The purpose of this method is to maintain backaward compatability
     * as regard to the use of a hardcoded certificate store which is checked
     * for existence before every request is sent, whilst also allowing the
     * value to be given in the constructor as a string or boolean (see the
     * \GuzzleHttp\RequestOptions::VERIFY option and
     * https://curl.haxx.se/docs/caextract.html for more info).
     *
     * @retun bool|string
     *
     * @throws RuntimeException
     */
    private function getTlsCertificateVerification()
    {
        if (is_string($this->tlsCertVerification)
            && (!file_exists($this->tlsCertVerification) || !is_file($this->tlsCertVerification))
        ) {
            throw new RuntimeException(
                "cacert.pem not found: {$this->tlsCertVerification}"
            );
        }
        return $this->tlsCertVerification;
    }
}
