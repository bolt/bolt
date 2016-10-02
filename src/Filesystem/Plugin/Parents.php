<?php

namespace Bolt\Filesystem\Plugin;

use Bolt\Filesystem\FilesystemInterface;
use Bolt\Filesystem\Handler\DirectoryInterface;
use Bolt\Filesystem\PluginInterface;

/**
 * Returns a list of parent directory objects for a path.
 *
 * Example: parents("a/b/c") // [b, a, (root)]
 */
class Parents implements PluginInterface
{
    /** @var FilesystemInterface */
    protected $filesystem;

    /**
     * {@inheritdoc}
     */
    public function getMethod()
    {
        return 'parents';
    }

    /**
     * {@inheritdoc}
     */
    public function setFilesystem(FilesystemInterface $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    /**
     * @param string $path
     *
     * @return DirectoryInterface[]
     */
    public function handle($path)
    {
        $parents = [];

        // path could actually be a file, but it doesn't matter here.
        $dir = $this->filesystem->getDir($path);

        if ($dir->isRoot()) {
            return $parents;
        }

        do {
            $dir = $dir->getParent();
            $parents[] = $dir;
        } while(!$dir->isRoot());

        return $parents;
    }
}
