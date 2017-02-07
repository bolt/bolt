<?php

namespace Bolt\Exception\Configuration\Validation\Database;

class UnsupportedDatabaseException extends AbstractDatabaseValidationException
{
    /**
     * Constructor.
     *
     * @param string $driver
     */
    public function __construct($driver)
    {
        parent::__construct('unsupported', $driver, $driver . ' was selected as the database type, but it is not supported.');
    }
}
