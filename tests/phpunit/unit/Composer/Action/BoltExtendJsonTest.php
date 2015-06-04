<?php
namespace Bolt\Tests\Composer\Action;

use Bolt\Composer\Action\BoltExtendJson;
use Bolt\Composer\PackageManager;
use Bolt\Tests\BoltUnitTest;

/**
 * Class to test src/Composer/Action/BoltExtendJson.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class BoltExtendJsonTest extends ActionUnitTest
{
    public function testExecute()
    {
        $app = $this->getApp();
        $app['extend.action']['json']->execute(
            $app['extend.action.options']['composerjson'],
            ['extra' => ['bolt-test' => true]]
        );
    }

    public function testWrite()
    {
        $app = $this->getApp();
        $app['extend.action']['json']->updateJson();
    }
}
