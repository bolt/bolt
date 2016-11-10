<?php

namespace Bolt\Session\Generator;

use Bolt\Security\Random\Generator;

/**
 * Generates session IDs.
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
    public function __construct(Generator $generator, $length = 32)
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
