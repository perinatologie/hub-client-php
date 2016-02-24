<?php

use Hub\Client\Model\Resource;
use Hub\Client\Model\Property;
use Hub\Client\Model\Share;

require_once __DIR__ . '/../vendor/autoload.php';
$usernameV1 = getenv('HUB_V1_USERNAME');
$passwordV1 = getenv('HUB_V1_PASSWORD');
$usernameV3 = getenv('HUB_V3_USERNAME');
$passwordV3 = getenv('HUB_V3_PASSWORD');
$url = getenv('HUB_URL');

if (!$url) {
    throw new RuntimeException("Please refer to the README.md file on how to setup the environment variables");
}

echo "CONFIG:\n";
echo " * URL: $url\n";
echo " * USERNAME v1: $usernameV1\n";
echo " * PASSWORD v1: $passwordV1\n";
echo " * USERNAME v3: $usernameV3\n";
echo " * PASSWORD v3: $passwordV3\n";

function loadUpdateClientInfoXml($filename)
{
    $xml = file_get_contents($filename);
    $clientNode = @simplexml_load_string($xml);
    if (!$clientNode) {
        throw new RuntimeException("Failed to parse " . $filename);
    }
    $resources = array();

    foreach ($clientNode->eocs->eoc as $eocNode) {
        $resource = new Resource();
        $resource->setType('perinatologie/dossier');
        
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
    return $resources;
}

function loadRegisterXml($filename)
{
    $xml = file_get_contents($filename);
    $resourceNode = @simplexml_load_string($xml);
    if (!$resourceNode) {
        throw new RuntimeException("Failed to parse " . $filename);
    }

    $resource = new Resource();
    $resource->setType('perinatologie/dossier');
    foreach ($resourceNode->property as $propertyNode) {
        $property = new Property((string)$propertyNode['name'], (string)$propertyNode);
        $resource->addProperty($property);
    }

    foreach ($resourceNode->share as $shareNode) {
        $share = new Share();
        $share->setName((string)$shareNode->name);
        $share->setIdentifierType((string)$shareNode->identifier['type']);
        $share->setIdentifier((string)$shareNode->identifier);
        $share->setPermission((string)$shareNode->permission);
        $resource->addShare($share);
    }

    return $resource;
}
