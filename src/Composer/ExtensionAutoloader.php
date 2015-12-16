<?php

namespace Bolt\Composer;

use Bolt\Filesystem\FilesystemInterface;

/**
 * Class to manage autoloading functionality for extensions.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class ExtensionAutoloader
{
    /** @var FilesystemInterface */
    private $filesystem;

    /**
     * ExtensionAutoloader constructor.
     *
     * @param FilesystemInterface $filesystem
     */
    public function __construct(FilesystemInterface $filesystem)
    {
        $this->filesystem = $filesystem;
    }
}
