<?php

use Hub\Client\Exception\NotFoundException;

require_once __DIR__ . '/../common.php';

$client = new \Hub\Client\V1\Client($username, $password, $url);

if (count($argv)!=2) {
    exit("Expecting 1 parameter: bsn\n");
}
$bsn = $argv[1];

try {
    $resources = $client->getDossierInfo($bsn);
} catch (NotFoundException $e) {
    exit("No results found...\n");
}

foreach ($resources as $resource) {
    $data = $client->getResourceData($resource);
    echo $data;
}
print_r($resources);
