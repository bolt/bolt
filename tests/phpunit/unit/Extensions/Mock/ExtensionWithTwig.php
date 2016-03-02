<?php
namespace Bolt\Tests\Extensions\Mock;

/**
 * Class to test correct operation and locations of composer configuration.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class ExtensionWithTwig extends Extension
{
    public function getTwigExtensions()
    {
        return [
            new TwigExtension(),
            new BadTwigExtension(),
        ];
    }
}
