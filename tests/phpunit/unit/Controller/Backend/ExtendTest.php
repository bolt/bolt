<?php

namespace Bolt\Tests\Controller\Backend;

use Bolt\Common\Json;
use Bolt\Composer\PackageManager;
use Bolt\Composer\Satis\QueryService;
use Bolt\Controller\Backend\Extend;
use Bolt\Tests\Controller\ControllerUnitTest;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class to test correct operation of src/Controller/Extend.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 **/
class ExtendTest extends ControllerUnitTest
{
    protected function setUp()
    {
    }

    public function testDefaultRegistries()
    {
        $this->assertNotEmpty($this->getService('extend.site'));
        $this->assertNotEmpty($this->getService('extend.repo'));

        $this->assertInstanceOf(PackageManager::class, $this->getService('extend.manager'));
    }

    public function testMethodsReturnTemplates()
    {
        $this->getApp()->flush();
        $this->getService('twig.loader.filesystem')->prependPath(TEST_ROOT . '/app/view/twig');

        $this->setRequest(Request::create('/bolt/extend'));
        $response = $this->controller()->overview();
        $this->assertEquals('@bolt/extend/extend.twig', $response->getTemplate());

        $response = $this->controller()->installPackage();
        $this->assertEquals('@bolt/extend/_action-modal.twig', $response->getTemplate());

        $this->setRequest(Request::create('/', 'GET', ['package' => 'bolt/theme-2014']));
        $controller = $this->getMockBuilder(Extend::class)
            ->setMethods(['installInfo', 'packageInfo', 'check'])
            ->getMock()
        ;
        $controller->expects($this->any())
            ->method('installInfo')
            ->will($this->returnValue(new Response('{"dev": [{"name": "bolt/theme-2014","version": "dev-master"}],"stable": []}')));

        $response = $controller->installInfo($this->getRequest());
        $this->assertNotEmpty($response);

        $this->setRequest(Request::create('/', 'GET', ['package' => 'bolt/theme-2014', 'version' => 'dev-master']));
        $controller->expects($this->any())
            ->method('packageInfo')
            ->will($this->returnValue(new Response('{"name":"bolt\/theme-2014","version":"unknown","type":"unknown","descrip":""}')));

        $response = $controller->packageInfo($this->getRequest());
        $this->assertNotEmpty($response);
        $content = Json::parse($response->getContent());
        $this->assertArrayHasKey('name', $content);
        $this->assertNotEmpty($content['name']);

        $this->setRequest(Request::create('/'));
        $controller->expects($this->any())
            ->method('check')
            ->will($this->returnValue(new Response('{"updates":[],"installs":[]}')));

        $response = $controller->check();
        $this->assertNotEmpty($response);
    }

    public function testOverview()
    {
        $this->getApp()->flush();
        $this->allowLogin($this->getApp());
        $this->setRequest(Request::create('/bolt/extend'));

        $response = $this->controller()->overview();

        $this->assertEquals('@bolt/extend/extend.twig', $response->getTemplate());
    }

    public function testInstallPackage()
    {
        $this->getApp()->flush();
        $this->allowLogin($this->getApp());
        $this->setRequest(Request::create('/bolt/extend/installPackage'));

        $response = $this->controller()->installPackage();

        $this->assertEquals('@bolt/extend/_action-modal.twig', $response->getTemplate());
    }

    public function testInstallInfo()
    {
        $this->getApp()->flush();
        $mockInfo = $this->getMockBuilder(QueryService::class)
            ->setMethods(['info'])
            ->setConstructorArgs([$this->getApp()])
            ->setMockClassName('MockInfoService')
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $mockInfo->expects($this->once())
            ->method('info')
            ->will($this->returnValue($this->packageInfoProvider()));
        $this->setService('extend.info', $mockInfo);
        $this->allowLogin($this->getApp());

        $this->setRequest(Request::create('/bolt/extend/installInfo?package=test&bolt=2.0.0'));
        $response = $this->controller()->installInfo($this->getRequest());

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $parsedOutput = Json::parse($response->getContent());
        $this->assertNotEmpty($parsedOutput['dev']);
        $this->assertNotEmpty($parsedOutput['stable']);
    }

    public function packageInfoProvider()
    {
        $info = [
            'package' => [
                'id'           => '99999',
                'title'        => 'Test',
                'source'       => 'https://github.com/',
                'name'         => 'test',
                'keywords'     => [],
                'type'         => 'bolt-extension',
                'description'  => 'Test',
                'approved'     => true,
                'requirements' => [
                    'bolt/bolt' => '>=2.0.0,<3.0.0',
                ],
                'versions' => [
                    0 => '1.0.0',
                    1 => 'dev-master',
                ],
            ],
            'version' => [
                [
                    'name'               => 'test',
                    'version'            => '1.0.0',
                    'version_normalized' => '1.0.0.0',
                    'source'             => [
                        'type'      => 'git',
                        'url'       => 'https://github.com/',
                        'reference' => 'xxx',
                    ],
                    'require' => [
                        'bolt/bolt' => '>=2.0.0,<3.0.0',
                    ],
                    'type'        => 'bolt-extension',
                    'stability'   => 'stable',
                    'buildStatus' => 'untested',
                ],
                [
                    'name'               => 'test',
                    'version'            => 'dev-master',
                    'version_normalized' => '9999999-dev',
                    'source'             => [
                        'type'      => 'git',
                        'url'       => 'https://github.com/',
                        'reference' => 'XXX',
                    ],
                    'require'     => ['bolt/bolt' => '>=2.0.0,<3.0.0'],
                    'type'        => 'bolt-extension',
                    'stability'   => 'dev',
                    'buildStatus' => 'untested',
                ],
            ],
        ];
        // This just ensures that the data matches the internal format of json decoded responses
        return json_decode(json_encode($info));
    }

    /**
     * @return \Bolt\Controller\Backend\Extend
     */
    protected function controller()
    {
        return $this->getService('controller.backend.extend');
    }
}
