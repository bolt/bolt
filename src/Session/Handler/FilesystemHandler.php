<?php

namespace Bolt\Session\Handler;

use Bolt\Filesystem\Exception\IOException;
use Bolt\Filesystem\Handler\DirectoryInterface;
use Bolt\Filesystem\Handler\FileInterface;

/**
 * Bolt's Filesystem abstraction session handler.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
class FilesystemHandler implements \SessionHandlerInterface
{
    /** @var DirectoryInterface */
    protected $directory;

    /**
     * Constructor.
     *
     * @param DirectoryInterface $directory
     */
    public function __construct(DirectoryInterface $directory)
    {
        $this->directory = $directory;
    }

    /**
     * {@inheritdoc}
     */
    public function read($sessionId)
    {
        try {
            return $this->directory->getFile($sessionId)->read();
        } catch (IOException $e) {
            return null;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function write($sessionId, $data)
    {
        $this->directory->getFile($sessionId)->put($data);
    }

    /**
     * {@inheritdoc}
     */
    public function destroy($sessionId)
    {
        try {
            $this->directory->getFile($sessionId)->delete();
        } catch (IOException $e) {
        }
    }

    /**
     * {@inheritdoc}
     */
    public function gc($maxlifetime)
    {
        $files = $this->directory->find()
            ->files()
            ->date("< now - $maxlifetime seconds")
        ;
        foreach ($files as $file) {
            /** @var $file FileInterface */
            $file->delete();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function open($savePath, $sessionName)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function close()
    {
    }
}
