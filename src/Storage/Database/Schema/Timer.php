<?php

namespace Bolt\Storage\Database\Schema;

use Bolt\Exception\StorageException;
use Bolt\Filesystem\Exception\FileNotFoundException;
use Bolt\Filesystem\Exception\IOException;
use Bolt\Filesystem\Handler\FileInterface;
use Carbon\Carbon;

/**
 * Schema validation check functionality.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class Timer
{
    /** @var FileInterface */
    protected $cacheFile;
    /** @var boolean */
    protected $expired;

    const CHECK_TIMESTAMP_FILE = 'dbcheck.ts';
    const CHECK_INTERVAL = 1800;

    /**
     * Constructor.
     *
     * @param FileInterface $cacheFile
     */
    public function __construct(FileInterface $cacheFile)
    {
        $this->cacheFile = $cacheFile;
    }

    /**
     * Check if we have determined that we need to do a database check.
     *
     * @return boolean
     */
    public function isCheckRequired()
    {
        if ($this->expired === false) {
            return false;
        }

        if ($this->cacheFile->exists()) {
            $expiryTimestamp = (integer) $this->cacheFile->read();
        } else {
            $expiryTimestamp = 0;
        }
        $ts = Carbon::createFromTimestamp($expiryTimestamp + self::CHECK_INTERVAL);

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
            $this->cacheFile->delete();
        } catch (FileNotFoundException $e) {
            // Don't need to delete the file, it isn't there
        } catch (IOException $e) {
            $message = sprintf('Unable to remove database schema check timestamp: %s', $e->getMessage());

            throw new StorageException($message, $e->getCode(), $e);
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
            $this->cacheFile->put(Carbon::now()->getTimestamp());
        } catch (IOException $e) {
            // Something went wrong
        }
    }
}
