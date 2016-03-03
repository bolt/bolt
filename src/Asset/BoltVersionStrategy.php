<?php

namespace Bolt\Asset;

use Bolt\Filesystem\Exception\IOException;
use Bolt\Filesystem\FilesystemInterface;
use Symfony\Component\Asset\VersionStrategy\StaticVersionStrategy;

/**
 * A version strategy that hashes a base salt, path, and timestamp of file.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
class BoltVersionStrategy extends StaticVersionStrategy
{
    /** @var FilesystemInterface */
    protected $filesystem;
    /** @var string */
    protected $baseSalt;

    /**
     * Constructor.
     *
     * @param FilesystemInterface $filesystem
     * @param string              $baseSalt
     * @param string              $format
     */
    public function __construct(FilesystemInterface $filesystem, $baseSalt, $format = null)
    {
        parent::__construct(null, $format);
        $this->filesystem = $filesystem;
        $this->baseSalt = $baseSalt;
    }

    /**
     * {@inheritdoc}
     */
    public function getVersion($path)
    {
        $file = $this->filesystem->getFile($path);

        try {
            return substr(md5($this->baseSalt . $file->getFullPath() . $file->getTimestamp()), 0, 10);
        } catch (IOException $e) {
            return '';
        }
    }
}
