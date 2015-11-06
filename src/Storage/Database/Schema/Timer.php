<?php

namespace Bolt\Storage\Database\Schema;

use Bolt\Exception\StorageException;
use Carbon\Carbon;
use Doctrine\DBAL\Schema\Schema;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Schema validation check functionality.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class Timer
{
    /** @var \Symfony\Component\Filesystem\Filesystem */
    protected $filesystem;
    /** @var string */
    protected $timestampFile;
    /** @var boolean */
    protected $expired;

    const CHECK_INTERVAL = 1800;

    public function __construct($cachePath)
    {
        $this->timestampFile = $cachePath . '/dbcheck.ts';
        $this->filesystem = new Filesystem();
    }
}
