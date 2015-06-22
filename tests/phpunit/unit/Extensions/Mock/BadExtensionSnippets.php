<?php
namespace Bolt\Tests\Extensions\Mock;

use Bolt\Application;
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
        $app['asset.queue.snippet']->add(Target::END_OF_HEAD, [$this, 'badSnippetCallBack'], __CLASS__);
    }

    public function getSnippets()
    {
    }

    public function getName()
    {
        return 'badextensionsnippets';
    }
}
