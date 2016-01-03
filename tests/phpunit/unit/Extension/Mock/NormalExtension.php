<?php

namespace Bolt\Tests\Extension\Mock;

use Bolt\Extension\SimpleExtension;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Mock extension that extends SimpleExtension.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class NormalExtension extends SimpleExtension
{
    /**
     * {@inheritdoc}
     */
    public function subscribe(EventDispatcherInterface $dispatcher)
    {
        $dispatcher->addListener('dropbear.sighting', [$this, 'eventListener']);
    }

    public function eventListener()
    {
        throw new \RuntimeException('Drop Bear Alert!');
    }
}
