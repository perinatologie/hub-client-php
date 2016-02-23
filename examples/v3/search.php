<?php

use Hub\Client\Exception\NotFoundException;

require_once __DIR__ . '/../common.php';

$hubClient = new \Hub\Client\V3\HubV3Client($username, $password, $url);
$providerClient = new \Hub\Client\V1\ProviderV1Client($username, $password);
//$providerClient = new \Hub\Client\V3\ProviderV3Client();

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

//print_r($resources); exit();

foreach ($resources as $resource) {
    $data = $providerClient->getResourceData($resource);
    echo $data;
}
