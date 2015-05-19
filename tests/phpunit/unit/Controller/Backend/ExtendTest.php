<?php
namespace Bolt\Tests\Controller\Backend;

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

        $this->assertInstanceOf('Bolt\Composer\PackageManager', $this->getService('extend.manager'));
    }

    public function testMethodsReturnTemplates()
    {
        $this->getService('twig.loader.filesystem')->prependPath(TEST_ROOT . '/app/view/twig');

        $this->setRequest(Request::create('/bolt/extend'));
        $response = $this->controller()->overview($this->getRequest());
        $this->assertEquals('extend/extend.twig', $response->getTemplateName());

        $response = $this->controller()->installPackage($this->getRequest());
        $this->assertEquals('extend/install-package.twig', $response->getTemplateName());

        $this->setRequest(Request::create('/', 'GET', array('package' => 'bolt/theme-2014')));
        $controller = $this->getMock('Bolt\Controller\Backend\Extend', array('installInfo', 'packageInfo', 'check'));
        $controller->expects($this->any())
            ->method('installInfo')
            ->will($this->returnValue(new Response('{"dev": [{"name": "bolt/theme-2014","version": "dev-master"}],"stable": []}')));

        $response = $controller->installInfo($this->getRequest());
        $this->assertNotEmpty($response);

        $this->setRequest(Request::create('/', 'GET', array('package' => 'bolt/theme-2014', 'version' => 'dev-master')));
        $controller->expects($this->any())
            ->method('packageInfo')
            ->will($this->returnValue(new Response('{"name":"bolt\/theme-2014","version":"unknown","type":"unknown","descrip":""}')));

        $response = $controller->packageInfo($this->getRequest());
        $this->assertNotEmpty($response);
        $content = json_decode($response->getContent());
        $this->assertAttributeNotEmpty('name', $content);

        $this->setRequest(Request::create('/'));
        $controller->expects($this->any())
            ->method('check')
            ->will($this->returnValue(new Response('{"updates":[],"installs":[]}')));

        $response = $controller->check($this->getRequest());
        $this->assertNotEmpty($response);
    }

    public function testOverview()
    {
        $this->allowLogin($this->getApp());
        $this->setRequest(Request::create('/bolt/extend'));
        $this->checkTwigForTemplate($this->getApp(), 'extend/extend.twig');

        $response = $this->controller()->overview($this->getRequest());

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testInstallPackage()
    {
        $this->allowLogin($this->getApp());
        $this->setRequest(Request::create('/bolt/extend/installPackage'));
        $this->checkTwigForTemplate($this->getApp(), 'extend/install-package.twig');

        $response = $response = $this->controller()->installPackage($this->getRequest());

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testInstallInfo()
    {
        $mockInfo = $this->getMock('Bolt\Extensions\ExtensionsInfoService', array('info'), array(), 'MockInfoService', false);
        $mockInfo->expects($this->once())
            ->method('info')
            ->will($this->returnValue($this->packageInfoProvider()));
        $this->setService('extend.info', $mockInfo);
        $this->allowLogin($this->getApp());

        $this->setRequest(Request::create('/bolt/extend/installInfo?package=test&bolt=2.0.0'));
        $response = $this->controller()->installInfo($this->getRequest());

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $parsedOutput = json_decode($response->getContent());
        $this->assertNotEmpty($parsedOutput->dev);
        $this->assertNotEmpty($parsedOutput->stable);
    }

    public function packageInfoProvider()
    {
        $info = array(
            'package' =>
                array(
                    'id'           => '99999',
                    'title'        => 'Test',
                    'source'       => 'https://github.com/',
                    'name'         => 'test',
                    'keywords'     => array(),
                    'type'         => 'bolt-extension',
                    'description'  => 'Test',
                    'approved'     => true,
                    'requirements' =>
                    array(
                      'bolt/bolt' => '>=2.0.0,<3.0.0',
                    ),
                    'versions' =>
                        array(
                          0 => '1.0.0',
                          1 => 'dev-master',
                        )
                ),
            'version' =>
                array(
                    array(
                          'name'               => 'test',
                          'version'            => '1.0.0',
                          'version_normalized' => '1.0.0.0',
                          'source'             =>
                          array(
                            'type'      => 'git',
                            'url'       => 'https://github.com/',
                            'reference' => 'xxx',
                          ),
                          'require' =>
                          array(
                            'bolt/bolt' => '>=2.0.0,<3.0.0',
                          ),
                          'type'        => 'bolt-extension',
                          'stability'   => 'stable',
                          'buildStatus' => 'untested',
                    ),
                    array(
                        'name'               => 'test',
                        'version'            => 'dev-master',
                        'version_normalized' => '9999999-dev',
                        'source'             =>
                            array(
                                'type'      => 'git',
                                'url'       => 'https://github.com/',
                                'reference' => 'XXX',
                            ),
                        'require'     => array('bolt/bolt' => '>=2.0.0,<3.0.0'),
                        'type'        => 'bolt-extension',
                        'stability'   => 'dev',
                        'buildStatus' => 'untested',
                    )
                )
            );
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
