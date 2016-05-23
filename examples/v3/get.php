<?php

use Hub\Client\Exception\NotFoundException;

require_once __DIR__ . '/../common.php';

$hubClient = new \Hub\Client\V3\HubV3Client($usernameV3, $passwordV3, $url);
$providerClient = new \Hub\Client\Provider\ProviderClient($usernameV1, $passwordV1);

if (count($argv)!=2) {
    echo "Please pass the resource key as the first argument\n";
}
$key=$argv[1];
echo "Fetching resource by key $key\n";
$resource = $hubClient->getResource($key);

echo "Resource: $key\n";
foreach ($resource->getProperties() as $property) {
    echo "   " . $property->getName() . '=' . $property->getValue() . "\n";
}

$source = $hubClient->getSource($key);

echo "Source: " . $source->getUrl() . " [" . $source->getApi() . "]\n";
if ($source->getJwt()) {
    echo "JWT: " . $source->getJwt() . "\n";
}

$data = $providerClient->getResourceData($resource, $source);
echo $data;

//print_r($source);

//print_r($resources);
//exit();

/*
foreach ($resources as $resource) {
    $data = $providerClient->getResourceData($resource);
    echo $data;
}
*/
