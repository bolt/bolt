<?php
namespace Bolt\Tests\Extensions\Mock;

use Bolt\Asset\Snippet\Snippet;
use Bolt\Asset\Target;
use Bolt\BaseExtension;
use Silex\Application;

/**
 * Class to test correct operation and locations of composer configuration.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class Extension extends BaseExtension
{
    public function __construct(Application $app)
    {
        $snippet = (new Snippet())
            ->setLocation(Target::END_OF_HEAD)
            ->setCallback([$this, 'snippetCallBack'])
        ;
        $app['asset.queue.snippet']->add($snippet);
    }

    public function initialize()
    {
    }

    public function getConfig()
    {
    }

    public function getSnippets()
    {
    }

    public function snippetCallBack()
    {
        return '<meta name="test-snippet" />';
    }

    public function getExtensionConfig()
    {
        return [];
    }
}
