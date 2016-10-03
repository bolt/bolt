<?php

namespace Bolt\Filesystem\Plugin;

use Bolt\Filesystem\Exception\InvalidArgumentException;
use Bolt\Filesystem\PluginInterface;
use Symfony\Component\Asset\PackageInterface;
use Symfony\Component\Asset\Packages;

/**
 * Returns the public url/path of an asset (filesystem path).
 *
 * If this plugin is given packages and the filesystem has a mount point
 * it will be used as the package name for getting the url.
 *
 * For example:
 *
 *  // $fs = Bolt\Filesystem\Manager
 *  $fs->url('foo://bar.jpg');
 *
 * Uses the package "foo" in the Packages class to generate url.
 */
class AssetUrl implements PluginInterface
{
    use PluginTrait;

    /** @var Packages|PackageInterface */
    protected $packages;

    /**
     * Constructor.
     *
     * @param Packages|PackageInterface $packages
     */
    public function __construct($packages)
    {
        if (!$packages instanceof Packages && !$packages instanceof PackageInterface) {
            throw new InvalidArgumentException(sprintf('Packages parameter must be an instance of %s or %s', Packages::class, PackageInterface::class));
        }

        $this->packages = $packages;
    }

    /**
     * {@inheritdoc}
     */
    public function getMethod()
    {
        return 'url';
    }

    /**
     * Returns the public url/path of an asset.
     *
     * @param string $path
     *
     * @return string
     */
    public function handle($path)
    {
        // getFile first to split mount point from path, if present.
        $file = $this->filesystem->getFile($path);

        if ($this->packages instanceof Packages) {
            return $this->packages->getUrl($file->getPath(), $file->getMountPoint());
        }

        return $this->packages->getUrl($file->getPath());
    }
}
