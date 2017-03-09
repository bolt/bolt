<?php

namespace Bolt\Tests\Composer\Action;

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
        $autoload = $app['path_resolver']->resolve('%extensions%/vendor/autoload.php');
        @unlink($autoload);
        $app['extend.action']['autoload']->execute();
        $this->assertTrue(is_readable($autoload));
    }
}
