<?php

namespace Bolt\Exception\Database;

use Exception;

class DatabaseException extends Exception implements DatabaseExceptionInterface
{
    use DatabaseExceptionTrait;

    /**
     * Constructor.
     *
     * @param string         $driver
     * @param string         $message
     * @param Exception|null $previous
     * @param int            $code
     */
    public function __construct($driver, $message = '', Exception $previous = null, $code = 0)
    {
        parent::__construct($message, $code, $previous);
        $this->driver = $driver;
    }
}
