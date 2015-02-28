<?php

namespace Bolt\Exception;

class PackageManagerException extends \Exception
{
    protected $file;
    protected $line;

    public function __construct($message = null, $code = 0, \Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);

        // Cascade file and line from the originating error
        if (!empty($previous)) {
            $this->file = $previous->file;
            $this->line = $previous->line;
        }
    }
}
