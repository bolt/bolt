<?php

namespace Bolt\Tests\Extension\Mock;

use Bolt\Extension\AbstractExtension;

/**
 * Mock extension that only extends AbstractExtension.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class BasicExtension extends AbstractExtension
{
    /**
     * {@inheritdoc}
     */
    public function getServiceProviders()
    {
        return [$this];
    }

    /**
     * {@inheritdoc}
     */
    public function getContainer()
    {
        return parent::getContainer();
    }
}
