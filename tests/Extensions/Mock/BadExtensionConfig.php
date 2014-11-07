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
class BadExtensionConfig extends Extension
{

    public function getConfig()
    {
        throw new \Exception("BadExtensionConfig", 1);
    }
    
    public function getName()
    {
        return "badextensionconfig";
    }

   
}
