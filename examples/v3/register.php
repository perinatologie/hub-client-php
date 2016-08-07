<?php

require_once __DIR__ . '/../common.php';

$client = new \Hub\Client\V3\HubV3Client($usernameV3, $passwordV3, $url);

$filename = __DIR__ . '/templates/register.xml';

$resource = loadRegisterXml($filename);

print_r($resource);

$key = $client->register($resource);

exit("Registered key: $key\n");
