<?php

namespace Bolt\Session\Handler;

use Predis\ClientInterface as Predis;
use Redis;

/**
 * Redis Session Handler.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
class RedisHandler implements \SessionHandlerInterface, LazyWriteHandlerInterface
{
    /** @var Redis|Predis */
    protected $redis;
    /** @var int */
    protected $ttl;

    /**
     * RedisHandler constructor.
     *
     * @param Redis|Predis $redis       The Redis or Predis client
     * @param int          $maxlifetime Number of seconds before session expires
     */
    public function __construct($redis, $maxlifetime)
    {
        if (!$redis instanceof Redis && !$redis instanceof Predis) {
            throw new \InvalidArgumentException('Argument must be an instance of Redis or Predis\ClientInterface');
        }
        $this->redis = $redis;
        $this->ttl = (int) $maxlifetime;
    }

    /**
     * {@inheritdoc}
     */
    public function open($savePath, $sessionName)
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function read($sessionId)
    {
        if ($data = $this->redis->get($sessionId)) {
            return $data;
        }

        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function gc($maxlifetime)
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function destroy($sessionId)
    {
        $this->redis->del($sessionId);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function write($sessionId, $data)
    {
        $this->redis->setex($sessionId, $this->ttl, $data);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function updateTimestamp($sessionId, $data)
    {
        $this->redis->expire($sessionId, $this->ttl);
    }

    /**
     * {@inheritdoc}
     */
    public function close()
    {
        return true;
    }
}
