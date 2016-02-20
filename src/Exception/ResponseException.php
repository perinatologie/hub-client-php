<?php

namespace Hub\Client\Exception;

use Exception;

abstract class ResponseException extends Exception
{
    protected $code;
    protected $message;
    
    public function __construct($code, $message)
    {
        $this->code = $code;
        $this->message = '[' . $code . '] ' . $message;
    }
}
