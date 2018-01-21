<?php

namespace Bolt\Asset;

use Bolt\Filesystem\Handler\FileInterface;
use Bolt\Filesystem\Handler\JsonFile;
use Symfony\Component\Asset\VersionStrategy\VersionStrategyInterface;

/**
 * @internal
 * @deprecated to be replaced with upstream implementation in v4
 *
 * The manifest file uses the following format:
 *     {
 *         "main.js": "main.abc123.js",
 *         "css/styles.css": "css/styles.555abc.css"
 *     }
 */
final class JsonManifestVersionStrategy implements VersionStrategyInterface
{
    /** @var FileInterface|JsonFile */
    private $manifestFile;
    /** @var array|null */
    private $manifestData;

    public function __construct(FileInterface $manifestFile)
    {
        $this->manifestFile = $manifestFile;
    }

    public function getVersion($path)
    {
        return $this->applyVersion($path);
    }

    public function applyVersion($path)
    {
        return $this->getManifestPath($path) ?: $path;
    }

    private function getManifestPath($path)
    {
        if ($this->manifestData === null) {
            if (!$this->manifestFile->exists()) {
                throw new \RuntimeException(sprintf('Asset manifest file "%s" does not exist.', $this->manifestFile->getFullPath()));
            }

            $this->manifestData = $this->manifestFile->parse();
        }

        return isset($this->manifestData[$path]) ? $this->manifestData[$path] : null;
    }
}
