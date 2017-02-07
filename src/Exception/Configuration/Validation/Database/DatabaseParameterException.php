<?php

namespace Bolt\Exception\Configuration\Validation\Database;

class DatabaseParameterException extends AbstractDatabaseValidationException
{
    /** @var string */
    protected $parameter;

    /**
     * Constructor.
     *
     * @param string $parameter
     * @param string $driver
     */
    public function __construct($parameter, $driver)
    {
        parent::__construct('parameter', $driver, "There is no '$parameter' set for your database.");
        $this->parameter = $parameter;
    }

    /**
     * @return string
     */
    public function getParameter()
    {
        return $this->parameter;
    }
}
