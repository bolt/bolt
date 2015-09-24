<?php
namespace Bolt\Tests\Extensions;

use Bolt\Provider\NutServiceProvider;
use Symfony\Component\Console\Command\Command;

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
        $app = $this->getApp();
        $ext = $this->getMockForAbstractClass('Bolt\BaseExtension', [$app]);
        $this->assertNotEmpty($ext->getBasePath());
        $this->assertNotEmpty($ext->getBaseUrl());
        $this->assertEquals('mockobject', $ext->getMachineName());
    }

    public function testComposerLoading()
    {
        $this->localExtensionInstall();
        $app = $this->getApp();
        $this->assertTrue($app['extensions']->isEnabled('testlocal'));
        $config = $app['extensions.testlocal']->getExtensionConfig();
        $this->assertNotEmpty($config);
    }

    public function testGetBasePath()
    {
        $app = $this->makeApp();
        $ext = $this->getMockForAbstractClass('Bolt\BaseExtension', [$app]);
        $this->assertNotEmpty(strpos($ext->getBasePath()->string(), 'MockObject'));
    }

    public function testGetBaseUrl()
    {
        $app = $this->makeApp();
        $ext = $this->getMockForAbstractClass('Bolt\BaseExtension', [$app]);
        $this->assertEquals(0, strpos($ext->getBaseUrl(), '/extensions'));
    }

    public function testGetComposerNameDefault()
    {
        $app = $this->makeApp();
        $ext = $this->getMockForAbstractClass('Bolt\BaseExtension', [$app]);
        $this->assertNull($ext->getComposerName());
    }

    public function testGetComposerName()
    {
        $app = $this->makeApp();
        $ext = $this->getMockForAbstractClass('Bolt\BaseExtension', [$app]);
        $this->assertNull($ext->getComposerName());
        $ext->setComposerConfiguration(['name' => 'valuefrommock']);

        $this->assertEquals('valuefrommock', $ext->getComposerName());
    }

    public function testGetMachineName()
    {
        $app = $this->makeApp();
        $ext = $this->getMockForAbstractClass('Bolt\BaseExtension', [$app]);

        // Machine name calculated from the Class name in this case MockObject
        $this->assertEquals('mockobject', $ext->getMachineName());
        $ext->setComposerConfiguration(['name' => 'valuefrommock']);
        $this->assertEquals('valuefrommock', $ext->getMachineName());
    }

    public function testSetComposerConfiguration()
    {
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
        $app = $this->makeApp();
        $ext = $this->getMockForAbstractClass('Bolt\BaseExtension', [$app]);
        $ext->addTwigFunction('test', [$this, 'testAddTwigFunction']);
        $loadedExt = $ext->getTwigExtensions();
        $builtin = $loadedExt[0];
        $this->assertEquals(1, count($builtin->getFunctions()));
    }

    public function testAddTwigFilter()
    {
        $app = $this->makeApp();
        $ext = $this->getMockForAbstractClass('Bolt\BaseExtension', [$app]);
        $ext->addTwigFilter('test', [$this, 'testAddTwigFilter']);
        $loadedExt = $ext->getTwigExtensions();
        $builtin = $loadedExt[0];
        $this->assertEquals(1, count($builtin->getFilters()));
    }

    public function testAddSnippet()
    {
        $app = $this->makeApp();
        $ext = $this->getMockForAbstractClass('Bolt\BaseExtension', [$app]);
        $handler = $this->getMock('Bolt\Asset\Snippet\Queue', ['add'], [$app]);

        $handler->expects($this->once())
            ->method('add');

        $app['asset.queue.snippet'] = $handler;

        $ext->addSnippet('test', [$this, 'testAddSnippet']);
    }

    public function testAddJquery()
    {
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
        $app = $this->makeApp();
        $app->initialize();
        $ext = $this->getMockForAbstractClass('Bolt\BaseExtension', [$app]);
        $handler = $this->getMock('Bolt\Asset\File\Queue', ['add'], [$app]);

        $handler->expects($this->once())
            ->method('add')
            ->with($this->matchesRegularExpression('/javascript/'), $this->matchesRegularExpression('/path1/'));

        $app['asset.queue.file'] = $handler;

        $this->php
            ->expects($this->at(0))
            ->method('file_exists')
            ->will($this->returnValue(true));

        $ext->addJavascript('path1');
    }

    public function testAddJavascriptTheme()
    {
        $app = $this->makeApp();
        $app->initialize();
        $ext = $this->getMockForAbstractClass('Bolt\BaseExtension', [$app]);
        $handler = $this->getMock('Bolt\Asset\File\Queue', ['add'], [$app]);

        $handler->expects($this->once())
            ->method('add')
            ->with($this->matchesRegularExpression('/javascript/'), $this->matchesRegularExpression('/\/theme.*path2/'));

        $this->php
            ->expects($this->at(0))
            ->method('file_exists')
            ->will($this->returnValue(false));

        $this->php
            ->expects($this->at(1))
            ->method('file_exists')
            ->will($this->returnValue(true));

        $app['asset.queue.file'] = $handler;

        $ext->addJavascript('path2');
    }

    public function testAddCssFailure()
    {
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
        $app = $this->makeApp();
        $app->initialize();
        $ext = $this->getMockForAbstractClass('Bolt\BaseExtension', [$app]);
        $handler = $this->getMock('Bolt\Asset\File\Queue', ['add'], [$app]);

        $handler->expects($this->once())
            ->method('add')
            ->with($this->matchesRegularExpression('/stylesheet/'), $this->matchesRegularExpression('/path1/'));

        $app['asset.queue.file'] = $handler;

        $this->php
            ->expects($this->at(0))
            ->method('file_exists')
            ->will($this->returnValue(true));

        $ext->addCss('path1');
    }

    public function testAddCssTheme()
    {
        $app = $this->makeApp();
        $app->initialize();
        $ext = $this->getMockForAbstractClass('Bolt\BaseExtension', [$app]);
        $handler = $this->getMock('Bolt\Asset\File\Queue', ['add'], [$app]);

        $handler->expects($this->once())
            ->method('add')
            ->with($this->matchesRegularExpression('/stylesheet/'), $this->matchesRegularExpression('/\/theme.*path2/'));

        $this->php
            ->expects($this->at(0))
            ->method('file_exists')
            ->will($this->returnValue(false));

        $this->php
            ->expects($this->at(1))
            ->method('file_exists')
            ->will($this->returnValue(true));

        $app['asset.queue.file'] = $handler;

        $ext->addCss('path2');
    }

    public function testAddMenuOption()
    {
        $app = $this->makeApp();
        $ext = $this->getMockForAbstractClass('Bolt\BaseExtension', [$app]);
        $handler = $this->getMockForAbstractClass('Bolt\BaseExtension', [$app], '', true, true, true, ['addMenuOption']);

        $handler->expects($this->once())
            ->method('addMenuOption');

        $app['extensions'] = $handler;

        $ext->addMenuOption('test', '/test');
    }

    public function testHasMenuOption()
    {
        $app = $this->makeApp();
        $ext = $this->getMockForAbstractClass('Bolt\BaseExtension', [$app]);
        $handler = $this->getMock('Bolt\Extensions', ['hasMenuOption'], [$app]);

        $handler->expects($this->once())
            ->method('hasMenuOption');

        $app['extensions'] = $handler;

        $ext->hasMenuOptions();
    }

    public function testGetMenuOption()
    {
        $app = $this->makeApp();
        $ext = $this->getMockForAbstractClass('Bolt\BaseExtension', [$app]);
        $handler = $this->getMock('Bolt\Extensions', ['getMenuOption'], [$app]);

        $handler->expects($this->once())
            ->method('getMenuOption');

        $app['extensions'] = $handler;

        $ext->getMenuOptions();
    }

    public function testParseSnippet()
    {
        $app = $this->makeApp();
        $ext = $this->getMock('Bolt\Tests\Extensions\Mock\ExtendedExtension', ['acallback'], [$app]);

        $ext->expects($this->once())
            ->method('acallback');

        $ext->parseSnippet('acallback');
        $this->assertFalse($ext->parseSnippet('nonexistingcallback'));
    }

    public function testAddWidget()
    {
        $app = $this->makeApp();
        $ext = $this->getMockForAbstractClass('Bolt\BaseExtension', [$app]);
        $handler = $this->getMock('Bolt\Extensions', ['insertWidget'], [$app]);
        $handler->expects($this->once())
            ->method('insertWidget')
            ->with($this->equalTo('testWidget'));

        $app['extensions'] = $handler;

        $ext->addWidget('testWidget', 'widgetLocation', [$this, 'testAddWidget']);
    }

    public function testRequireUserLevel()
    {
        $app = $this->getApp();
        $ext = $this->getMockForAbstractClass('Bolt\BaseExtension', [$app], '', true, true, true, ['requireUserPermission']);

        $ext->expects($this->once())
            ->method('requireUserPermission');

        $ext->requireUserLevel('test');
    }

    public function testRequireUserPermission()
    {
        $app = $this->getApp();
        $ext = $this->getMockForAbstractClass('Bolt\BaseExtension', [$app]);
        $permissions = $this->getMock('Bolt\AccessControl\Permissions', ['isAllowed'], [$this->getApp()]);
        $permissions->expects($this->once())
            ->method('isAllowed')
            ->will($this->returnValue(true));
        $app['permissions'] = $permissions;

        $ext->requireUserPermission('test');
    }

    public function testRequireUserPermissionRedirect()
    {
        $app = $this->getApp();
        $ext = $this->getMockForAbstractClass('Bolt\BaseExtension', [$app]);
        $permissions = $this->getMock('Bolt\AccessControl\Permissions', ['isAllowed'], [$this->getApp()]);
        $permissions->expects($this->once())
            ->method('isAllowed')
            ->will($this->returnValue(false));
        $app['permissions'] = $permissions;

        $this->expectOutputRegex("/Redirecting to/i");
        $response = $ext->requireUserPermission('test');
        $this->assertFalse($response);
    }

    public function testParseWidget()
    {
        $app = $this->makeApp();
        $ext = $this->getMock('Bolt\Tests\Extensions\Mock\ExtendedExtension', ['acallback'], [$app]);

        $ext->expects($this->once())
            ->method('acallback');

        $ext->parseWidget('acallback');
        $this->assertFalse($ext->parseWidget('nonexistingcallback'));
    }

    public function testParseWidgetFails()
    {
        $app = $this->makeApp();
        $ext = $this->getMockForAbstractClass('Bolt\BaseExtension', [$app]);

        $this->assertFalse($ext->parseWidget('fakecallback'));
    }

    public function testAddNutCommand()
    {
        $app = $this->makeApp();
        $app->register(new NutServiceProvider());

        $ext = $this->getMockForAbstractClass('Bolt\BaseExtension', [$app]);
        $ext->addConsoleCommand(new Command('test_command'));

        $this->assertTrue($app['nut']->has('test_command'));
    }
}
