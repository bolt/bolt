<?php

namespace Bolt\Exception\Configuration\Validation\Database;

class InsecureDatabaseException extends AbstractDatabaseValidationException
{
    /**
     * Constructor.
     *
     * @param string $driver
     */
    public function __construct($driver)
    {
        parent::__construct('insecure', $driver, "You're using the root user without a password. You are insecure.");
    }
}
