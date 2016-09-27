<?php

namespace Bolt\Filesystem\Plugin;

use Bolt\Filesystem\FilesystemInterface;
use Bolt\Filesystem\PluginInterface;
use Symfony\Component\Asset\PackageInterface;

/**
 * This is used to get urls using the asset package given.
 */
class Url implements PluginInterface
{
    /** @var FilesystemInterface */
    protected $filesystem;
    /** @var PackageInterface */
    protected $package;

    /**
     * Constructor.
     *
     * @param PackageInterface $package
     */
    public function __construct(PackageInterface $package)
    {
        $this->package = $package;
    }

    /**
     * {@inheritdoc}
     */
    public function setFilesystem(FilesystemInterface $filesystem)
    {
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
        return $this->package->getUrl($path);
    }
}
