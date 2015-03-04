<?php
namespace Bolt\Tests\Composer;

use Bolt\Composer\PackageManager;
use Bolt\Tests\BoltUnitTest;

/**
 * Class to test src/Composer/CommandRunner.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class PackageManagerTest extends BoltUnitTest
{
    public function testConstruct()
    {
        $app = $this->getApp();
        $manager = new PackageManager($app);
    }
}
