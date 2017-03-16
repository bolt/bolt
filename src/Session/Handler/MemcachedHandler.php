<?php

namespace Bolt\Session\Handler;

use Symfony\Component\HttpFoundation\Session\Storage\Handler\MemcachedSessionHandler;

/**
 * {@inheritdoc}
 *
 * Added lazy write support.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
class MemcachedHandler extends MemcachedSessionHandler implements LazyWriteHandlerInterface
{
    /** @var int Time to live in seconds */
    protected $ttl;
    /** @var string Key prefix for shared environments */
    protected $prefix;

    /**
     * {@inheritdoc}
     */
    public function __construct(\Memcached $memcached, array $options = [])
    {
        parent::__construct($memcached, $options);

        $this->ttl = isset($options['expiretime']) ? (int) $options['expiretime'] : 86400;
        $this->prefix = isset($options['prefix']) ? $options['prefix'] : 'sf2s';
    }

    /**
     * {@inheritdoc}
     */
    public function updateTimestamp($sessionId, $data)
    {
        $this->getMemcached()->touch($this->prefix . $sessionId, time() + $this->ttl);
    }
}
