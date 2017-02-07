<?php

namespace Bolt\Exception\Configuration\Validation\Database;

class SqlitePathException extends AbstractDatabaseValidationException
{
    /** @var string */
    private $type;
    /** @var string */
    private $path;
    /** @var string */
    private $error;

    public static function folderMissing($path)
    {
        return new static($path, 'folder', 'does not exist');
    }

    public static function fileMissing($path)
    {
        return new static($path, 'file', 'does not exist');
    }

    public static function fileNotWritable($path)
    {
        return new static($path, 'file', 'is not writable');
    }

    public static function folderNotWritable($path)
    {
        return new static($path, 'folder', 'is not writable');
    }

    /**
     * Constructor.
     *
     * @param string $path
     * @param string $type
     * @param string $error
     */
    public function __construct($path, $type, $error)
    {
        parent::__construct('sqlite', "The database $type \"$path\" $error");
        $this->path = $path;
        $this->type = $type;
        $this->error = $error;
    }

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getError()
    {
        return $this->error;
    }
}
