<?php
namespace Bolt\Tests\Extensions\Mock;

use Bolt\Application;
use Bolt\Asset\Snippet\Snippet;
use Bolt\Asset\Target;

/**
 * Class to test correct operation and locations of composer configuration.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class BadExtensionSnippets extends Extension
{
    public function __construct(Application $app)
    {
        $snippet = (new Snippet())
            ->setLocation(Target::END_OF_HEAD)
            ->setCallback([$this, 'badSnippetCallBack'])
        ;
        $app['asset.queue.snippet']->add($snippet);
    }

    public function getSnippets()
    {
    }
}
