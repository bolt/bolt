<?php

namespace Bolt\Filesystem\Exception;

class DefaultImageNotFoundException extends FileNotFoundException
{
    /**
     * Constructor.
     *
     * @param string          $message
     * @param string          $path
     * @param \Exception|null $previous
     */
    public function __construct($message, $path, \Exception $previous = null)
    {
        IOException::__construct($message, $path, 0, $previous);
    }
}
