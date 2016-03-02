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
class FlashLogger implements FlashLoggerInterface, FlashBagAttachableInterface
{
    /** @var array $flash */
    protected $flashes = [];
    /** @var FlashBagInterface|null */
    protected $flashBag;

    /**
     * {@inheritdoc}
     */
    public function danger($message)
    {
        $this->add(self::DANGER, $message);
    }

    /**
     * {@inheritdoc}
     */
    public function error($message)
    {
        $this->add(self::ERROR, $message);
    }

    /**
     * {@inheritdoc}
     */
    public function info($message)
    {
        $this->add(self::INFO, $message);
    }

    /**
     * {@inheritdoc}
     */
    public function success($message)
    {
        $this->add(self::SUCCESS, $message);
    }

    /**
     * {@inheritdoc}
     */
    public function warning($message)
    {
        $this->add(self::WARNING, $message);
    }

    /**
     * {@inheritdoc}
     */
    public function configuration($message)
    {
        $this->add(self::CONFIGURATION, $message);
    }

    /**
     * {@inheritdoc}
     */
    public function has($type)
    {
        if ($this->flashBag) {
            return $this->flashBag->has($type);
        }

        return array_key_exists($type, $this->flashes) && $this->flashes[$type];
    }

    /**
     * {@inheritdoc}
     */
    public function get($type, array $default = [])
    {
        if ($this->flashBag) {
            return $this->flashBag->get($type, $default);
        }

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
        if ($this->flashBag) {
            $this->flashBag->clear();

            return;
        }
        $this->flashes = [];
    }

    /**
     * Add a message.
     *
     * @param string $type
     * @param string $message
     */
    public function add($type, $message)
    {
        if ($this->flashBag) {
            $this->flashBag->add($type, $message);

            return;
        }
        $this->flashes[$type][] = $message;
    }

    /**
     * {@inheritdoc}
     */
    public function keys()
    {
        if ($this->flashBag) {
            return $this->flashBag->keys();
        }

        return array_keys($this->flashes);
    }

    /**
     * {@inheritdoc}
     */
    public function attachFlashBag(FlashBagInterface $flashBag)
    {
        if ($this->flashBag) {
            return;
        }
        $this->flashBag = $flashBag;

        // We iterate as some flashes might validly be set in Twig
        // and we shouldn't wipe them.
        foreach ($this->flashes as $type => $messages) {
            foreach ($messages as $message) {
                $flashBag->add($type, (string) $message);
            }
            unset($this->flashes[$type]);
        }
    }

    /**
     * Return whether a FlashBag has been attached
     *
     * @return bool
     */
    public function isFlashBagAttached()
    {
        return (bool) $this->flashBag;
    }
}
