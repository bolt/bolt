<?php
namespace Bolt\Tests\Composer\Action;

use Bolt\Composer\Action\UpdatePackage;
use Bolt\Tests\BoltUnitTest;

/**
 * Class to test src/Composer/Action/UpdatePackage.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class UpdatePackageTest extends ActionUnitTest
{
    public function testRun()
    {
        $app = $this->getApp();

        $result = $app['extend.action']['update']->execute();
        $this->assertEquals(0, $result);
    }
}
