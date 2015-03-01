<?php
namespace Bolt\Tests\Extensions\Mock;

/**
 * Class to test correct operation and locations of composer configuration.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class TwigExtension extends \Twig_Extension
{
    public function getName()
    {
        return "testTwig";
    }

    public function isSafe()
    {
        return true;
    }
}
