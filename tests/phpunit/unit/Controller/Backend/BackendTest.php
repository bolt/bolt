<?php
namespace Bolt\Tests\Controller\Backend;

use Bolt\Controllers\Backend\Backend;
use Bolt\Tests\BoltUnitTest;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Yaml\Yaml;

/**
 * Class to test correct operation of src/Controllers/Backend/Backend.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 **/
class BackendTest extends BoltUnitTest
{
    public function testAbout()
    {
        $app = $this->getApp();
        $controller = new Backend();
        $controller->connect($app);

        $request = Request::create('/bolt/about');
        $response = $controller->actionAbout();
        $this->assertEquals('about/about.twig', $response->getTemplateName());
    }

    public function testClearCache()
    {
        $app = $this->getApp();
        $this->allowLogin($app);
        $cache = $this->getMock('Bolt\Cache', array('clearCache'), array(__DIR__, $app));
        $cache->expects($this->at(0))
            ->method('clearCache')
            ->will($this->returnValue(array('successfiles' => '1.txt', 'failedfiles' => '2.txt')));

        $cache->expects($this->at(1))
            ->method('clearCache')
            ->will($this->returnValue(array('successfiles' => '1.txt')));

        $app['cache'] = $cache;
        $request = Request::create('/bolt/clearcache');
        $this->checkTwigForTemplate($app, 'clearcache/clearcache.twig');

        $app->handle($request);
        $this->assertNotEmpty($app['session']->getFlashBag()->get('error'));

        $request = Request::create('/bolt/clearcache');
        $this->checkTwigForTemplate($app, 'clearcache/clearcache.twig');
        $app->handle($request);
        $this->assertNotEmpty($app['session']->getFlashBag()->get('success'));
    }

    public function testDashboard()
    {
        $this->resetDb();
        $app = $this->getApp();
        $this->addSomeContent();
        $twig = $this->getMockTwig();
        $phpunit = $this;
        $testHandler = function ($template, $context) use ($phpunit) {
            $phpunit->assertEquals('dashboard/dashboard.twig', $template);
            $phpunit->assertNotEmpty($context['context']);
            $phpunit->assertArrayHasKey('latest', $context['context']);
            $phpunit->assertArrayHasKey('suggestloripsum', $context['context']);

            return new Response();
        };

        $twig->expects($this->any())
            ->method('render')
            ->will($this->returnCallBack($testHandler));
        $this->allowLogin($app);
        $app['render'] = $twig;
        $request = Request::create('/bolt');
        $app->run($request);
    }

    public function testOmnisearch()
    {
        $app = $this->getApp();
        $this->allowLogin($app);

        $request = Request::create('/bolt/omnisearch', 'GET', array('q' => 'test'));
        $this->checkTwigForTemplate($app, 'omnisearch/omnisearch.twig');

        $app->run($request);
    }

    public function testPrefill()
    {
        $app = $this->getApp();
        $controller = new Backend();
        $controller->connect($app);

        $request = Request::create('/bolt/prefill');
        $response = $controller->actionPrefill($request);
        $context = $response->getContext();
        $this->assertEquals(3, count($context['context']['contenttypes']));
        $this->assertInstanceOf('Symfony\Component\Form\FormView', $context['context']['form']);

        // Test the post
        $app['request'] = $request = Request::create('/bolt/prefill', 'POST', array('contenttypes' => 'pages'));
        $response = $controller->actionPrefill($request);
        $this->assertEquals('/bolt/prefill', $response->getTargetUrl());

        // Test for the Exception if connection fails to the prefill service
        $store = $this->getMock('Bolt\Storage', array('preFill'), array($app));

        $this->markTestIncomplete(
            'Needs work.'
        );

        if ($app['deprecated.php']) {
            $store->expects($this->any())
                ->method('preFill')
                ->will($this->returnCallback(function () {
                    throw new \Guzzle\Http\Exception\RequestException();
            }));
        } else {
            $request = new \GuzzleHttp\Message\Request('GET', '');
            $store->expects($this->any())
                ->method('preFill')
                ->will($this->returnCallback(function () use ($request) {
                    throw new \GuzzleHttp\Exception\RequestException('', $request);
            }));
        }

        $app['storage'] = $store;

        $logger = $this->getMock('Monolog\Logger', array('error'), array('test'));
        $logger->expects($this->once())
            ->method('error')
            ->with("Timeout attempting to the 'Lorem Ipsum' generator. Unable to add dummy content.");
        $app['logger.system'] = $logger;

        $app['request'] = $request = Request::create('/bolt/prefill', 'POST', array('contenttypes' => 'pages'));
        $response = $controller->actionPrefill($request);
    }

    public function testTranslation()
    {
        // We make a new translation and ensure that the content is created.
        $app = $this->getApp();
        $controller = new Backend();
        $controller->connect($app);

        $this->removeCSRF($app);
        $app['request'] = $request = Request::create('/bolt/tr/contenttypes/en_CY');
        $response = $controller->actionTranslation($request, 'contenttypes', 'en_CY');
        $context = $response->getContext();
        $this->assertEquals('contenttypes.en_CY.yml', $context['context']['basename']);

        // Now try and post the update
        $app['request'] = $request = Request::create(
            '/bolt/tr/contenttypes/en_CY',
            'POST',
            array(
                'form' => array(
                    'contents' => 'test content at least 10 chars',
                    '_token'   => 'xyz'
                )
            )
        );
        $response = $controller->actionTranslation($request, 'contenttypes', 'en_CY');
        $context = $response->getContext();
        $this->assertEquals('editlocale/editlocale.twig', $response->getTemplateName());

        // Write isn't allowed initially so check the error
        $error = $app['session']->getFlashBag()->get('error');
        $this->assertRegexp('/is not writable/', $error[0]);

        // Check that YML parse errors get caught
        $app['request'] = $request = Request::create(
            '/bolt/tr/contenttypes/en_CY',
            'POST',
            array(
                'form' => array(
                    'contents' => "- this is invalid yaml markup: *thisref",
                    '_token'   => 'xyz'
                )
            )
        );
        $response = $controller->actionTranslation($request, 'contenttypes', 'en_CY');
        $info = $app['session']->getFlashBag()->get('error');
        $this->assertRegexp('/could not be saved/', $info[0]);
    }
}
