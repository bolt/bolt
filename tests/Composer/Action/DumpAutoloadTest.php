<?php
namespace Bolt\Tests\Composer\Action;

use Bolt\Composer\Action\DumpAutoload;
use Bolt\Tests\BoltUnitTest;

/**
 * Class to test src/Composer/Action/DumpAutoload.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class DumpAutoloadTest extends BoltUnitTest
{
    public function testConstruct()
    {
        $app = $this->getApp();
        $autoload = $app['resources']->getPath('extensionspath/vendor/autoload.php');
        @unlink($autoload);
        $action = new DumpAutoload($app);
        $action->execute();
        $this->assertTrue(is_readable($app['resources']->getPath('extensionspath/vendor/autoload.php')));
    }
}
