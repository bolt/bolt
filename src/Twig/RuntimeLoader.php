<?php

namespace Bolt\Twig;

use Pimple as Container;
use Twig\RuntimeLoader\RuntimeLoaderInterface;

/**
 * Twig RuntimeLoader implementation.
 *
 * @internal based on the similar class from Silex 2 and will probably be
 * replaced when Bolt v4 switches to Silex 2
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class RuntimeLoader implements RuntimeLoaderInterface
{
    /** @var Container */
    private $container;
    /** @var array */
    private $mapping;

    /**
     * Constructor.
     *
     * @param Container $container
     * @param array     $mapping
     */
    public function __construct(Container $container, array $mapping)
    {
        $this->container = $container;
        $this->mapping = $mapping;
    }

    /**
     * {@inheritdoc}
     */
    public function load($class)
    {
        if (!isset($this->mapping[$class])) {
            return null;
        }

        return $this->container[$this->mapping[$class]];
    }
}
