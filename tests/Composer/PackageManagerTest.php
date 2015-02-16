<?php
namespace Bolt\Tests\Composer;

use Bolt\Tests\BoltUnitTest;
use Bolt\Composer\PackageManager;

/**
 * Class to test src/Composer/CommandRunner.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 *
 */
class PackageManagerTest extends BoltUnitTest
{

    public function testConstruct()
    {
        $app = $this->getApp();
        $manager = new PackageManager($app);
    }
}
