<?php

namespace Bolt\Tests\Extension\Mock;

use Bolt\Extension\AbstractExtension;

/**
 * Mock extension that only extends AbstractExtension and doesn't has a legacy name.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class Extension extends AbstractExtension
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
