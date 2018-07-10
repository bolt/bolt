<?php

namespace Bolt\Tests\Controller\Backend;

use Bolt\Application;
use Bolt\Controller\Zone;
use Bolt\Logger\FlashLogger;
use Bolt\Response\TemplateView;
use Bolt\Tests\Controller\ControllerUnitTest;
use Bolt\Tests\Mocks\LoripsumMock;
use Symfony\Component\Form\FormErrorIterator;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
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

        $this->assertEquals('@bolt/about/about.twig', $response->getTemplate());
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
        $flashes = $this->createMock(FlashLogger::class);
        $this->setService('logger.flash', $flashes);

        $flashes->expects($this->once())
            ->method('error');

        $flashes->expects($this->once())
            ->method('success');

        $response = $this->controller()->clearCache();
        $this->assertEquals('@bolt/clearcache/clearcache.twig', $response->getTemplate());

        $this->setRequest(Request::create('/bolt/clearcache'));

        $response = $this->controller()->clearCache();
        $this->assertEquals('@bolt/clearcache/clearcache.twig', $response->getTemplate());
    }

    public function testDashboard()
    {
        $this->setRequest(Request::create('/bolt'));

        $response = $this->controller()->dashboard();

        $this->assertEquals('@bolt/dashboard/dashboard.twig', $response->getTemplate());
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
        $this->assertEquals('@bolt/omnisearch/omnisearch.twig', $response->getTemplate());
    }

    public function testPrefill()
    {
        $this->setRequest(Request::create('/bolt/prefill'));
        $response = $this->controller()->prefill($this->getRequest());
        $context = $response->getContext();
        $this->assertEquals(5, count($context['context']['contenttypes']));
        $this->assertInstanceOf(FormView::class, $context['context']['form']);

        // Test the post
        $this->setRequest(Request::create('/bolt/prefill', 'POST', ['contenttypes' => 'pages']));
        $response = $this->controller()->prefill($this->getRequest());
        $this->assertEquals('/bolt/prefill', $response->getTargetUrl());

        $app = $this->getApp();
        $app['prefill'] = new LoripsumMock();

        $this->setRequest(Request::create('/bolt/prefill', 'POST', ['contenttypes' => 'pages']));
        $response = $this->controller()->prefill($this->getRequest());
        $this->assertInstanceOf(RedirectResponse::class, $response);
    }

    public function testTranslation()
    {
        $this->removeCSRF($this->getApp());

        // Render new translation file
        $this->setRequest(Request::create('/bolt/tr'));
        $response = $this->controller()->translation($this->getRequest(), 'messages', 'en_GB');

        $this->assertTrue($response instanceof TemplateView, 'Response is not instance of TemplateView');
        $this->assertEquals('@bolt/editlocale/editlocale.twig', $response->getTemplate());
        $context = $response->getContext();
        $this->assertEquals('messages.en_GB.yml', $context['context']['basename']);

        // Save updated content and redirect back to page
        $this->setRequest(Request::create(
            '/bolt/tr',
            'POST',
            [
                'file_edit' => [
                    'contents' => 'test content at least 10 chars',
                    '_token'   => 'xyz',
                ],
            ]
        ));

        $response = $this->controller()->translation($this->getRequest(), 'messages', 'en_GB');

        $this->assertInstanceOf(TemplateView::class, $response);
        $this->assertArrayHasKey('context', $context);
        $this->assertArrayHasKey('form', $context['context']);
        $this->assertInstanceOf(FormView::class, $context['context']['form']);

        /** @var FormView $form */
        $form = $context['context']['form'];
        /** @var FormErrorIterator $errors */
        $errors = $form->vars['errors'];
        $this->assertFalse($errors->getForm()->get('contents')->isValid());

        // Check that YML parse errors get caught
        $this->setRequest(Request::create(
            '/bolt/tr',
            'POST',
            [
                'file_edit' => [
                    'contents' => 'form true',
                    '_token'   => 'xyz',
                ],
            ]
        ));

        $response = $this->controller()->translation($this->getRequest(), 'contenttypes', 'en_GB');
        $context = $response->getContext()->toArray();

        $this->assertInstanceOf(TemplateView::class, $response);
        $this->assertArrayHasKey('context', $context);
        $this->assertArrayHasKey('form', $context['context']);
        $this->assertInstanceOf(FormView::class, $context['context']['form']);

        /** @var FormView $form */
        $form = $context['context']['form'];
        /** @var FormErrorIterator $errors */
        $errors = $form->vars['errors'];
        $this->assertFalse($errors->getForm()->get('contents')->isValid());
    }

    /**
     * @return \Bolt\Controller\Backend\General
     */
    protected function controller()
    {
        return $this->getService('controller.backend.general');
    }
}
