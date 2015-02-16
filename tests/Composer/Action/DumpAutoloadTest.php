<?php
namespace Bolt\Tests\Composer\Action;

use Bolt\Tests\BoltUnitTest;
use Bolt\Composer\Action\DumpAutoload;
use Bolt\Composer\PackageManager;


/**
 * Class to test src/Composer/Action/DumpAutoload.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 *
 */
class DumpAutoloadTest extends BoltUnitTest
{
    
    
    public function testConstruct()
    {
        $app = $this->getApp();
        $autoload = $app['resources']->getPath('extensionspath/vendor/autoload.php');
        @unlink($autoload);
        $action = new DumpAutoload($app);
        $result = $action->execute();
        $this->assertTrue(is_readable($app['resources']->getPath('extensionspath/vendor/autoload.php')));
    }

    
    
    
}
