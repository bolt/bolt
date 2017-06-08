<?php

namespace Bolt\Tests\Extensions\Mock;

use Twig\Extension\AbstractExtension;

/**
 * Class to test correct operation and locations of composer configuration.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class TwigExtension extends AbstractExtension
{
    public function getName()
    {
        return 'testTwig';
    }

    public function isSafe()
    {
        return true;
    }
}
