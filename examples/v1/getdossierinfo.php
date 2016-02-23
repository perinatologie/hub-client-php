<?php

use Hub\Client\Exception\NotFoundException;

require_once __DIR__ . '/../common.php';

$hubClient = new \Hub\Client\V1\HubV1Client($username, $password, $url);
$providerClient = new \Hub\Client\V1\ProviderV1Client($username, $password);

if (count($argv)!=2) {
    exit("Expecting 1 parameter: bsn\n");
}
$bsn = $argv[1];

try {
    $resources = $hubClient->getDossierInfo($bsn);
} catch (NotFoundException $e) {
    exit("No results found...\n");
}
print_r($resources);

foreach ($resources as $resource) {
    $data = $providerClient->getResourceData($resource);
    echo $data;
}
