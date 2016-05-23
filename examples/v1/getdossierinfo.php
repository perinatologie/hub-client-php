<?php

use Hub\Client\Exception\NotFoundException;

require_once __DIR__ . '/../common.php';

$hubClient = new \Hub\Client\V1\HubV1Client($usernameV1, $passwordV1, $url);
$providerClient = new \Hub\Client\Provider\ProviderClient($usernameV1, $passwordV1);

if (count($argv)!=2) {
    exit("Expecting 1 parameter: bsn\n");
}
$bsn = $argv[1];

try {
    $resources = $hubClient->getDossierInfo($bsn);
} catch (NotFoundException $e) {
    exit("No results found...\n");
}

foreach ($resources as $resource) {
    $data = $providerClient->getResourceData($resource, $resource->getSource());
    echo $data;
}
exit();
