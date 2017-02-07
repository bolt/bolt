<?php

namespace Bolt\Exception\Configuration\Validation\Database;

use Bolt\Exception\Configuration\Validation\ValidationException;
use Bolt\Exception\Database\DatabaseExceptionInterface;
use Bolt\Exception\Database\DatabaseExceptionTrait;

abstract class AbstractDatabaseValidationException extends ValidationException implements DatabaseExceptionInterface
{
    use DatabaseExceptionTrait;

    protected $subType;

    /**
     * Constructor.
     *
     * @param string $subType
     * @param string $driver
     * @param string $message
     */
    public function __construct($subType, $driver, $message = '')
    {
        parent::__construct($message);
        $this->subType = $subType;
        $this->driver = $driver;
    }

    /**
     * @return string
     */
    public function getSubType()
    {
        return $this->subType;
    }
}
