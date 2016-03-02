<?php

namespace Bolt\Session\Generator;

/**
 * Generator for session IDs with native random_bytes() function.
 *
 * This requires PHP 7.0 or the "paragonie/random_compat" library.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
class NativeGenerator implements GeneratorInterface
{
    /** @var int */
    private $length;

    /**
     * Constructor.
     *
     * @param int $length The length of the random string that should be returned in bytes.
     */
    public function __construct($length = 32)
    {
        $this->length = $length;
    }

    /**
     * {@inheritdoc}
     */
    public function generateId()
    {
        return random_bytes($this->length);
    }
}
