<?php
namespace Bolt\Tests\Composer\Action;

use Bolt\Composer\Action\BoltExtendJson;
use Bolt\Composer\Action\CheckPackage;
use Bolt\Composer\Action\RemovePackage;
use Bolt\Composer\Action\RequirePackage;
use Bolt\Tests\BoltUnitTest;

/**
 * Class to test src/Composer/Action/CheckPackage.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class CheckPackageTest extends BoltUnitTest
{
    public function tearDown()
    {
        $app = $this->getApp();
        $action = new RemovePackage($app);
        $action->execute(['gawain/clippy']);
    }

    public function testConstruct()
    {
        $app = $this->getApp();
        $action = new CheckPackage($app);
        $result = $action->execute();
        $this->assertTrue(is_array($result['updates']));
        $this->assertTrue(is_array($result['installs']));
    }

    public function testNewlyAdded()
    {
        $app = $this->getApp();
        $options = $app['extend.manager']->getOptions();
        $boltJson = new BoltExtendJson($options);
        $json = $boltJson->updateJson($app);
        $json['require']['gawain/clippy'] = '~2.0';
        $boltJson->execute($options['composerjson'], $json);

        $action = new CheckPackage($app);
        $result = $action->execute();
        $this->assertEquals('gawain/clippy', $result['installs'][0]['name']);
    }

    public function testUpdateCheck()
    {
        $app = $this->getApp();
        $action = new RequirePackage($app);
        $action->execute(['name' => 'gawain/clippy', 'version' => '~2.0']);

        $action = new CheckPackage($app);
        $result = $action->execute();
        $this->assertTrue(is_array($result['updates']));
    }
}
