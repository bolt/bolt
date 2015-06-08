<?php

namespace Bolt\Session\Generator;

use RandomLib\Generator;

/**
 * Generates session IDs with RandomLib
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
class RandomGenerator implements GeneratorInterface
{
    /** @var Generator */
    protected $generator;
    /** @var integer */
    protected $length;
    /** @var integer */
    protected $characters;

    /**
     * Constructor.
     *
     * @param Generator $generator
     * @param integer   $length
     * @param integer   $characters
     */
    public function __construct(Generator $generator, $length = 32, $characters = Generator::CHAR_ALNUM)
    {
        $this->generator = $generator;
        $this->length = $length;
        $this->characters = $characters;
    }

    /**
     * {@inheritdoc}
     */
    public function generateId()
    {
        return $this->generator->generateString($this->length, $this->characters);
    }
}
