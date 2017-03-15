<?php

namespace Bolt\Session\Handler;

use Psr\Cache\CacheItemPoolInterface;

/**
 * PSR-6 Cache Session Handler.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
class PsrCacheHandler extends AbstractHandler
{
    /** @var CacheItemPoolInterface */
    protected $pool;
    /** @var int */
    protected $ttl;

    /**
     * Constructor.
     *
     * @param CacheItemPoolInterface $pool The cache instance
     * @param int|null               $ttl  Number of seconds before session expires.
     *                                     Null for default value from cache pool.
     */
    public function __construct(CacheItemPoolInterface $pool, $ttl = null)
    {
        $this->pool = $pool;
        $this->ttl = $ttl;
    }

    /**
     * {@inheritdoc}
     */
    public function read($sessionId)
    {
        $item = $this->pool->getItem($sessionId);

        return $item->isHit() ? $item->get() : '';
    }

    /**
     * {@inheritdoc}
     */
    public function write($sessionId, $data)
    {
        $item = $this->pool->getItem($sessionId);

        $item->set($data);
        $item->expiresAfter($this->ttl);

        return $this->pool->save($item);
    }

    /**
     * {@inheritdoc}
     */
    public function destroy($sessionId)
    {
        return $this->pool->deleteItem($sessionId);
    }
}
