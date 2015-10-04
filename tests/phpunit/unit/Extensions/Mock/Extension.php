<?php
namespace Bolt\Tests\Extensions\Mock;

use Bolt\Application;
use Bolt\Asset\Target;
use Bolt\Extensions\ExtensionInterface;

/**
 * Class to test correct operation and locations of composer configuration.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class Extension implements ExtensionInterface
{
    public function __construct(Application $app)
    {
        $app['asset.queue.snippet']->add(Target::END_OF_HEAD, [$this, 'snippetCallBack']);
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

    public function getName()
    {
        return 'testext';
    }
}
