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
    

}
