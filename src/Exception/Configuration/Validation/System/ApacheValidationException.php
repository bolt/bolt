<?php

namespace Bolt\Exception\Configuration\Validation\System;

use Bolt\Configuration\Validation\Validator;

class ApacheValidationException extends AbstractSystemValidationException
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct(Validator::CHECK_APACHE, 'There is no .htaccess file in your webroot.');
    }
}
