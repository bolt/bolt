<?php
namespace Bolt\Tests\Extensions;

use Bolt\BaseExtension;
use Bolt\Provider\NutServiceProvider;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Class to test src/BaseExtension.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 *
 * @runTestsInSeparateProcesses
 */
class BaseExtensionTest extends AbstractExtensionsUnitTest
{
    public function testSetup()
    {
        $this->markTestIncomplete('Update required');

        $app = $this->getApp();
        $ext = $this->getMockForAbstractClass('Bolt\BaseExtension', [$app]);
        $this->assertNotEmpty($ext->getBasePath());
        $this->assertNotEmpty($ext->getBaseUrl());
    }

    public function testComposerLoading()
    {
        $this->markTestIncomplete('Update required');

        $this->localExtensionInstall();
        $app = $this->getApp();
        $this->assertTrue($app['extensions']->isEnabled('testlocal'));
        $config = $app['extensions.testlocal']->getExtensionConfig();
        $this->assertNotEmpty($config);
    }

    public function testGetBasePath()
    {
        $this->markTestIncomplete('Update required');

        $app = $this->makeApp();
        $ext = $this->getMockForAbstractClass('Bolt\BaseExtension', [$app]);
        $this->assertNotEmpty(strpos($ext->getBasePath()->string(), 'MockObject'));
    }

    public function testGetBaseUrl()
    {
        $this->markTestIncomplete('Update required');

        $app = $this->makeApp();
        $ext = $this->getMockForAbstractClass('Bolt\BaseExtension', [$app]);
        $this->assertEquals(0, strpos($ext->getBaseUrl(), '/extensions'));
    }

    public function testGetComposerNameDefault()
    {
        $this->markTestIncomplete('Update required');

        $app = $this->makeApp();
        $ext = $this->getMockForAbstractClass('Bolt\BaseExtension', [$app]);
        $this->assertNull($ext->getComposerName());
    }

    public function testGetComposerName()
    {
        $this->markTestIncomplete('Update required');

        $app = $this->makeApp();
        $ext = $this->getMockForAbstractClass('Bolt\BaseExtension', [$app]);
        $this->assertNull($ext->getComposerName());
        $ext->setComposerConfiguration(['name' => 'valuefrommock']);

        $this->assertEquals('valuefrommock', $ext->getComposerName());
    }

    public function testSetComposerConfiguration()
    {
        $this->markTestIncomplete('Update required');

        if (version_compare(PHP_VERSION, '7', '>=')) {
            $this->markTestSkipped('Revist this test when exception handling stablises.');
        }
        $app = $this->makeApp();
        $ext = $this->getMockForAbstractClass('Bolt\BaseExtension', [$app]);
        $this->setExpectedException(get_class(new \PHPUnit_Framework_Error('', 0, '', 1)));
        $ext->setComposerConfiguration('stringsinvalid');
    }

    public function testGetExtensionConfig()
    {
        $this->markTestIncomplete('Update required');

        $config = ['name' => 'mock', 'description' => 'mocking'];
        $app = $this->makeApp();
        $ext = $this->getMockForAbstractClass('Bolt\BaseExtension', [$app]);
        $ext->setComposerConfiguration($config);
        $result = $ext->getExtensionConfig();
        $this->assertArrayHasKey('mock', $result);
        $this->assertEquals('MockObject', $result['mock']['name']);
        $this->assertEquals($config, $result['mock']['json']);
    }

    public function testGetConfig()
    {
        $this->markTestIncomplete('Update required');

        $app = $this->makeApp();
        $ext = new Mock\ExtendedExtension($app);

        $mockConfig = "---\nname: mock\ndescription: mocking\n";
        $mockLocalConfig = "---\nversion: local\n";

        $this->php
            ->expects($this->any())
            ->method('file_exists')
            ->will($this->returnValue(true));

        $this->php
            ->expects($this->any())
            ->method('is_readable')
            ->will($this->returnValue(true));

        $this->php
            ->expects($this->any())
            ->method('file_get_contents')
            ->will($this->returnValue($mockConfig));

        $fetched = $ext->getConfig();
        $this->assertEquals('mocking', $fetched['description']);
    }

    public function testGetConfigUnreadable()
    {
        $this->markTestIncomplete('Update required');

        $app = $this->makeApp();
        $ext = new Mock\ExtendedExtension($app);

        $logger = $this->getMock('Monolog\Logger', ['critical'], [$app]);

        $logger->expects($this->any())
            ->method('critical')
            ->with($this->matchesRegularExpression('/Couldn\'t read/'));

        $app['logger.system'] = $logger;

        $this->php
            ->expects($this->any())
            ->method('file_exists')
            ->will($this->returnValue(true));

        $this->php
            ->expects($this->any())
            ->method('is_readable')
            ->will($this->returnValue(false));

        $ext->getConfig();
    }

    public function testGetConfigCreatesFile()
    {
        $this->markTestIncomplete('Update required');

        $app = $this->makeApp();
        $ext = new Mock\ExtendedExtension($app);

        $logger = $this->getMock('Monolog\Logger', ['info'], [$app]);

        $logger->expects($this->any())
            ->method('info')
            ->with($this->matchesRegularExpression('/Copied/'));

        $app['logger.system'] = $logger;

        $this->php
            ->expects($this->any())
            ->method('file_exists')
            ->will($this->returnValue(false));

        $this->php
            ->expects($this->any())
            ->method('is_readable')
            ->will($this->returnValue(true));

        $this->php
            ->expects($this->any())
            ->method('is_dir')
            ->will($this->returnValue(true));

        $this->php
            ->expects($this->once())
            ->method('copy')
            ->with($this->matchesRegularExpression('/dist/'))
            ->will($this->returnValue(true));

        $ext->getConfig();
    }

    public function testGetConfigCreatesFileFailure()
    {
        $this->markTestIncomplete('Update required');

        $app = $this->makeApp();
        $ext = new Mock\ExtendedExtension($app);

        $logger = $this->getMock('Monolog\Logger', ['critical'], [$app]);

        $logger->expects($this->any())
            ->method('critical')
            ->with($this->matchesRegularExpression('/File is not writable/'));

        $app['logger.system'] = $logger;

        $this->php
            ->expects($this->any())
            ->method('file_exists')
            ->will($this->returnValue(false));

        $this->php
            ->expects($this->any())
            ->method('is_readable')
            ->will($this->returnValue(true));

        $this->php
            ->expects($this->any())
            ->method('is_dir')
            ->will($this->returnValue(true));

        $this->php
            ->expects($this->once())
            ->method('copy')
            ->with($this->matchesRegularExpression('/dist/'))
            ->will($this->returnValue(false));

        $ext->getConfig();
    }

    public function testAddTwigFunction()
    {
        $this->markTestIncomplete('Update required');

        $app = $this->makeApp();
        $ext = $this->getMockForAbstractClass('Bolt\BaseExtension', [$app]);
        $ext->addTwigFunction('test', [$this, 'testAddTwigFunction']);
        $loadedExt = $ext->getTwigExtensions();
        $builtin = $loadedExt[0];
        $this->assertEquals(1, count($builtin->getFunctions()));
    }

    public function testAddTwigFilter()
    {
        $this->markTestIncomplete('Update required');

        $app = $this->makeApp();
        $ext = $this->getMockForAbstractClass('Bolt\BaseExtension', [$app]);
        $ext->addTwigFilter('test', [$this, 'testAddTwigFilter']);
        $loadedExt = $ext->getTwigExtensions();
        $builtin = $loadedExt[0];
        $this->assertEquals(1, count($builtin->getFilters()));
    }

    public function testAddSnippet()
    {
        $this->markTestIncomplete('Update required');

        $app = $this->getApp();
        $ext = $this->getMockForAbstractClass('Bolt\BaseExtension', [$app]);
        $handler = $this->getMock('Bolt\Asset\Snippet\Queue', ['add'], [
            $app['asset.injector'],
            $app['cache'],
            $app['config'],
            $app['resources'],
            $app['request_stack'],
        ]);

        $handler->expects($this->once())
            ->method('add');

        $app['asset.queue.snippet'] = $handler;

        $ext->addSnippet('test', [$this, 'testAddSnippet']);
    }

    public function testAddJquery()
    {
        $this->markTestIncomplete('Update required');

        $app = $this->makeApp();
        $ext = $this->getMockForAbstractClass('Bolt\BaseExtension', [$app]);
        $handler = $this->getMock('Bolt\Extensions', ['addJquery'], [$app]);

        $handler->expects($this->once())
            ->method('addJquery');

        $app['extensions'] = $handler;

        $ext->addJquery();
    }

    public function testDisableJquery()
    {
        $this->markTestIncomplete('Update required');

        $app = $this->makeApp();
        $ext = $this->getMockForAbstractClass('Bolt\BaseExtension', [$app]);
        $handler = $this->getMock('Bolt\Extensions', ['disableJquery'], [$app]);

        $handler->expects($this->once())
            ->method('disableJquery');

        $app['extensions'] = $handler;

        $ext->disableJquery();
    }

    public function testGetAssets()
    {
        $this->markTestIncomplete('Update required');

        $app = $this->makeApp();
        $ext = $this->getMockForAbstractClass('Bolt\BaseExtension', [$app]);
        $handler = $this->getMock('Bolt\Extensions', ['getAssets'], [$app]);

        $handler->expects($this->once())
            ->method('getAssets');

        $app['extensions'] = $handler;

        $ext->getAssets();
    }

    public function testAddJavascriptFailure()
    {
        $this->markTestIncomplete('Update required');

        $app = $this->makeApp();
        $app->initialize();
        $ext = $this->getMockForAbstractClass('Bolt\BaseExtension', [$app]);
        $handler = $this->getMock('Bolt\Extensions', ['addJavascript'], [$app]);

        $changeRepository = $app['storage']->getRepository('Bolt\Storage\Entity\LogChange');
        $systemRepository = $app['storage']->getRepository('Bolt\Storage\Entity\LogSystem');
        $logger = $this->getMock('Bolt\Logger\Manager', ['error'], [$app, $changeRepository, $systemRepository]);

        $logger->expects($this->once())
            ->method('error');

        $app['extensions'] = $handler;
        $app['logger.system'] = $logger;

        $ext->addJavascript('path1');
    }

    public function testAddJavascriptBase()
    {
        $this->markTestIncomplete('Update required');

        $app = $this->makeApp();
        $app->initialize();
        $ext = new ExtensionAssetMocker($app);

        $ext->addJavascript('test.js');
        $assets = $app['asset.queue.file']->getQueue();
        $this->assertEquals(1, count($assets['javascript']));
    }

    public function testAddJavascriptTheme()
    {
        $this->markTestIncomplete('Update required');

        $app = $this->makeApp();
        $app->initialize();
        $ext = new ExtensionAssetMocker($app);
        $fs = new Filesystem();
        $fs->copy(PHPUNIT_ROOT . '/resources/test.js', PHPUNIT_WEBROOT . '/theme/default/moved-test.js');

        $ext->addJavascript('moved-test.js');
        $assets = $app['asset.queue.file']->getQueue();
        $this->assertEquals(1, count($assets['javascript']));
    }

    public function testAddCssFailure()
    {
        $this->markTestIncomplete('Update required');

        $app = $this->makeApp();
        $app->initialize();
        $ext = $this->getMockForAbstractClass('Bolt\BaseExtension', [$app]);

        $changeRepository = $app['storage']->getRepository('Bolt\Storage\Entity\LogChange');
        $systemRepository = $app['storage']->getRepository('Bolt\Storage\Entity\LogSystem');
        $logger = $this->getMock('Bolt\Logger\Manager', ['error'], [$app, $changeRepository, $systemRepository]);

        $logger->expects($this->once())
            ->method('error');

        $app['logger.system'] = $logger;

        $ext->addCss('path1');
    }

    public function testAddCssBase()
    {
        $this->markTestIncomplete('Update required');

        $app = $this->makeApp();
        $app->initialize();
        $ext = new ExtensionAssetMocker($app);

        $ext->addCss('test.css');
        $assets = $app['asset.queue.file']->getQueue();
        $this->assertEquals(1, count($assets['stylesheet']));
    }

    public function testAddCssTheme()
    {
        $this->markTestIncomplete('Update required');

        $app = $this->makeApp();
        $app->initialize();
        $ext = new ExtensionAssetMocker($app);
        $fs = new Filesystem();
        $fs->copy(PHPUNIT_ROOT . '/resources/test.css', PHPUNIT_WEBROOT . '/theme/default/moved-test.css');

        $ext->addCss('moved-test.css');
        $assets = $app['asset.queue.file']->getQueue();
        $this->assertEquals(1, count($assets['stylesheet']));
    }

    public function testAddMenuOption()
    {
        $this->markTestIncomplete('Update required');

        $app = $this->makeApp();
        $ext = $this->getMockForAbstractClass('Bolt\BaseExtension', [$app]);
        $handler = $this->getMockForAbstractClass('Bolt\BaseExtension', [$app], '', true, true, true, ['addMenuOption']);

        $handler->expects($this->once())
            ->method('addMenuOption');

        $app['extensions'] = $handler;

        $ext->addMenuOption('test', '/test');
    }

    public function testParseSnippet()
    {
        $this->markTestIncomplete('Update required');

        $app = $this->makeApp();
        $ext = $this->getMock('Bolt\Tests\Extensions\Mock\ExtendedExtension', ['acallback'], [$app]);

        $ext->expects($this->once())
            ->method('acallback');

        $ext->parseSnippet('acallback');
        $this->assertFalse($ext->parseSnippet('nonexistingcallback'));
    }

    public function testAddWidget()
    {
        $this->markTestIncomplete('Update required');

        $app = $this->getApp();
        $ext = $this->getMockForAbstractClass('Bolt\BaseExtension', [$app]);
        $handler = $this->getMock('Bolt\Asset\Widget\Queue', ['add'], [
            $app['asset.injector'],
            $app['cache'],
            $app['render'],
        ]);
        $handler->expects($this->once())
            ->method('add')
            ->with($this->isInstanceOf('\Bolt\Asset\Widget\Widget'))
        ;
        $widget = new \Bolt\Asset\Widget\Widget();
        $widget
            ->setZone('frontend')
            ->setLocation('aside_top')
        ;

        $app['asset.queue.widget'] = $handler;

        $ext->addWidget($widget);
    }

    public function testAddNutCommand()
    {
        $this->markTestIncomplete('Update required');

        $app = $this->makeApp();
        $app->register(new NutServiceProvider());

        $ext = $this->getMockForAbstractClass('Bolt\BaseExtension', [$app]);
        $ext->addConsoleCommand(new Command('test_command'));

        $this->assertTrue($app['nut']->has('test_command'));
    }
}

class ExtensionAssetMocker extends BaseExtension
{
    protected $basepath;

    public function __construct(\Bolt\Application $app)
    {
        $this->app = $app;
        $this->basepath = PHPUNIT_ROOT . '/resources';
    }

    public function initialize()
    {
    }
}
