<?php

namespace Bolt\Tests\Extension\Mock;

use Bolt\Extension\SimpleExtension;

/**
 *  Mock extension that extends SimpleExtension for testing the ControllerMountTrait.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class ControllerMountExtension extends SimpleExtension
{
    /**
     * {@inheritdoc}
     */
    protected function registerFrontendControllers()
    {
        return [
            '/' => new Controller(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function registerBackendControllers()
    {
        return [
            '/' => new Controller(),
        ];
    }
}
