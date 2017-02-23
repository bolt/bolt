<?php

namespace Bolt\Session\Generator;

use Bolt\Helpers\Deprecated;
use Bolt\Security\Random\Generator;

Deprecated::cls(RandomGenerator::class, 3.3, NativeGenerator::class);

/**
 * Generates session IDs.
 *
 * @deprecated Deprecated since 3.3, to be removed in 4.0. Use \Bolt\Session\Generator\NativeGenerator
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
class RandomGenerator implements GeneratorInterface
{
    /** @var Generator */
    protected $generator;
    /** @var integer */
    protected $length;

    /**
     * Constructor.
     *
     * @param Generator $generator
     * @param integer   $length
     */
    public function __construct($generator, $length = 32)
    {
        $this->generator = $generator;
        $this->length = $length;
    }

    /**
     * {@inheritdoc}
     */
    public function generateId()
    {
        return $this->generator->generateString($this->length);
    }
}
