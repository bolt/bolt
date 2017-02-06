<?php

namespace Bolt\Security\Random;

/**
 * Random generator.
 *
 * NOTE: PHP 5 polyfill for random_bytes() and random_int() provided by
 * paragonie/random_compat Composer library.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class Generator
{
    /**
     * Generate a random byte string.
     *
     * @param int $length
     *
     * @return mixed
     */
    public function generate($length)
    {
        return random_bytes($length);
    }

    /**
     * Generate a random integer.
     *
     * @param int $min Lower bound of the range to generate
     * @param int $max Upper bound of the range to generate
     *
     * @return int
     */
    public function generateInt($min = 0, $max = PHP_INT_MAX)
    {
        return random_int($min, $max);
    }

    /**
     * Generate a random string.
     *
     * @param int $length
     *
     * @return string
     */
    public function generateString($length)
    {
        return substr(bin2hex(random_bytes($length)), 0, $length);
    }
}
