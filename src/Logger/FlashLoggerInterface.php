<?php

namespace Bolt\Logger;

use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;

/**
 * FlashBag logger interface.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
interface FlashLoggerInterface
{
    /**
     * Display a 'danger' message.
     *
     * @param string $message
     */
    public function danger($message);

    /**
     * Display a 'error' message.
     *
     * @param string $message
     */
    public function error($message);

    /**
     * Display a 'info' message.
     *
     * @param string $message
     */
    public function info($message);

    /**
     * Display a 'success' message.
     *
     * @param string $message
     */
    public function success($message);

    /**
     * Display a 'warning' message.
     *
     * @param string $message
     */
    public function warning($message);

    /**
     * Get a message from the stack.
     *
     * @param string $type
     * @param array  $default
     *
     * @return array
     */
    public function get($type, array $default = array());

    /**
     * Has messages for a given type?
     *
     * @param string $type
     *
     * @return boolean
     */
    public function has($type);

    /**
     * Clear out messages.
     */
    public function clear();

    /**
     * Flush stored flashes to the Symfony FlashBag.
     *
     * @param FlashBagInterface $bag
     */
    public function flush(FlashBagInterface $bag);
}
