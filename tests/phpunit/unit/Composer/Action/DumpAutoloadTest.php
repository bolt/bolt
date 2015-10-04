<?php
namespace Bolt\Tests\Composer\Action;

use Bolt\Composer\Action\DumpAutoload;

/**
 * Class to test src/Composer/Action/DumpAutoload.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class DumpAutoloadTest extends ActionUnitTest
{
    public function testConstruct()
    {
        $app = $this->getApp();
        $autoload = $app['resources']->getPath('extensionspath/vendor/autoload.php');
        @unlink($autoload);
        $app['extend.action']['autoload']->execute();
        $this->assertTrue(is_readable($app['resources']->getPath('extensionspath/vendor/autoload.php')));
    }
}
