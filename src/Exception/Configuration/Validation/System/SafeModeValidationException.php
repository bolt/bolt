<?php

namespace Bolt\Exception\Configuration\Validation\System;

use Bolt\Configuration\Validation\Validator;

class SafeModeValidationException extends AbstractSystemValidationException
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct(Validator::CHECK_SAFE_MODE, "Bolt requires 'Safe mode' to be off.");
    }
}
