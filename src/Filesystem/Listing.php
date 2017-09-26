<?php

namespace Bolt\Filesystem;

use Bolt\Filesystem\Handler\DirectoryInterface;
use Bolt\Filesystem\Handler\FileInterface;
use Bolt\Filesystem\Handler\HandlerInterface;

/**
 * File and directory listing handler.
 *
 * @internal
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
final class Listing
{
    /** @var DirectoryInterface */
    private $directory;
    /** @var HandlerInterface[] */
    private $contents;

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
     * Return the directories content listing of the requested target.
     *
     * @param bool $showHidden True to show hidden (dot) files
     *
     * @throws Exception\IOException
     *
     * @return DirectoryInterface[]
     */
    public function getDirectories($showHidden = false)
    {
        $it = $this->getContents();

        return array_filter($it, function (HandlerInterface $handler) use ($showHidden) {
            return $handler->isDir() && ($showHidden ?: strpos($handler->getFilename(), '.') !== 0);
        });
    }

    /**
     * Return the files content listing of the requested target.
     *
     * @param bool $showHidden True to show hidden (dot) files
     *
     * @throws Exception\IOException
     *
     * @return FileInterface[]
     */
    public function getFiles($showHidden = false)
    {
        $it = $this->getContents();

        return array_filter($it, function (HandlerInterface $handler) use ($showHidden) {
            return $handler->isFile() && ($showHidden ?: strpos($handler->getFilename(), '.') !== 0);
        });
    }

    /**
     * Does the web server user have read/write file system permissions to the
     * target directory, see {@see \Bolt\Filesystem\Plugin\Authorized}.
     *
     * @deprecated since 3.3 to be removed in 4.0.
     *
     * @throws Exception\IOException
     *
     * @return bool
     */
    public function isAuthorized()
    {
        return $this->directory->authorized();
    }

    /**
     * Lazy load & cache the directory contents.
     *
     * @return HandlerInterface[]
     */
    private function getContents()
    {
        if ($this->contents) {
            return $this->contents;
        }

        return $this->contents = $this->directory->getContents();
    }
}
