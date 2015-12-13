<?php

namespace Bolt\Storage\Database\Schema;

use Bolt\Exception\StorageException;
use Carbon\Carbon;
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

    /**
     * Check if we have determined that we need to do a database check.
     *
     * @return boolean
     */
    public function isCheckRequired()
    {
        if ($this->expired !== null) {
            return $this->expired;
        }

        if ($this->filesystem->exists($this->timestampFile)) {
            $expiryTimestamp = (integer) file_get_contents($this->timestampFile);
        } else {
            $expiryTimestamp = 0;
        }
        $ts = Carbon::createFromTimeStamp($expiryTimestamp + self::CHECK_INTERVAL);

        return $this->expired = $ts->isPast();
    }

    /**
     * Invalidate our database check by removing the timestamp file from cache.
     *
     * @throws \RuntimeException
     */
    public function setCheckRequired()
    {
        try {
            $this->expired = true;
            $this->filesystem->remove($this->timestampFile);
        } catch (IOException $e) {
            $message = sprintf(
                "The file '%s' exists, but couldn't be removed. Please remove this file manually, and try again.",
                $this->timestampFile
            );
            throw new StorageException($message);
        }
    }

    /**
     * Set our state as valid by writing the current date/time to the
     * app/cache/dbcheck.ts file.
     *
     * We only want to do these checks once per hour, per session, since it's
     * pretty time consuming… Unless specifically requested.
     */
    public function setCheckExpiry()
    {
        try {
            $this->expired = false;
            $this->filesystem->dumpFile($this->timestampFile, time());
        } catch (IOException $e) {
            // Something went wrong
        }
    }
}
