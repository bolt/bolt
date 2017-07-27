<?php

namespace Bolt\Legacy\PasswordLib;

use PasswordLib\Random\Generator;
use PasswordLib\Random\Source;

/**
 * A PasswordLib Random Generator that uses random_bytes.
 *
 * @internal
 *
 * @deprecated Deprecated since 3.3, to be removed in 4.0.
 */
final class PasswordLibRandomGenerator extends Generator
{
    /**
     * Constructor.
     */
    public function __construct()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function generate($size)
    {
        return random_bytes($size);
    }

    /**
     * {@inheritdoc}
     */
    public function generateInt($min = 0, $max = PHP_INT_MAX)
    {
        return random_int($min, $max);
    }

    /**
     * {@inheritdoc}
     */
    public function generateString($length, $characters = '')
    {
        return substr(bin2hex(random_bytes($length)), 0, $length);
    }

    /**
     * {@inheritdoc}
     */
    public function addSource(Source $source)
    {
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getMixer()
    {
        throw new \RuntimeException('This does not use Mixers.');
    }

    /**
     * {@inheritdoc}
     */
    public function getSources()
    {
        return [];
    }
}
