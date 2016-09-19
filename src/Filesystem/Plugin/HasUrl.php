<?php

namespace Bolt\Filesystem\Plugin;

use Bolt\Filesystem\FilesystemInterface;
use Bolt\Filesystem\PluginInterface;

/**
 * Determines if the path has a url by try-catching the url plugin.
 */
class HasUrl implements PluginInterface
{
    /** @var FilesystemInterface */
    protected $filesystem;

    /**
     * {@inheritdoc}
     */
    public function getMethod()
    {
        return 'hasUrl';
    }

    /**
     * {@inheritdoc}
     */
    public function setFilesystem(FilesystemInterface $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    /**
     * Determine if the path has a url.
     *
     * @param string $path
     *
     * @return bool
     */
    public function handle($path)
    {
        try {
            $this->filesystem->url($path);
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }
}
