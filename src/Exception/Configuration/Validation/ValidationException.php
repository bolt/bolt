<?php

namespace Bolt\Exception\Configuration\Validation;

use Exception;

class ValidationException extends Exception
{
    /**
     * Constructor.
     *
     * @param string         $message
     * @param Exception|null $previous
     */
    public function __construct($message = '', Exception $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}
