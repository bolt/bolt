<?php

namespace Bolt\Tests\Composer\Action;

use Bolt\Common\Json;
use Bolt\Composer\Action\Options;
use Bolt\Composer\JsonManager;
use Bolt\Filesystem\Handler\JsonFile;
use Bolt\Filesystem\Manager;

/**
 * @covers \Bolt\Composer\JsonManager
 *
 * @author Ross Riley <riley.ross@gmail.com>
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class JsonManagerTest extends ActionUnitTest
{
    public function testExecute()
    {
        $app = $this->getApp();
        /** @var JsonManager $jsonManager */
        $jsonManager = $app['extend.manager.json'];
        /** @var Options $options */
        $options = $app['extend.action.options'];
        /** @var Manager $fsManager */
        $fsManager = $app['filesystem'];
        $filesystem = $fsManager->getFilesystem('extensions');
        /** @var JsonFile $composerJson */
        $composerJson = $filesystem->getFile('extensions://composer.json');
        $composerJson->delete();

        $expected = ['extra' => ['bolt-test' => true]];
        $jsonManager->init($options->composerJson()->getFullPath(), $expected);

        $result = $composerJson->read();
        $this->assertJson($result);
        $this->assertSame(Json::dump($expected, Json::HUMAN), $result);
    }

    public function testWrite()
    {
        $app = $this->getApp();
        /** @var JsonManager $jsonManager */
        $jsonManager = $app['extend.manager.json'];
        /** @var Manager $fsManager */
        $fsManager = $app['filesystem'];
        $filesystem = $fsManager->getFilesystem('extensions');
        /** @var JsonFile $composerJson */
        $composerJson = $filesystem->getFile('extensions://composer.json');
        $composerJson->delete();

        $jsonManager->update();
        $result = $composerJson->read();
        $this->assertJson($result);

        $result = Json::parse($result);
        $this->assertArrayHasKey('autoload', $result);
        $this->assertArrayHasKey('config', $result);
        $this->assertArrayHasKey('description', $result);
        $this->assertArrayHasKey('extra', $result);
        $this->assertArrayHasKey('license', $result);
        $this->assertArrayHasKey('minimum-stability', $result);
        $this->assertArrayHasKey('name', $result);
        $this->assertArrayHasKey('prefer-stable', $result);
        $this->assertArrayHasKey('provide', $result);
        $this->assertArrayHasKey('repositories', $result);
        $this->assertArrayHasKey('scripts', $result);
    }
}
