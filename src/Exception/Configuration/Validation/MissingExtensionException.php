<?php

namespace Bolt\Exception\Configuration\Validation;

class MissingExtensionException extends ValidationException
{
    /** @var string */
    protected $extension;

    /**
     * Constructor.
     *
     * @param string $extension
     */
    public function __construct($extension)
    {
        parent::__construct("PHP extension '$extension' is missing.");
        $this->extension = $extension;
    }

    /**
     * @return string
     */
    public function getExtension()
    {
        return $this->extension;
    }
}
