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
class SnippetCallbackExtension extends Extension
{
    public function __construct(Application $app)
    {
        $snippet = (new Snippet())
            ->setLocation(Target::START_OF_HEAD)
            ->setCallback([$this, 'snippetCallBack'])
        ;
        $app['asset.queue.snippet']->add($snippet);
    }

    public function snippetCallBack()
    {
        return '<meta name="test-snippet" />';
    }
}
