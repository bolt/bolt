<?php
namespace Bolt\Tests\Controller\Backend;

use Bolt\Controller\Zone;
use Bolt\Response\BoltResponse;
use Bolt\Tests\Controller\ControllerUnitTest;
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
        $kernel = $this->getMock('Symfony\\Component\\HttpKernel\\HttpKernelInterface');
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
        $cache = $this->getMock('Bolt\Cache', ['clearCache'], [__DIR__, $this->getApp()]);
        $cache->expects($this->at(0))
            ->method('clearCache')
            ->will($this->returnValue(['successfiles' => '1.txt', 'failedfiles' => '2.txt']));

        $cache->expects($this->at(1))
            ->method('clearCache')
            ->will($this->returnValue(['successfiles' => '1.txt']));

        $this->setService('cache', $cache);
        $this->setRequest(Request::create('/bolt/clearcache'));
        $this->checkTwigForTemplate($this->getApp(), '@bolt/clearcache/clearcache.twig');

        $this->controller()->clearCache();
        $this->assertNotEmpty($this->getFlashBag()->get('error'));

        $this->setRequest(Request::create('/bolt/clearcache'));
        $this->checkTwigForTemplate($this->getApp(), '@bolt/clearcache/clearcache.twig');

        $this->controller()->clearCache();
        $this->assertNotEmpty($this->getFlashBag()->get('success'));
    }

    public function testDashboard()
    {
        $twig = $this->getMockTwig();
        $phpunit = $this;
        $testHandler = function ($template, $context) use ($phpunit) {
            $phpunit->assertEquals('@bolt/dashboard/dashboard.twig', $template);
            $phpunit->assertNotEmpty($context['context']);
            $phpunit->assertArrayHasKey('latest', $context['context']);
            $phpunit->assertArrayHasKey('suggestloripsum', $context['context']);

            return new Response();
        };

        $twig->expects($this->any())
            ->method('render')
            ->will($this->returnCallback($testHandler));
        $this->allowLogin($this->getApp());

        $this->setService('render', $twig);

        $this->setRequest(Request::create('/bolt'));
        $this->controller()->dashboard();
    }

    public function testOmnisearch()
    {
        $this->allowLogin($this->getApp());

        $this->setRequest(Request::create('/bolt/omnisearch', 'GET', ['q' => 'test']));
        $this->checkTwigForTemplate($this->getApp(), '@bolt/omnisearch/omnisearch.twig');

        $this->controller()->omnisearch($this->getRequest());
    }

    public function testPrefill()
    {
        $this->setRequest(Request::create('/bolt/prefill'));
        $response = $this->controller()->prefill($this->getRequest());
        $context = $response->getContext();
        $this->assertEquals(3, count($context['context']['contenttypes']));
        $this->assertInstanceOf('Symfony\Component\Form\FormView', $context['context']['form']);

        // Test the post
        $this->setRequest(Request::create('/bolt/prefill', 'POST', ['contenttypes' => 'pages']));
        $response = $this->controller()->prefill($this->getRequest());
        $this->assertEquals('/bolt/prefill', $response->getTargetUrl());

        // Test for the Exception if connection fails to the prefill service
        $store = $this->getMock('Bolt\Storage', ['preFill'], [$this->getApp()]);

        $guzzleRequest = new \GuzzleHttp\Message\Request('GET', '');
        $store->expects($this->any())
            ->method('preFill')
            ->will($this->returnCallback(function () use ($guzzleRequest) {
                throw new \GuzzleHttp\Exception\RequestException('', $guzzleRequest);
        }));

        $this->setService('storage', $store);

        $logger = $this->getMock('Monolog\Logger', ['error'], ['test']);
        $logger->expects($this->once())
            ->method('error')
            ->with("Timeout attempting to the 'Lorem Ipsum' generator. Unable to add dummy content.");
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

        $this->assertTrue($response instanceof BoltResponse, 'Response is not instance of BoltResponse');
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
                    '_token'   => 'xyz'
                ]
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
                    '_token'   => 'xyz'
                ]
            ]
        ));
        $this->controller()->translation($this->getRequest(), 'contenttypes', 'en_CY');

        $this->assertTrue($response instanceof RedirectResponse, 'Response is not instance of RedirectResponse');
        $errors = $this->getFlashBag()->get('error');
        $this->assertRegExp('/could not be saved/', $errors[0]);
    }

    /**
     * @return \Bolt\Controller\Backend\General
     */
    protected function controller()
    {
        return $this->getService('controller.backend.general');
    }
}
