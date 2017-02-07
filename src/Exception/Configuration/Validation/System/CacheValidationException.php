<?php

namespace Bolt\Exception\Configuration\Validation\System;

use Bolt\Configuration\Validation\Validator;

class CacheValidationException extends AbstractSystemValidationException
{
    /** @var string */
    protected $path;

    /**
     * Constructor.
     *
     * @param string $path
     */
    public function __construct($path)
    {
        parent::__construct(Validator::CHECK_CACHE, "The folder $path doesn't exist, or isn't writable.");
        $this->path = $path;
    }

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }
}
