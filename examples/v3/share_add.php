<?php

use Hub\Client\Exception\NotFoundException;

require_once __DIR__ . '/../common.php';

$hubClient = new \Hub\Client\V3\HubV3Client($usernameV3, $passwordV3, $url);
$providerClient = new \Hub\Client\Provider\ProviderClient($usernameV1, $passwordV1);

if (count($argv)!=4) {
    echo "Please pass the resource key, accountname and permission as 3 arguments\n";
    exit(-1);
}
$key=$argv[1];
$grantee=$argv[2];
$permission=$argv[3];
echo "Adding share for resource [$key] for @$grantee with permission [$permission]\n";

$hubClient->addShare($key, $grantee, $permission);

$shares = $hubClient->getShares($key);

echo "Shares: " . count($shares) ."\n";
foreach ($shares as $share) {
    echo "  @" . $share->getName() . " <" . $share->getDisplayName() . "> [" . $share->getPermission() . "]\n";
}
