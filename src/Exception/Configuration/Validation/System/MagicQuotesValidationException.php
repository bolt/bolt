<?php

namespace Bolt\Exception\Configuration\Validation\System;

use Bolt\Configuration\Validation\Validator;

class MagicQuotesValidationException extends AbstractSystemValidationException
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct(Validator::CHECK_MAGIC_QUOTES, "Bolt requires 'Magic Quotes' to be off.");
    }
}
