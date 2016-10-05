<?php

namespace Bolt\Asset;

use Bolt\Filesystem\Exception\IOException;
use Bolt\Filesystem\Handler\DirectoryInterface;
use Symfony\Component\Asset\VersionStrategy\VersionStrategyInterface;

/**
 * A version strategy that hashes a base salt, path, and timestamp of file.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
class BoltVersionStrategy implements VersionStrategyInterface
{
    /** @var DirectoryInterface */
    protected $directory;
    /** @var string */
    protected $baseSalt;

    /**
     * Constructor.
     *
     * @param DirectoryInterface $directory
     * @param string             $baseSalt
     */
    public function __construct(DirectoryInterface $directory, $baseSalt)
    {
        $this->directory = $directory;
        $this->baseSalt = $baseSalt;
    }

    /**
     * {@inheritdoc}
     */
    public function getVersion($path)
    {
        $file = $this->directory->getFile($path);

        try {
            return substr(md5($this->baseSalt . $file->getFullPath() . $file->getTimestamp()), 0, 10);
        } catch (IOException $e) {
            return '';
        }
    }

    /**
     * {@inheritdoc}
     */
    public function applyVersion($path)
    {
        $version = $this->getVersion($path);

        if (!$version) {
            return $path;
        }

        return sprintf('%s?%s', $path, $version);
    }
}
