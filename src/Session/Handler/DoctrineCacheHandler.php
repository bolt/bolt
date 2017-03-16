<?php

namespace Bolt\Session\Handler;

use Doctrine\Common\Cache\Cache;

/**
 * Doctrine Cache Session Handler.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
class DoctrineCacheHandler extends AbstractHandler
{
    /** @var Cache */
    protected $cache;
    /** @var int */
    protected $ttl;

    /**
     * Constructor.
     *
     * @param Cache $cache The cache instance
     * @param int   $ttl   Number of seconds before session expires
     */
    public function __construct(Cache $cache, $ttl = 86400)
    {
        $this->cache = $cache;
        $this->ttl = $ttl;
    }

    /**
     * {@inheritdoc}
     */
    public function read($sessionId)
    {
        return $this->cache->fetch($sessionId) ?: '';
    }

    /**
     * {@inheritdoc}
     */
    public function write($sessionId, $data)
    {
        return $this->cache->save($sessionId, $data, $this->ttl);
    }

    /**
     * {@inheritdoc}
     */
    public function destroy($sessionId)
    {
        return $this->cache->delete($sessionId);
    }
}
