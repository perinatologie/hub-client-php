<?php

use Hub\Client\Model\Resource;
use Hub\Client\Model\Property;
use Hub\Client\Model\Share;

require_once __DIR__ . '/../common.php';

$client = new \Hub\Client\V1\Client($username, $password, $url);

$filename = __DIR__ . '/templates/updateclientinfo.xml';
$xml = file_get_contents($filename);
$clientNode = @simplexml_load_string($xml);
if (!$clientNode) {
    throw new RuntimeException("Failed to parse " . $filename);
}

$agb = '08000000';

$resources = array();

foreach ($clientNode->eocs->eoc as $eocNode) {
    $resource = new Resource();
    $resource->setType('hub/dossier');
    
    // Client details
    $resource->addPropertyValue('bsn', $clientNode->bsn);
    $resource->addPropertyValue('birthdate', $clientNode->birthdate);
    $resource->addPropertyValue('zisnummer', $clientNode->zisnummer);
    $resource->addPropertyValue('firstname', $clientNode->firstname);
    $resource->addPropertyValue('lastname', $clientNode->lastname);

    // Eoc details
    $resource->addPropertyValue('reference', $eocNode->reference);
    $resource->addPropertyValue('gravida', $eocNode->gravida);
    $resource->addPropertyValue('para', $eocNode->para);
    $resource->addPropertyValue('starttimestamp', $eocNode->starttimestamp);
    $resource->addPropertyValue('edd', $eocNode->edd);
    
    foreach ($eocNode->teammember as $shareNode) {
        $share = new Share();
        $share->setName((string)$shareNode->name);
        $share->setIdentifierType('agb');
        $share->setIdentifier((string)$shareNode->agb);
        $share->setPermission((string)$shareNode->permission);
        $resource->addShare($share);
    }

    $resources[] = $resource;
}
print_r($resources);

foreach ($resources as $resource) {
    $client->updateClientInfo($resource, $agb);
}

exit("DONE\n");
