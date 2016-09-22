<?php

namespace Hub\Client\Common;

use Hub\Client\Exception\BadRequestException;
use Hub\Client\Exception\NotFoundException;
use Hub\Client\Exception\NotAuthorizedException;
use RuntimeException;

class ErrorResponseHandler
{
    public static function handle($response)
    {
        if ($response->getStatusCode() == 401) {
            throw new NotAuthorizedException('NOT_AUTHORIZED', 'Basic auth failed');
        }

        $xml = $response->getBody();
        $rootNode = @simplexml_load_string($xml);
        if ($rootNode) {
            if ($rootNode->getName()=='error') {
                switch ((string)$rootNode->status) {
                    case 400:
                        throw new BadRequestException((string)$rootNode->code, (string)$rootNode->message);
                        break;
                    case 404:
                        throw new NotFoundException((string)$rootNode->code, (string)$rootNode->message);
                        break;
                    case 403:
                        throw new NotAuthorizedException((string)$rootNode->code, (string)$rootNode->message);
                        break;
                    default:
                        throw new RuntimeException(
                            "Unsupported response status code returned: " .
                            (string)$rootNode->code . ': ' .
                            (string)$rootNode->messsage .
                            " (" . (string)$rootNode->details . ")"
                        );
                        break;
                }
            }
            throw new RuntimeException(
                "Failed to parse response. It's valid XML, but not the expected error root element"
            );
        }

        throw new RuntimeException("Failed to parse response: " . $xml);
    }
}
