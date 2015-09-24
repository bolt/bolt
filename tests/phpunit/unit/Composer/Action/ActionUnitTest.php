<?php
namespace Bolt\Tests\Composer\Action;

use Bolt\Tests\BoltUnitTest;

abstract class ActionUnitTest extends BoltUnitTest
{
    public function setUp()
    {
        $app = $this->getApp();
        $action = $app['extend.action']['json'];
        $action->updateJson();
    }

    protected function getApp($boot = true)
    {
        $bolt = parent::getApp();
        $bolt['extend.action.options']['basedir'] = $bolt['resources']->getPath('extensions');
        $bolt['extend.action.options']['composerjson'] = $bolt['resources']->getPath('extensions') . '/composer.json';
        return $bolt;
    }
}
