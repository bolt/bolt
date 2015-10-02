<?php
namespace Bolt\Tests\Composer\Action;

use Bolt\Composer\Action\InstallPackage;

/**
 * Class to test src/Composer/Action/InstallPackage.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class InstallPackageTest extends ActionUnitTest
{
    public function testRun()
    {
        $app = $this->getApp();

        $result = $app['extend.action']['install']->execute();
        $this->assertEquals(0, $result);
    }
}
