<?php

namespace Bolt\Session\Handler;

use Symfony\Component\HttpFoundation\Session\Storage\Handler\MemcacheSessionHandler;

/**
 * Fixed to not close memcache connection on session close.
 *
 * @deprecated since 3.3, will be removed in 4.0. Use {@see Bolt\Session\Handler\MemcachedHandler} instead.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
class MemcacheHandler extends MemcacheSessionHandler
{
    /**
     * {@inheritdoc}
     *
     * Don't close connection.
     */
    public function close()
    {
        return true;
    }
}
