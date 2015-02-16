<?php
namespace Bolt\Tests\Composer\Action;

use Bolt\Tests\BoltUnitTest;
use Bolt\Composer\Action\InstallPackage;
use Bolt\Composer\PackageManager;


/**
 * Class to test src/Composer/Action/InstallPackage.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 *
 */
class InstallPackageTest extends BoltUnitTest
{
    
    
    public function testRun()
    {
        $app = $this->getApp();
        
        $action = new InstallPackage($app);
        $result = $action->execute();
        $this->assertEquals(0, $result);
    }
    
}
