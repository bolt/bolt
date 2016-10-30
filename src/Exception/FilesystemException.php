<?php

namespace Bolt\Exception;

/**
 * Filesystem exceptions.
 */
class FilesystemException extends \Exception
{
    const FILE_NOT_READABLE            = 1;
    const FILE_NOT_WRITEABLE           = 2;
    const FILE_NOT_REMOVEABLE          = 4;
    const FILE_UPLOADS_ARE_NOT_ALLOWED = 5;

    protected $code = 0;

    public function __construct($message = null, $code = 0, \Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);

        $this->code = $code;
    }
}
