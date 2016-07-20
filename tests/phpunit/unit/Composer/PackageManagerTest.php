<?php
namespace Bolt\Tests\Composer;

use Bolt\Composer\PackageManager;
use Bolt\Tests\BoltFunctionalTestCase;

/**
 * Class to test src/Composer/CommandRunner.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class PackageManagerTest extends BoltFunctionalTestCase
{
    public function testConstruct()
    {
        $app = $this->getApp();
        $manager = new PackageManager($app);
    }
}
