<?php

namespace Hub\Client\Exception;

use Exception;

use Psr\Http\Message\ResponseInterface;

/**
 * An exception thrown when a response (or lack of one) indicates a failure of
 * authentication.
 */
class AuthenticationFailureException extends Exception
{
    protected $response;

    public function __construct(
        ResponseInterface $response = null,
        $message = null,
        $code = null,
        $previous = null
    ) {
        $this->response = $response;
        parent::__construct($message, $code, $previous);
    }

    public function getResponse()
    {
        return $this->response;
    }
}
