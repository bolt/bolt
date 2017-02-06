<?php

namespace Bolt\Tests\Controller\Backend;

use Bolt\Application;
use Bolt\Controller\Zone;
use Bolt\Legacy\Storage;
use Bolt\Logger\FlashLogger;
use Bolt\Response\TemplateResponse;
use Bolt\Tests\Controller\ControllerUnitTest;
use Prophecy\Argument\Token\StringContainsToken;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class to test correct operation of src/Controller/Backend/Backend.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 **/
class GeneralTest extends ControllerUnitTest
{
    /**
     * @covers \Bolt\Controller\Zone::get
     * @covers \Bolt\Controller\Zone::isBackend
     */
    public function testControllerZone()
    {
        $app = $this->getApp();
        $this->setRequest(Request::create('/bolt'));

        $request = $this->getRequest();
        $kernel = $this->createMock(HttpKernelInterface::class);
        $app['dispatcher']->dispatch(KernelEvents::REQUEST, new GetResponseEvent($kernel, $request, HttpKernelInterface::MASTER_REQUEST));

        $this->assertEquals('backend', Zone::get($request));
        $this->assertTrue(Zone::isBackend($request));
    }

    public function testAbout()
    {
        $this->setRequest(Request::create('/bolt/about'));

        $response = $this->controller()->about();

        $this->assertEquals('@bolt/about/about.twig', $response->getTemplateName());
    }

    public function testClearCache()
    {
        $this->allowLogin($this->getApp());
        $cache = $this->getMockCache();
        $cache->expects($this->at(0))
            ->method('flushAll')
            ->will($this->returnValue(false));

        $cache->expects($this->at(1))
            ->method('flushAll')
            ->will($this->returnValue(true));

        $this->setService('cache', $cache);
        $this->setRequest(Request::create('/bolt/clearcache'));

        /** @var Application $app */
        $app = $this->getApp();
        $flashes = $this->getMock(FlashLogger::class);
        $app['logger.flash'] = $flashes;

        $flashes->expects($this->once())
            ->method('error');

        $flashes->expects($this->once())
            ->method('success');

        $response = $this->controller()->clearCache();
        $this->assertEquals('@bolt/clearcache/clearcache.twig', $response->getTemplateName());

        $this->setRequest(Request::create('/bolt/clearcache'));

        $response = $this->controller()->clearCache();
        $this->assertEquals('@bolt/clearcache/clearcache.twig', $response->getTemplateName());
    }

    public function testDashboard()
    {
        $this->setRequest(Request::create('/bolt'));

        $response = $this->controller()->dashboard();

        $this->assertEquals('@bolt/dashboard/dashboard.twig', $response->getTemplateName());
        $context = $response->getContext();
        $this->assertArrayHasKey('context', $context);
        $this->assertArrayHasKey('latest', $context['context']);
        $this->assertArrayHasKey('suggestloripsum', $context['context']);
    }

    public function testOmnisearch()
    {
        $this->allowLogin($this->getApp());

        $this->setRequest(Request::create('/bolt/omnisearch', 'GET', ['q' => 'test']));

        $response = $this->controller()->omnisearch($this->getRequest());
        $this->assertEquals('@bolt/omnisearch/omnisearch.twig', $response->getTemplateName());
    }

    public function testPrefill()
    {
        $this->setRequest(Request::create('/bolt/prefill'));
        $response = $this->controller()->prefill($this->getRequest());
        $context = $response->getContext();
        $this->assertEquals(4, count($context['context']['contenttypes']));
        $this->assertInstanceOf('Symfony\Component\Form\FormView', $context['context']['form']);

        // Test the post
        $this->setRequest(Request::create('/bolt/prefill', 'POST', ['contenttypes' => 'pages']));
        $response = $this->controller()->prefill($this->getRequest());
        $this->assertEquals('/bolt/prefill', $response->getTargetUrl());

        // Test for the Exception if connection fails to the prefill service
        $store = $this->getMockBuilder(Storage::class)
            ->setMethods(['preFill'])
            ->setConstructorArgs([$this->getApp()])
            ->getMock()
        ;

        $app = $this->getApp();
        if ($app['guzzle.api_version'] === 5) {
            $guzzleRequest = new \GuzzleHttp\Message\Request('GET', '');
        } else {
            $guzzleRequest = new \GuzzleHttp\Psr7\Request('GET', '');
        }
        $store->expects($this->any())
            ->method('preFill')
            ->will($this->returnCallback(function () use ($guzzleRequest) {
                throw new \GuzzleHttp\Exception\RequestException('', $guzzleRequest);
            }));

        $this->setService('storage', $store);

        $logger = $this->getMockMonolog();
        $logger->expects($this->once())
            ->method('error')
            ->with("Timeout attempting connection to the 'Lorem Ipsum' generator. Unable to add dummy content.");
        $this->setService('logger.system', $logger);

        $this->setRequest(Request::create('/bolt/prefill', 'POST', ['contenttypes' => 'pages']));
        $this->controller()->prefill($this->getRequest());
    }

    public function testTranslation()
    {
        $this->removeCSRF($this->getApp());

        // Render new translation file
        $this->setRequest(Request::create('/bolt/tr/contenttypes/en_CY'));
        $response = $this->controller()->translation($this->getRequest(), 'contenttypes', 'en_CY');

        $this->assertTrue($response instanceof TemplateResponse, 'Response is not instance of TemplateResponse');
        $this->assertEquals('@bolt/editlocale/editlocale.twig', $response->getTemplateName());
        $context = $response->getContext();
        $this->assertEquals('contenttypes.en_CY.yml', $context['context']['basename']);

        // Save updated content and redirect back to page
        $this->setRequest(Request::create(
            '/bolt/tr/contenttypes/en_CY',
            'POST',
            [
                'form' => [
                    'contents' => 'test content at least 10 chars',
                    '_token'   => 'xyz',
                ],
            ]
        ));

        $response = $this->controller()->translation($this->getRequest(), 'contenttypes', 'en_CY');

        $this->assertTrue($response instanceof RedirectResponse);
        $this->assertTrue($response->isRedirect('/bolt/tr/contenttypes/en_CY'));

        $this->rmdir($this->getService('resources')->getPath('app/resources/translations/en_CY'));

        // Check that YML parse errors get caught
        $this->setRequest(Request::create(
            '/bolt/tr/contenttypes/en_CY',
            'POST',
            [
                'form' => [
                    'contents' => '- this is invalid yaml markup: *thisref',
                    '_token'   => 'xyz',
                ],
            ]
        ));

        $flash = $this->prophesize(FlashLogger::class);
        $flash->keys()->shouldBeCalled();
        $flash->get('info')->shouldBeCalled();
        $flash->get('success')->shouldBeCalled();
        $flash->get('error')->shouldBeCalled();
        $flash->error(new StringContainsToken('could not be saved'))->shouldBeCalled();
        $this->setService('logger.flash', $flash->reveal());

        $this->controller()->translation($this->getRequest(), 'contenttypes', 'en_CY');

        $this->assertTrue($response instanceof RedirectResponse, 'Response is not instance of RedirectResponse');
    }

    /**
     * @return \Bolt\Controller\Backend\General
     */
    protected function controller()
    {
        return $this->getService('controller.backend.general');
    }
}
