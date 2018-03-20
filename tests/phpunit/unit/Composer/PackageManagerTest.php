<?php

namespace Bolt\Tests\Composer;

use Bolt\Common\Json;
use Bolt\Composer\EventListener\PackageDescriptor;
use Bolt\Composer\JsonManager;
use Bolt\Composer\PackageManager;
use Bolt\Extension\Manager;
use Bolt\Extension\ResolvedExtension;
use Bolt\Filesystem\Handler\File;
use Bolt\Filesystem\Manager as FilesystemManager;
use Bolt\Logger\FlashLogger;
use Composer\Package\CompletePackage;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7;
use PHPUnit\Framework\TestCase;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGenerator;

/**
 * @covers \Bolt\Composer\PackageManager
 *
 * @author Ross Riley <riley.ross@gmail.com>
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class PackageManagerTest extends TestCase
{
    public function testSetup()
    {
        $app = new Application();
        $jsonManager = $this->getMockBuilder(JsonManager::class)
            ->disableOriginalConstructor()
            ->setMethods(['update'])
            ->getMock()
        ;
        $jsonManager
            ->expects($this->once())
            ->method('update')
        ;
        $app['extend.manager.json'] = $jsonManager;
        $app['extend.writeable'] = true;
        $app['extend.site'] = 'https://example.com';
        $app['request_stack']->push(Request::createFromGlobals());

        $mock = new MockHandler([
            new Psr7\Response(200),
        ]);

        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler]);
        $app['guzzle.client'] = $client;

        new PackageManager($app);
    }

    public function testSetupJsonFail()
    {
        $app = new Application();
        $jsonManager = $this->getMockBuilder(JsonManager::class)
            ->disableOriginalConstructor()
            ->setMethods(['update'])
            ->getMock()
        ;
        $jsonManager
            ->expects($this->once())
            ->method('update')
            ->willThrowException(new \Bolt\Filesystem\Exception\ParseException('bad'));
        $app['extend.manager.json'] = $jsonManager;
        $app['extend.writeable'] = true;
        $flashLogger = $this->getMockBuilder(FlashLogger::class)
            ->disableOriginalConstructor()
            ->setMethods(['danger'])
            ->getMock()
        ;
        $flashLogger
            ->expects($this->once())
            ->method('danger')
        ;
        $app['logger.flash'] = $flashLogger;

        new PackageManager($app);
    }

    public function testGetAllPackages()
    {
        $installed = [
            ['package' => new CompletePackage('test/installed-a', '1.2.3.0', '1.2.3'), 'versions' => '1.2.3.0'],
            ['package' => new CompletePackage('test/installed-b', '2.4.6.0', '2.4.6'), 'versions' => '2.4.6.0'],
        ];
        $requires = [
            'test/required-a' => '^3.0',
            'test/required-b' => '^4.0',
        ];
        $app = new Application();
        $app['extend.writeable'] = false;
        $app['extend.action'] = $this->getActionMock('show', $installed);

        $urlGeneratorMock = $this->getMockBuilder(UrlGenerator::class)
            ->disableOriginalConstructor()
            ->setMethods(['generate'])
            ->getMock()
        ;
        $urlGeneratorMock
            ->expects($this->at(0))
            ->method('generate')
            ->willReturn('/async/readme/test/installed-a')
        ;
        $urlGeneratorMock
            ->expects($this->at(1))
            ->method('generate')
            ->willReturn('/bolt/file/edit/extensions/installed-a.test.yml')
        ;
        $app['url_generator'] = $urlGeneratorMock;

        $descriptor = new PackageDescriptor(null, null, null, null, 'x.y.z', true);
        $extensionMock = $this->getMockBuilder(ResolvedExtension::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId', 'getName', 'getVendor', 'getDisplayName', 'getDescriptor', 'isValid', 'isEnabled'])
            ->getMock()
        ;
        $extensionMock
            ->expects($this->once())
            ->method('getDescriptor')
            ->willReturn($descriptor)
        ;
        $extensionMock
            ->expects($this->once())
            ->method('getId')
            ->willReturn('test/installed-a')
        ;
        $extensionMock
            ->expects($this->once())
            ->method('getName')
            ->willReturn('installed-a')
        ;
        $extensionMock
            ->expects($this->once())
            ->method('getVendor')
            ->willReturn('test')
        ;
        $extensionMock
            ->expects($this->once())
            ->method('isValid')
            ->willReturn(true)
        ;
        $extensionMock
            ->expects($this->once())
            ->method('isEnabled')
            ->willReturn(false)
        ;

        $fileMock = $this->getMockBuilder(File::class)
            ->disableOriginalConstructor()
            ->setMethods(['exists'])
            ->getMock()
        ;
        $fileMock
            ->expects($this->atLeastOnce())
            ->method('exists')
            ->willReturn(true)
        ;
        $fsMock = $this->getMockBuilder(FilesystemManager::class)
            ->disableOriginalConstructor()
            ->setMethods(['getFile'])
            ->getMock()
        ;
        $fsMock
            ->expects($this->atLeastOnce())
            ->method('getFile')
            ->with('extensions_config://installed-a.test.yml')
            ->willReturn($fileMock)
        ;
        $app['filesystem'] = $fsMock;

        $extensions = $this->getMockBuilder(Manager::class)
            ->disableOriginalConstructor()
            ->setMethods(['getResolved'])
            ->getMock()
        ;
        $extensions
            ->expects($this->at(0))
            ->method('getResolved')
            ->willReturn($extensionMock)
        ;
        $extensions
            ->expects($this->at(1))
            ->method('getResolved')
            ->willReturn(false)
        ;
        $app['extensions'] = $extensions;

        $packageManager = new PackageManager($app);

        $reflection = new \ReflectionClass(PackageManager::class);
        $method = $reflection->getProperty('json');
        $method->setAccessible(true);
        $method->setValue($packageManager, ['require' => $requires]);

        $packages = $packageManager->getAllPackages();
        $package = $packages->get('test/installed-b');
        $package->setConstraint('a.b.c');

        $expected = '{"test/installed-a":{"status":"installed","type":"library","name":"test/installed-a","title":null,"description":null,"version":"1.2.3","authors":null,"keywords":null,"readmeLink":"/async/readme/test/installed-a","configLink":"/bolt/file/edit/extensions/installed-a.test.yml","repositoryLink":null,"constraint":"x.y.z","valid":true,"enabled":false},"test/installed-b":{"status":"installed","type":"library","name":"test/installed-b","title":"test/installed-b","description":null,"version":"2.4.6","authors":null,"keywords":null,"readmeLink":null,"configLink":null,"repositoryLink":null,"constraint":"a.b.c","valid":true,"enabled":true},"test/required-a":{"status":"pending","type":"unknown","name":"test/required-a","title":"test/required-a","description":"Not yet installed.","version":"^3.0","authors":[],"keywords":[],"readmeLink":null,"configLink":null,"repositoryLink":null,"constraint":null,"valid":false,"enabled":false},"test/required-b":{"status":"pending","type":"unknown","name":"test/required-b","title":"test/required-b","description":"Not yet installed.","version":"^4.0","authors":[],"keywords":[],"readmeLink":null,"configLink":null,"repositoryLink":null,"constraint":null,"valid":false,"enabled":false}}';

        $this->assertSame($expected, Json::dump($packages));
    }

    public function providerActions()
    {
        return [
            ['checkPackage', 'check', []],
            ['dependsPackage', 'depends', [null, null]],
            ['dumpAutoload', 'autoload', []],
            ['installPackages', 'install', []],
            ['prohibitsPackage', 'prohibits', [null, null]],
            ['removePackage', 'remove', [[]]],
            ['requirePackage', 'require', [[]]],
            ['searchPackage', 'search', [[]]],
            ['showPackage', 'show', [null]],
            ['updatePackage', 'update', [[]]],
        ];
    }

    /**
     * @dataProvider providerActions
     *
     * @param string $method
     * @param string $action
     * @param array  $args
     */
    public function testActionCalls($method, $action, array $args)
    {
        $app = new Application();
        $app['extend.writeable'] = false;
        $app['extend.action'] = $this->getActionMock($action, true);

        $packageManager = new PackageManager($app);
        $mockResult = call_user_func_array([$packageManager, $method], $args);

        $this->assertTrue($mockResult);
    }

    public function testGetOutput()
    {
        $app = new Application();
        $app['extend.writeable'] = false;
        $mock = $this->getMockBuilder(\stdClass::class)
            ->disableOriginalConstructor()
            ->setMethods(['getOutput'])
            ->getMock()
        ;
        $mock
            ->expects($this->once())
            ->method('getOutput')
            ->willReturn('gum leaves')
        ;
        $app['extend.action.io'] = $mock;

        $packageManager = new PackageManager($app);
        $output = $packageManager->getOutput();

        $this->assertSame('gum leaves', $output);
    }

    public function testInitJson()
    {
        $app = new Application();
        $app['extend.writeable'] = false;
        $mock = $this->getMockBuilder(\stdClass::class)
            ->disableOriginalConstructor()
            ->setMethods(['init'])
            ->getMock()
        ;
        $mock
            ->expects($this->once())
            ->method('init')
            ->willReturn('gum leaves')
        ;
        $app['extend.manager.json'] = $mock;

        $packageManager = new PackageManager($app);
        $packageManager->initJson('composer.json', []);
    }

    /**
     * @param string $action
     * @param mixed  $returnValue
     *
     * @return array
     */
    protected function getActionMock($action, $returnValue)
    {
        $mock = $this->getMockBuilder(\stdClass::class)
            ->disableOriginalConstructor()
            ->setMethods(['execute'])
            ->getMock()
        ;
        $mock
            ->expects($this->once())
            ->method('execute')
            ->willReturn($returnValue)
        ;

        return [$action => $mock];
    }
}
