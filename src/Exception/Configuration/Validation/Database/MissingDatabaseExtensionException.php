<?php

namespace Bolt\Exception\Configuration\Validation\Database;

use Bolt\Exception\Configuration\Validation\MissingExtensionException;
use Bolt\Exception\Database\DatabaseExceptionInterface;
use Bolt\Exception\Database\DatabaseExceptionTrait;

class MissingDatabaseExtensionException extends MissingExtensionException implements DatabaseExceptionInterface
{
    use DatabaseExceptionTrait;

    /** @var string */
    protected $extension;

    /**
     * Constructor.
     *
     * @param string $driver pdo_*
     */
    public function __construct($driver)
    {
        parent::__construct($driver);
        $this->driver = $driver;
    }
}
