<?php

namespace Bolt\Tests\Composer\Action;

/**
 * Class to test src/Composer/Action/SearchPackage.
 *
 * @group slow
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class SearchPackageTest extends ActionUnitTest
{
    public function testRun()
    {
        $app = $this->getApp();

        $result = $app['extend.action']['search']->execute(['gawain/clippy']);
        $this->assertInternalType('array', $result);
    }
}
