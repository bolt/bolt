<?php
namespace Bolt\Tests\Composer\Action;

use Bolt\Composer\Action\SearchPackage;

/**
 * Class to test src/Composer/Action/SearchPackage.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class SearchPackageTest extends ActionUnitTest
{
    public function testRun()
    {
        $app = $this->getApp();

        $result = $app['extend.action']['search']->execute(['gawain/clippy']);
        $this->assertTrue(is_array($result));
    }
}
