<?php

namespace Bolt\Tests\Composer\Action;

/**
 * Class to test src/Composer/Action/CheckPackage.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class CheckPackageTest extends ActionUnitTest
{
    public function tearDown()
    {
        $app = $this->getApp();
        $action = $app['extend.action']['remove'];
        $action->execute(['gawain/clippy']);
    }

    public function testConstruct()
    {
        $app = $this->getApp();
        $result = $app['extend.action']['check']->execute();
        $this->assertTrue(is_array($result['updates']));
        $this->assertTrue(is_array($result['installs']));
    }

    public function testNewlyAdded()
    {
        $app = $this->getApp();
        $json = $app['extend.manager.json']->update();
        $json['require']['gawain/clippy'] = '~2.0';
        $app['extend.action.options']->composerJson()->delete();
        $app['extend.manager.json']->init($app['extend.action.options']->composerJson()->getFullPath(), $json);

        $result = $app['extend.action']['check']->execute();
        $this->assertEquals('gawain/clippy', $result['installs'][0]['name']);
    }

    public function testUpdateCheck()
    {
        $app = $this->getApp();
        $action = $app['extend.action']['require'];
        $action->execute(['name' => 'gawain/clippy', 'version' => '~2.0']);

        $action = $app['extend.action']['check'];
        $result = $action->execute();
        $this->assertTrue(is_array($result['updates']));
    }
}
