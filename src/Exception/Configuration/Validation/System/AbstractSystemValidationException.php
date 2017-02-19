<?php

namespace Bolt\Exception\Configuration\Validation\System;

use Bolt\Exception\Configuration\Validation\ValidationException;

abstract class AbstractSystemValidationException extends ValidationException
{
    /** @var string */
    protected $type;

    /**
     * Constructor.
     *
     * @param string $type
     * @param string $message
     */
    public function __construct($type, $message = '')
    {
        parent::__construct($message);
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }
}
