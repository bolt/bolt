<?php

namespace Bolt\Session\Generator;

/**
 * Generator for session IDs
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
interface GeneratorInterface
{
    /**
     * Generate a session ID
     *
     * @return string
     */
    public function generateId();
}
