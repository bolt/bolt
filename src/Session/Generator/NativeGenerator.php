<?php

namespace Bolt\Session\Generator;

/**
 * Generator for session IDs with native functions.
 * This is probably only useful for testing.
 *
 * WARNING: This is NOT cryptographically secure.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
class NativeGenerator implements GeneratorInterface
{
    /**
     * {@inheritdoc}
     */
    public function generateId()
    {
        return hash('sha256', uniqid(mt_rand()));
    }
}
