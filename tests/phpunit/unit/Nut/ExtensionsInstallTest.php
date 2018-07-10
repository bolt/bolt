<?php

namespace Bolt\Tests\Nut;

use Bolt\Composer\PackageManager;
use Bolt\Composer\Satis\PingService;
use Bolt\Nut\ExtensionsInstall;
use Bolt\Tests\BoltUnitTest;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class to test src/Nut/ExtensionsEnable.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class ExtensionsInstallTest extends BoltUnitTest
{
    public function testRun()
    {
        $app = $this->getApp();

        $runner = $this->getMockBuilder(PackageManager::class)
            ->setMethods(['requirePackage'])
            ->setConstructorArgs([$app])
            ->getMock()
        ;
        $runner->expects($this->any())
            ->method('requirePackage')
            ->will($this->returnValue(0));

        $this->setService('extend.manager', $runner);
        $this->setService('extend.ping', $this->getPingServiceMock(new Response(200, ['X-Foo' => 'Bar'])));

        $command = new ExtensionsInstall($app);
        $tester = new CommandTester($command);

        $tester->execute(['name' => 'test', 'version' => '1.0']);
        $result = $tester->getDisplay();
        $this->assertRegExp('/Installing test:1.0/', $result);
    }

    public function testFailed()
    {
        $app = $this->getApp();

        $runner = $this->getMockBuilder(PackageManager::class)
            ->setMethods(['requirePackage'])
            ->setConstructorArgs([$app])
            ->getMock()
        ;
        $runner->expects($this->any())
            ->method('requirePackage')
            ->will($this->returnValue(1));

        $this->setService('extend.manager', $runner);
        $this->setService('extend.ping', $this->getPingServiceMock(new RequestException('Mock testing failure', new Request('GET', 'http://localhost/ping'))));

        $command = new ExtensionsInstall($app);
        $tester = new CommandTester($command);

        $tester->execute(['name' => 'test', 'version' => '1.0']);
        $result = $tester->getDisplay();

        $this->assertRegExp('/Testing connection to extension server failed: Mock testing failure/', $result);
    }

    private function getPingServiceMock($response)
    {
        $mock = new MockHandler([$response]);

        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler]);

        $requestStack = $this->createMock(RequestStack::class);
        $requestStack
            ->expects($this->atLeastOnce())
            ->method('getCurrentRequest')
        ;

        return new PingService($client, $requestStack, 'http://localhost/ping');
    }
}
