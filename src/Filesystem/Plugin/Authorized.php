<?php

namespace Bolt\Filesystem\Plugin;

use Bolt\Filesystem\FilePermissions;
use Bolt\Filesystem\PluginInterface;

/**
 * Connects filesystem to FilePermissions class.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
class Authorized implements PluginInterface
{
    use PluginTrait;

    /** @var FilePermissions */
    protected $filePermissions;

    /**
     * Constructor.
     *
     * @param FilePermissions $filePermissions
     */
    public function __construct(FilePermissions $filePermissions)
    {
        $this->filePermissions = $filePermissions;
    }

    /**
     * {@inheritdoc}
     */
    public function getMethod()
    {
        return 'authorized';
    }

    /**
     * Returns whether you are authorized to do something with a file or directory.
     *
     * @param string $path
     *
     * @return bool
     */
    public function handle($path)
    {
        $file = $this->filesystem->getFile($path);

        return $this->filePermissions->authorized($file->getMountPoint(), $file->getPath());
    }
}
