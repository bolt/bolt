<?php
namespace Bolt\Tests\Composer;

use Bolt\Tests\BoltUnitTest;
use Bolt\Composer\PackageManager;

/**
 * Class to test src/Composer/PackageManager.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 *
 */
class PackageManagerTest extends BoltUnitTest
{

    public function testConstruct()
    {

    }
    
    public function testGetRootDependencies()
    {
        $app = $this->getApp();
        $manager = new PackageManager($app);
        $dependencies = $manager->getRootDependencies($app);

        $this->assertTrue(is_array($dependencies));
    }
}
