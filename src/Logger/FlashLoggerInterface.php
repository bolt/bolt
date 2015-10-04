<?php

namespace Bolt\Logger;

/**
 * FlashBag logger interface.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 * @author Carson Full <carsonfull@gmail.com>
 */
interface FlashLoggerInterface
{
    const DANGER  = 'danger';
    const ERROR   = 'error';
    const INFO    = 'info';
    const SUCCESS = 'success';
    const WARNING = 'warning';

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
     * Add a message.
     *
     * @param string $type
     * @param string $message
     */
    public function add($type, $message);

    /**
     * Get a message from the stack.
     *
     * @param string $type
     * @param array  $default
     *
     * @return array
     */
    public function get($type, array $default = []);

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
     * Returns a list of all defined types.
     *
     * @return array
     */
    public function keys();
}
