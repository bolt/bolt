<?php
namespace Bolt\Tests\Composer\Action;

use Bolt\Tests\BoltUnitTest;
use Bolt\Composer\Action\UpdatePackage;
use Bolt\Composer\PackageManager;


/**
 * Class to test src/Composer/Action/UpdatePackage.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 *
 */
class UpdatePackageTest extends BoltUnitTest
{
    
    
    public function testRun()
    {
        $app = $this->getApp();
        
        $action = new UpdatePackage($app);
        $result = $action->execute();
        $this->assertEquals(0, $result);

    }
    
}
