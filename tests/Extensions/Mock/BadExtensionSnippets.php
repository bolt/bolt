<?php
namespace Bolt\Tests\Extensions\Mock;

use Bolt\Extensions\ExtensionInterface;
use Bolt\Application;

/**
 * Class to test correct operation and locations of composer configuration.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 *
 */
class BadExtensionSnippets extends Extension
{

    public function getSnippets()
    {
        throw new \Exception("BadExtensionSnippets", 1);
    }

    
    public function getName()
    {
        return "badextensionsnippets";
    }

   
}
