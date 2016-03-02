<?php

namespace Bolt\Session\Handler;

/**
 * This defines a session handler that supports lazy writing.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
interface LazyWriteHandlerInterface
{
    /**
     * Updates the session data modified/access timestamp in order to
     * prevent premature garbage collection.
     *
     * This is called, instead of {@see SessionHandler::write()}, when
     * the "lazy_write" option is enabled and session data has not changed.
     *
     * @param string $sessionId
     * @param string $data
     */
    public function updateTimestamp($sessionId, $data);
}
