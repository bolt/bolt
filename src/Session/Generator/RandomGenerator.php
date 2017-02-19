<?php

namespace Bolt\Session\Generator;

use Bolt\Security\Random\Generator;

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
        @trigger_error(sprintf('%s is deprecated and will be removed in version 4.0.', __CLASS__), E_USER_DEPRECATED);

        return $this->generator->generateString($this->length);
    }
}
