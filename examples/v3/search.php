<?php

use Hub\Client\Exception\NotFoundException;

require_once __DIR__ . '/../common.php';

$hubClient = new \Hub\Client\V3\HubV3Client($usernameV3, $passwordV3, $url);
$providerClient = new \Hub\Client\Provider\ProviderClient($usernameV1, $passwordV1);

$filters = array();
if (count($argv)>1) {
    $arguments = $argv;
    $cmd = array_shift($arguments); // shifts the first item cmd from the array
    //print_r($arguments);
    
    foreach ($arguments as $property) {
        $part = explode("=", $property);
        if (count($part)!=2) {
            throw new RuntimeException("Filter `$property` invalid. Expecting filters in key=value format");
        }
        $key = $part[0];
        $value = $part[1];
        $filters[$key] = $value;
        echo "Adding filter: $key=$value\n";
    }
}

try {
    $resources = $hubClient->findResources($filters);
} catch (NotFoundException $e) {
    exit("\nNo results found...\n");
}
print_r($resources);
//exit();

foreach ($resources as $resource) {
    $data = $providerClient->getResourceData($resource);
    echo $data;
}
