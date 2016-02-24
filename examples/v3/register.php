<?php

require_once __DIR__ . '/../common.php';

$client = new \Hub\Client\V3\HubV3Client($usernameV3, $passwordV3, $url);

$filename = __DIR__ . '/templates/register.xml';

$resource = loadRegisterXml($filename);

$agb = '08000000';

print_r($resource);

$client->register($resource, $agb);

exit("DONE\n");
