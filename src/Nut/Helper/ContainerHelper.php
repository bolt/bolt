<?php

namespace Bolt\Nut\Helper;

use Pimple as Container;
use Symfony\Component\Console\Helper\Helper;

/**
 * Bridges Container to commands.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
class ContainerHelper extends Helper
{
    /** @var Container */
    private $container;

    /**
     * Constructor.
     *
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * @return Container
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'container';
    }
}
