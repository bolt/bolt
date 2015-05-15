<?php

namespace Bolt\Logger;

use Psr\Log\AbstractLogger;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBag;

/**
 * Storage logger for FlashBag messages.
 *
 * This class stores messages in memory until they are ready to be dispatched,
 * as this allows them to be set without a session being started and a cookie
 * set when it might not be valid.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class FlashLogger extends AbstractLogger
{
    /** @var array $flash */
    private $flashes = [];

    const SUCCESS = 'success';

    /**
     * Add success messages.
     *
     * @param string $message
     * @param array  $context
     */
    public function success($message, array $context = array())
    {
        $this->log(self::SUCCESS, $message, $context);
    }

    /**
     * @see \Psr\Log\LoggerInterface::log()
     */
    public function log($level, $message, array $context = array())
    {
        $this->flashes[$level][] = $message;
    }

    /**
     * Flush stored flashes to the Symfony.
     *
     * We iterate as some flashes might validly be set in Twig and we shouldn't
     * wipe them.
     *
     * @param FlashBag $flashbag
     */
    public function flush(FlashBag &$flashbag)
    {
        foreach ($this->flashes as $type => $messages) {
            foreach ($messages as $message) {
                $flashbag->add($type, $message);
            }
        }
    }
}
