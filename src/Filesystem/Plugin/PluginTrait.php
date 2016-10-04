<?php

namespace Bolt\Filesystem\Plugin;

use Bolt\Filesystem\FilesystemInterface;

/**
 * Trait to shortcut filesystem setter for plugins.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
trait PluginTrait
{
    /** @var FilesystemInterface */
    protected $filesystem;

    /**
     * {@inheritdoc}
     */
    public function setFilesystem(FilesystemInterface $filesystem)
    {
        $this->filesystem = $filesystem;
    }
}
