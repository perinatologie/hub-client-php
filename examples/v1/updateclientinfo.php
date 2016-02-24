<?php

require_once __DIR__ . '/../common.php';

$client = new \Hub\Client\V1\HubV1Client($usernameV1, $passwordV1, $url);

$filename = __DIR__ . '/templates/updateclientinfo.xml';

$resources = loadUpdateClientInfoXml($filename);

$agb = '08000000';

print_r($resources);

foreach ($resources as $resource) {
    $client->updateClientInfo($resource, $agb);
}

exit("DONE\n");
