<?php
namespace Bolt\Tests\Extensions\Mock;

/**
 * Class to test correct operation and locations of composer configuration.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class ExtensionWithTwig extends Extension
{
    public function getName()
    {
        return "extensionwithtwig";
    }

    public function getTwigExtensions()
    {
        return array(
            new TwigExtension(),
            new BadTwigExtension()
        );
    }
}
