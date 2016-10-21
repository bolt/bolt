<?php
namespace Bolt\Tests\Controller\Backend;

use Bolt\Tests\Controller\ControllerUnitTest;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class to test correct operation of src/Controller/Backend/Log.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 **/
class LogTest extends ControllerUnitTest
{
    public function setUp()
    {
        $this->resetConfig();
        $this->resetDb();
        $this->addSomeContent();

        $content = $this->getService('storage')->getContentObject('pages');
        $content['contentid'] = 1;
        $this->getService('storage')->saveContent($content, 'pages');
    }

    public function testChangeOverview()
    {
        $this->allowLogin($this->getApp());

        $changeRepository = $this->getService('storage')->getRepository('Bolt\Storage\Entity\LogChange');
        $systemRepository = $this->getService('storage')->getRepository('Bolt\Storage\Entity\LogSystem');
        $log = $this->getMock('Bolt\Logger\Manager', ['clear', 'trim'], [$this->getApp(), $changeRepository, $systemRepository]);

        $log->expects($this->once())
            ->method('clear')
            ->will($this->returnValue(true));
        $log->expects($this->once())
            ->method('trim')
            ->will($this->returnValue(true));
        $this->setService('logger.manager', $log);

        $this->setRequest(Request::create('/bolt/changelog', 'GET', ['action' => 'trim']));
        $this->controller()->changeOverview($this->getRequest());
        $this->assertNotEmpty($this->getFlashBag()->get('success'));

        $this->setRequest(Request::create('/bolt/changelog', 'GET', ['action' => 'clear']));
        $response = $this->controller()->changeOverview($this->getRequest());
        $this->assertNotEmpty($this->getFlashBag()->get('success'));

        $this->assertEquals('/bolt/changelog', $response->getTargetUrl());

        $this->setRequest(Request::create('/bolt/changelog'));
        $this->checkTwigForTemplate($this->getApp(), '@bolt/activity/changelog.twig');
        $this->controller()->changeOverview($this->getRequest());
    }

    public function testChangeRecord()
    {
        $this->getService('config')->set('general/changelog/enabled', true);

        $this->setRequest(Request::create('/bolt/editcontent/pages/1'));
        /** @var \Bolt\Legacy\Content $record */
        $record = $this->getService('storage')->getContent('pages/1');
        $record->setValue('title', 'Clippy was here!');
        $this->getService('storage')->saveContent($record, 'Saving');

        // Test valid entry
        $this->setRequest(Request::create('/bolt/changelog/pages/1/1'));
        $response = $this->controller()->changeRecord($this->getRequest(), 'pages', 1, 1);

        $context = $response->getContext();
        $this->assertInstanceOf('Bolt\Storage\Entity\LogChange', $context['context']['entry']);

        // Test non-existing entry
        $this->setExpectedException('Symfony\Component\HttpKernel\Exception\HttpException', 'exist');
        $this->setRequest(Request::create('/bolt/changelog/pages/1/100'));
        $response = $this->controller()->changeRecord($this->getRequest(), 'pages', 1, 100);
        $response->getContext();
    }

    public function testChangeRecordListing()
    {
        $this->getService('config')->set('general/changelog/enabled', true);

        // First test tests without any changelogs available
        $this->setRequest(Request::create('/bolt/changelog/pages'));
        $response = $this->controller()->changeRecordListing($this->getRequest(), 'pages', null);

        $context = $response->getContext();

        $this->assertEmpty($context['context']['entries']);
        $this->assertNull($context['context']['content']);
        $this->assertEquals('Pages', $context['context']['title']);
        $this->assertEquals('pages', $context['context']['contenttype']['slug']);

        // Search for a specific record where the content object doesn't exist
        $this->setRequest(Request::create('/bolt/changelog/pages/1'));
        $response = $this->controller()->changeRecordListing($this->getRequest(), 'pages', 200);

        $context = $response->getContext();
        $this->assertEquals('Page #200', $context['context']['title']);

        // This block generates a changelog on the page in question so we have something to test.
        $this->setRequest(Request::create('/'));
        /** @var \Bolt\Legacy\Content $content */
        $content = $this->getService('storage')->getContent('pages/1');
        $content->setValues(['status' => 'draft', 'ownerid' => 99]);
        $this->getService('storage')->saveContent($content, 'Test Suite Update');

        // Now handle all the other request variations
        $this->setRequest(Request::create('/bolt/changelog'));
        $response = $this->controller()->changeRecordListing($this->getRequest(), null, null);

        $context = $response->getContext();
        $this->assertEquals('All ContentTypes', $context['context']['title']);
        $this->assertEquals(1, count($context['context']['entries']));
        $this->assertEquals(1, $context['context']['pagecount']);

        $this->setRequest(Request::create('/bolt/changelog/pages'));
        $response = $this->controller()->changeRecordListing($this->getRequest(), 'pages', null);

        $context = $response->getContext();
        $this->assertEquals('Pages', $context['context']['title']);
        $this->assertEquals(1, count($context['context']['entries']));
        $this->assertEquals(1, $context['context']['pagecount']);

        $this->setRequest(Request::create('/bolt/changelog/pages/1'));
        $response = $this->controller()->changeRecordListing($this->getRequest(), 'pages', '1');

        $context = $response->getContext();
        $this->assertEquals($content['title'], $context['context']['title']);
        $this->assertEquals(1, count($context['context']['entries']));
        $this->assertEquals(1, $context['context']['pagecount']);

        // Test pagination
        $this->setRequest(Request::create('/bolt/changelog/pages', 'GET', ['page' => 'all']));
        $response = $this->controller()->changeRecordListing($this->getRequest(), 'pages', null);

        $context = $response->getContext();
        $this->assertNull($context['context']['currentpage']);
        $this->assertNull($context['context']['pagecount']);

        $this->setRequest(Request::create('/bolt/changelog/pages', 'GET', ['page' => '1']));
        $response = $this->controller()->changeRecordListing($this->getRequest(), 'pages', null);
        $context = $response->getContext();
        $this->assertEquals(1, $context['context']['currentpage']);

        // Finally we delete the original content record, but make sure the logs still show
        $originalTitle = $content['title'];
        $this->getService('storage')->deleteContent('pages', 1);
        $this->setRequest(Request::create('/bolt/changelog/pages/1'));
        $response = $this->controller()->changeRecordListing($this->getRequest(), 'pages', '1');

        $context = $response->getContext();
        $this->assertEquals($originalTitle, $context['context']['title']);
        // Note the delete generates an extra log, hence the extra count
        $this->assertEquals(2, count($context['context']['entries']));
    }

    public function testSystemOverview()
    {
        $this->allowLogin($this->getApp());

        $changeRepository = $this->getService('storage')->getRepository('Bolt\Storage\Entity\LogChange');
        $systemRepository = $this->getService('storage')->getRepository('Bolt\Storage\Entity\LogSystem');
        $log = $this->getMock('Bolt\Logger\Manager', ['clear', 'trim'], [$this->getApp(), $changeRepository, $systemRepository]);
        $log->expects($this->once())
            ->method('clear')
            ->will($this->returnValue(true));
        $log->expects($this->once())
            ->method('trim')
            ->will($this->returnValue(true));
        $this->setService('logger.manager', $log);

        $this->setRequest(Request::create('/bolt/systemlog', 'GET', ['action' => 'trim']));
        $this->controller()->systemOverview($this->getRequest());
        $this->assertNotEmpty($this->getFlashBag()->get('success'));

        $this->setRequest(Request::create('/bolt/systemlog', 'GET', ['action' => 'clear']));
        $response = $this->controller()->systemOverview($this->getRequest());
        $this->assertNotEmpty($this->getFlashBag()->get('success'));

        $this->assertEquals('/bolt/systemlog', $response->getTargetUrl());

        $this->setRequest(Request::create('/bolt/systemlog'));
        $this->checkTwigForTemplate($this->getApp(), '@bolt/activity/systemlog.twig');
        $this->controller()->systemOverview($this->getRequest());
    }

    /**
     * @return \Bolt\Controller\Backend\Log
     */
    protected function controller()
    {
        return $this->getService('controller.backend.log');
    }
}
