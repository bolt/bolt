<?php

namespace Bolt\Filesystem\Plugin;

use Bolt\Filesystem\Exception\InvalidArgumentException;
use Bolt\Filesystem\FilesystemInterface;
use Bolt\Filesystem\MountPointAwareInterface;
use Bolt\Filesystem\PluginInterface;
use Symfony\Component\Asset\Packages;

/**
 * As Url plugin is designed for a single filesystem with a single asset package, this is designed for multiple
 * filesystems and multiple packages. The filesystem's mount point is used to map to the package name in the packages
 * object given.
 *
 * For example:
 *
 * // $fs = Bolt\Filesystem\Manager
 * $fs->url('foo://bar.jpg');
 *
 * Uses the package "foo" in the Packages class to generate url.
 *
 * Note: This can only be used if the filesystem is aware of mount points.
 */
class CompositeUrls implements PluginInterface
{
    /** @var FilesystemInterface|MountPointAwareInterface */
    protected $filesystem;
    /** @var Packages */
    protected $packages;

    /**
     * Constructor.
     *
     * @param Packages $packages
     */
    public function __construct(Packages $packages)
    {
        $this->packages = $packages;
    }

    /**
     * {@inheritdoc}
     */
    public function setFilesystem(FilesystemInterface $filesystem)
    {
        if (!$filesystem instanceof MountPointAwareInterface) {
            throw new InvalidArgumentException('Filesystem given must be an instance of Bolt\Filesystem\MountPointAwareInterface');
        }

        $this->filesystem = $filesystem;
    }

    /**
     * {@inheritdoc}
     */
    public function getMethod()
    {
        return 'url';
    }

    /**
     * Returns an absolute or root-relative public path to be used for a url.
     *
     * @param string $path
     *
     * @return string
     */
    public function handle($path)
    {
        return $this->packages->getUrl($path, $this->filesystem->getMountPoint());
    }
}
