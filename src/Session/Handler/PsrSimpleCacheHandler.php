<?php

namespace Bolt\Session\Handler;

use Psr\SimpleCache\CacheInterface;

/**
 * PSR-16 Simple Cache Session Handler.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
class PsrSimpleCacheHandler extends AbstractHandler
{
    /** @var CacheInterface */
    protected $cache;
    /** @var int */
    protected $ttl;

    /**
     * Constructor.
     *
     * @param CacheInterface $cache The cache instance
     * @param int|null       $ttl   Number of seconds before session expires.
     *                              Null for default value from cache pool.
     */
    public function __construct(CacheInterface $cache, $ttl = null)
    {
        $this->cache = $cache;
        $this->ttl = $ttl;
    }

    /**
     * {@inheritdoc}
     */
    public function read($sessionId)
    {
        return $this->cache->get($sessionId, '');
    }

    /**
     * {@inheritdoc}
     */
    public function write($sessionId, $data)
    {
        return $this->cache->set($sessionId, $data, $this->ttl);
    }

    /**
     * {@inheritdoc}
     */
    public function destroy($sessionId)
    {
        return $this->cache->delete($sessionId);
    }
}
