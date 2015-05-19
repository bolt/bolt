<?php

namespace Bolt\Logger;

use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;

/**
 * Storage logger for FlashBag messages.
 *
 * This class stores messages in memory until they are ready to be dispatched,
 * as this allows them to be set without a session being started and a cookie
 * set when it might not be valid.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class FlashLogger implements FlashLoggerInterface
{
    const DANGER  = 'danger';
    const ERROR   = 'error';
    const INFO    = 'info';
    const SUCCESS = 'success';
    const WARNING = 'warning';

    /** @var array $flash */
    private $flashes = [];

    /**
     * {@inheritdoc}
     */
    public function danger($message)
    {
        $this->queue(self::DANGER, $message);
    }

    /**
     * {@inheritdoc}
     */
    public function error($message)
    {
        $this->queue(self::ERROR, $message);
    }

    /**
     * {@inheritdoc}
     */
    public function info($message)
    {
        $this->queue(self::INFO, $message);
    }

    /**
     * {@inheritdoc}
     */
    public function success($message)
    {
        $this->queue(self::SUCCESS, $message);
    }

    /**
     * {@inheritdoc}
     */
    public function warning($message)
    {
        $this->queue(self::WARNING, $message);
    }

    /**
     * {@inheritdoc}
     */
    public function has($type)
    {
        return array_key_exists($type, $this->flashes) && $this->flashes[$type];
    }

    /**
     * {@inheritdoc}
     */
    public function get($type, array $default = array())
    {
        if (!$this->has($type)) {
            return $default;
        }

        $return = $this->flashes[$type];

        unset($this->flashes[$type]);

        return $return;
    }

    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        return $this->flashes = [];
    }

    /**
     * Queue a FlashBag message.
     *
     * @param string $level
     * @param string $message
     */
    public function queue($level, $message)
    {
        $this->flashes[$level][] = $message;
    }

    /**
     * We iterate as some flashes might validly be set in Twig and we shouldn't
     * wipe them.
     *
     * {@inheritdoc}
     */
    public function flush(FlashBagInterface $bag)
    {
        foreach ($this->flashes as $type => $messages) {
            foreach ($messages as $message) {
                $bag->add($type, $message);
            }
            unset ($this->flashes[$type]);
        }
    }
}
