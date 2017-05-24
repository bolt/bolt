<?php

namespace Bolt\Tests\Composer;

use Bolt\Tests\BoltUnitTest;

/**
 * Class to test Bolt\Composer\JsonManager class.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class JsonManagerTest extends BoltUnitTest
{
    public function testExecute()
    {
        $app = $this->getApp();
        $app['filesystem']->get('extensions://composer.json')->delete();
        $app['extend.manager.json']->init(
            $app['extend.action.options']->composerJson()->getFullPath(),
            ['extra' => ['bolt-test' => true]]
        );
    }

    public function testWrite()
    {
        $app = $this->getApp();
        $app['extend.manager.json']->update();
    }
}
