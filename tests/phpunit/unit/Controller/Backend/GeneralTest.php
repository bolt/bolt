<?php
namespace Bolt\Tests\Controller\Backend;

use Bolt\Response\BoltResponse;
use Bolt\Tests\BoltUnitTest;
use Bolt\Tests\Controller\ControllerUnitTest;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class to test correct operation of src/Controller/Backend/Backend.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 **/
class GeneralTest extends ControllerUnitTest
{
    protected function setUp()
    {
    }

    public function testAbout()
    {
        $this->setRequest(Request::create('/bolt/about'));

        $response = $this->controller()->actionAbout();

        $this->assertEquals('about/about.twig', $response->getTemplateName());
    }

    public function testClearCache()
    {

        $this->allowLogin($this->getApp());
        $cache = $this->getMock('Bolt\Cache', array('clearCache'), array(__DIR__, $this->getApp()));
        $cache->expects($this->at(0))
            ->method('clearCache')
            ->will($this->returnValue(array('successfiles' => '1.txt', 'failedfiles' => '2.txt')));

        $cache->expects($this->at(1))
            ->method('clearCache')
            ->will($this->returnValue(array('successfiles' => '1.txt')));

        $this->setService('cache', $cache);
        $this->setRequest(Request::create('/bolt/clearcache'));
        $this->checkTwigForTemplate($this->getApp(), 'clearcache/clearcache.twig');

        $this->controller()->actionClearCache();
        $this->assertNotEmpty($this->getService('session')->getFlashBag()->get('error'));

        $this->setRequest(Request::create('/bolt/clearcache'));
        $this->checkTwigForTemplate($this->getApp(), 'clearcache/clearcache.twig');

        $this->controller()->actionClearCache();
        $this->assertNotEmpty($this->getService('session')->getFlashBag()->get('success'));
    }

    public function testDashboard()
    {
        $this->resetDb();

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
        $this->allowLogin($this->getApp());

        $this->setService('render', $twig);

        $this->setRequest(Request::create('/bolt'));
        $this->controller()->actionDashboard($this->getRequest());
    }

    public function testOmnisearch()
    {
        $this->allowLogin($this->getApp());

        $this->setRequest(Request::create('/bolt/omnisearch', 'GET', array('q' => 'test')));
        $this->checkTwigForTemplate($this->getApp(), 'omnisearch/omnisearch.twig');

        $this->controller()->actionOmnisearch($this->getRequest());
    }

    public function testPrefill()
    {
        $this->setRequest(Request::create('/bolt/prefill'));
        $response = $this->controller()->actionPrefill($this->getRequest());
        $context = $response->getContext();
        $this->assertEquals(3, count($context['context']['contenttypes']));
        $this->assertInstanceOf('Symfony\Component\Form\FormView', $context['context']['form']);

        // Test the post
        $this->setRequest(Request::create('/bolt/prefill', 'POST', array('contenttypes' => 'pages')));
        $response = $this->controller()->actionPrefill($this->getRequest());
        $this->assertEquals('/bolt/prefill', $response->getTargetUrl());

        // Test for the Exception if connection fails to the prefill service
        $store = $this->getMock('Bolt\Storage', array('preFill'), array($this->getApp()));

        $this->markTestIncomplete(
            'Needs work.'
        );

        if ($this->getService('deprecated.php')) {
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

        $this->setService('storage', $store);

        $logger = $this->getMock('Monolog\Logger', array('error'), array('test'));
        $logger->expects($this->once())
            ->method('error')
            ->with("Timeout attempting to the 'Lorem Ipsum' generator. Unable to add dummy content.");
        $this->setService('logger.system', $logger);

        $this->setRequest(Request::create('/bolt/prefill', 'POST', array('contenttypes' => 'pages')));
        $this->controller()->actionPrefill($request);
    }

    public function testTranslation()
    {
        $this->removeCSRF($this->getApp());

        // Render new translation file
        $this->setRequest(Request::create('/bolt/tr/contenttypes/en_CY'));
        $response = $this->controller()->actionTranslation($this->getRequest(), 'contenttypes', 'en_CY');

        $this->assertTrue($response instanceof BoltResponse, 'Response is not instance of BoltResponse');
        $this->assertEquals('editlocale/editlocale.twig', $response->getTemplateName());
        $context = $response->getContext();
        $this->assertEquals('contenttypes.en_CY.yml', $context['context']['basename']);

        // Save updated content and redirect back to page
        $this->setRequest(Request::create(
            '/bolt/tr/contenttypes/en_CY',
            'POST',
            array(
                'form' => array(
                    'contents' => 'test content at least 10 chars',
                    '_token'   => 'xyz'
                )
            )
        ));

        $response = $this->controller()->actionTranslation($this->getRequest(), 'contenttypes', 'en_CY');

        $this->assertTrue($response instanceof RedirectResponse);
        $this->assertTrue($response->isRedirect('/bolt/tr/contenttypes/en_CY'));

        $this->rmdir($this->getService('resources')->getPath('app/resources/translations/en_CY'));

        // Check that YML parse errors get caught
        $this->setRequest(Request::create(
            '/bolt/tr/contenttypes/en_CY',
            'POST',
            array(
                'form' => array(
                    'contents' => '- this is invalid yaml markup: *thisref',
                    '_token'   => 'xyz'
                )
            )
        ));
        $this->controller()->actionTranslation($this->getRequest(), 'contenttypes', 'en_CY');

        $this->assertTrue($response instanceof RedirectResponse, 'Response is not instance of RedirectResponse');
        $errors = $this->getService('session')->getFlashBag()->get('error');
        $this->assertRegExp('/could not be saved/', $errors[0]);
    }

    /**
     * @return \Bolt\Controller\Backend\Backend
     */
    protected function controller()
    {
        return $this->getService('controller.backend.general');
    }
}
