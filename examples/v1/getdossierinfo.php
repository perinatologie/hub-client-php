<?php

require_once __DIR__ . '/../common.php';

$client = new \Hub\Client\V1\Client($username, $password, $url);

if (count($argv)!=2) {
    exit("Expecting 1 parameter: bsn\n");
}
$bsn = $argv[1];

$resources = $client->getDossierInfo($bsn);
foreach ($resources as $resource) {
    $data = $client->getResourceData($resource);
    echo $data;
}
print_r($resources);
