<?php
namespace Bolt\Tests\Controller\Backend;

use Bolt\Content;
use Bolt\Controller\Backend\Records;
use Bolt\Tests\BoltUnitTest;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class to test correct operation of src/Controller/Backend/Records.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 **/
class RecordsTest extends BoltUnitTest
{
    protected function setUp()
    {
        $this->resetDb();
        $this->addSomeContent();
    }

    public function testDelete()
    {
        $app = $this->getApp();
        $controller = $app['controller.backend.records'];

        $app['request'] = $request = Request::create('/bolt/deletecontent/pages/4');
        $response = $controller->actionDelete($request, 'pages', 4);
        // This one should fail for permissions
        $this->assertEquals('/bolt/overview/pages', $response->getTargetUrl());
        $err = $app['session']->getFlashBag()->get('error');
        $this->assertRegexp('/denied/', $err[0]);

        $users = $this->getMock('Bolt\Users', array('isAllowed', 'checkAntiCSRFToken'), array($app));
        $users->expects($this->any())
            ->method('isAllowed')
            ->will($this->returnValue(true));
        $app['users'] = $users;

        // This one should get killed by the anti CSRF check
        $response = $controller->actionDelete($request, 'pages', 4);
        $this->assertEquals('/bolt/overview/pages', $response->getTargetUrl());
        $err = $app['session']->getFlashBag()->get('info');
        $this->assertRegexp('/could not be deleted/', $err[0]);

        $app['users']->expects($this->any())
            ->method('checkAntiCSRFToken')
            ->will($this->returnValue(true));

        $response = $controller->actionDelete($request, 'pages', 4);
        $this->assertEquals('/bolt/overview/pages', $response->getTargetUrl());
        $err = $app['session']->getFlashBag()->get('info');
        $this->assertRegexp('/has been deleted/', $err[0]);
    }

    public function testEditGet()
    {
        $app = $this->getApp();
        $controller = $app['controller.backend.records'];

        // First test will fail permission so we check we are kicked back to the dashboard
        $app['request'] = $request = Request::create('/bolt/editcontent/pages/4');
        $response = $controller->actionEdit($request, 'pages', 4);
        $this->assertEquals('/bolt', $response->getTargetUrl());

        // Since we're the test user we won't automatically have permission to edit.
        $users = $this->getMock('Bolt\Users', array('isAllowed'), array($app));
        $users->expects($this->any())
            ->method('isAllowed')
            ->will($this->returnValue(true));
        $app['users'] = $users;

        $app['request'] = $request = Request::create('/bolt/editcontent/pages/4');
        $response = $controller->actionEdit($request, 'pages', 4);
        $context = $response->getContext();
        $this->assertEquals('Pages', $context['context']['contenttype']['name']);
        $this->assertInstanceOf('Bolt\Content', $context['context']['content']);

        // Test creation
        $app['request'] = $request = Request::create('/bolt/editcontent/pages');
        $response = $controller->actionEdit($request, 'pages', null);
        $context = $response->getContext();
        $this->assertEquals('Pages', $context['context']['contenttype']['name']);
        $this->assertInstanceOf('Bolt\Content', $context['context']['content']);
        $this->assertNull($context['context']['content']->id);

        // Test that non-existent throws a redirect
        $app['request'] = $request = Request::create('/bolt/editcontent/pages/310');
        $this->setExpectedException('Symfony\Component\HttpKernel\Exception\HttpException', 'not-existing');
        $response = $controller->actionEdit($request, 'pages', 310);
    }

    public function testEditDuplicate()
    {
        $app = $this->getApp();
        $controller = $app['controller.backend.records'];

        // Since we're the test user we won't automatically have permission to edit.
        $users = $this->getMock('Bolt\Users', array('isAllowed'), array($app));
        $users->expects($this->any())
            ->method('isAllowed')
            ->will($this->returnValue(true));
        $app['users'] = $users;

        $app['request'] = $request = Request::create('/bolt/editcontent/pages/4', 'GET', array('duplicate' => true));
        $original = $app['storage']->getContent('pages/4');
        $response = $controller->actionEdit($request, 'pages', 4);
        $context = $response->getContext();

        // Check that correct fields are equal in new object
        $new = $context['context']['content'];
        $this->assertEquals($new['body'], $original['body']);
        $this->assertEquals($new['title'], $original['title']);
        $this->assertEquals($new['teaser'], $original['teaser']);

        // Check that some have been cleared.
        $this->assertEquals('', $new['id']);
        $this->assertEquals('', $new['slug']);
        $this->assertEquals('', $new['ownerid']);
    }

    public function testEditCSRF()
    {
        $app = $this->getApp();
        $controller = $app['controller.backend.records'];

        $users = $this->getMock('Bolt\Users', array('isAllowed', 'checkAntiCSRFToken'), array($app));
        $users->expects($this->any())
            ->method('isAllowed')
            ->will($this->returnValue(true));
        $users->expects($this->any())
            ->method('checkAntiCSRFToken')
            ->will($this->returnValue(false));

        $app['users'] = $users;

        $app['request'] = $request = Request::create('/bolt/editcontent/showcases/3', 'POST');
        $this->setExpectedException('Symfony\Component\HttpKernel\Exception\HttpException', 'Something went wrong');
        $controller->actionEdit($request, 'showcases', 3);
    }

    public function testEditPermissions()
    {
        $app = $this->getApp();

        $users = $this->getMock('Bolt\Users', array('isAllowed', 'checkAntiCSRFToken'), array($app));
        $users->expects($this->at(0))
            ->method('isAllowed')
            ->will($this->returnValue(true));

        $users->expects($this->any())
            ->method('checkAntiCSRFToken')
            ->will($this->returnValue(true));

        $app['users'] = $users;

        // We should get kicked here because we dont have permissions to edit this
        $controller = $app['controller.backend.records'];
        $app['request'] = $request = Request::create('/bolt/editcontent/showcases/3', 'POST');
        $response = $controller->actionEdit($request, 'showcases', 3);
        $this->assertEquals('/bolt', $response->getTargetUrl());
    }

    public function testEditPost()
    {
        $app = $this->getApp();
        $controller = $app['controller.backend.records'];

        $users = $this->getMock('Bolt\Users', array('isAllowed', 'checkAntiCSRFToken'), array($app));
        $users->expects($this->any())
            ->method('isAllowed')
            ->will($this->returnValue(true));
        $users->expects($this->any())
            ->method('checkAntiCSRFToken')
            ->will($this->returnValue(true));

        $app['users'] = $users;

        $app['request'] = $request = Request::create('/bolt/editcontent/showcases/3', 'POST', array('floatfield' => 1.2));
        //$original = $app['storage']->getContent('showcases/3');
        $response = $controller->actionEdit($request, 'showcases', 3);
        $this->assertEquals('/bolt/overview/showcases', $response->getTargetUrl());
    }

    public function testEditPostAjax()
    {
        $app = $this->getApp();
        $controller = $app['controller.backend.records'];

        // Since we're the test user we won't automatically have permission to edit.
        $users = $this->getMock('Bolt\Users', array('isAllowed', 'checkAntiCSRFToken'), array($app));
        $users->expects($this->any())
            ->method('isAllowed')
            ->will($this->returnValue(true));
        $users->expects($this->any())
            ->method('checkAntiCSRFToken')
            ->will($this->returnValue(true));

        $app['users'] = $users;

        $app['request'] = $request = Request::create('/bolt/editcontent/pages/4?returnto=ajax', 'POST');
        $original = $app['storage']->getContent('pages/4');
        $response = $controller->actionEdit($request, 'pages', 4);
        $this->assertInstanceOf('Symfony\Component\HttpFoundation\JsonResponse', $response);
        $returned = json_decode($response->getContent());
        $this->assertEquals($original['title'], $returned->title);
    }

    public function testModify()
    {
        // Try status switches
        $app = $this->getApp();
        $controller = $app['controller.backend.records'];

        $app['request'] = $request = Request::create('/bolt/content/held/pages/3');

        // This one should fail for lack of permission
        $response = $controller->actionModify($request, 'held', 'pages', 3);
        $this->assertEquals('/bolt/overview/pages', $response->getTargetUrl());
        $err = $app['session']->getFlashBag()->get('error');
        $this->assertRegexp('/right privileges/', $err[0]);

        $users = $this->getMock('Bolt\Users', array('isAllowed', 'checkAntiCSRFToken', 'isContentStatusTransitionAllowed'), array($app));
        $users->expects($this->any())
            ->method('isAllowed')
            ->will($this->returnValue(true));
        $app['users'] = $users;

        // This one should fail for the second permission check `isContentStatusTransitionAllowed`
        $response = $controller->actionModify($request, 'held', 'pages', 3);
        $this->assertEquals('/bolt/overview/pages', $response->getTargetUrl());
        $err = $app['session']->getFlashBag()->get('error');
        $this->assertRegexp('/right privileges/', $err[0]);

        $app['users']->expects($this->any())
            ->method('isContentStatusTransitionAllowed')
            ->will($this->returnValue(true));

        $response = $controller->actionModify($request, 'held', 'pages', 3);
        $this->assertEquals('/bolt/overview/pages', $response->getTargetUrl());
        $err = $app['session']->getFlashBag()->get('info');
        $this->assertRegexp('/has been changed/', $err[0]);

        // Test an invalid action fails
        $app['request'] = $request = Request::create('/bolt/content/fake/pages/3');
        $response = $controller->actionModify($request, 'fake', 'pages', 3);
        $err = $app['session']->getFlashBag()->get('error');
        $this->assertRegexp('/No such action/', $err[0]);

        // Test that any save error gets reported
        $app['request'] = $request = Request::create('/bolt/content/held/pages/3');

        $storage = $this->getMock('Bolt\Storage', array('updateSingleValue'), array($app));
        $storage->expects($this->once())
            ->method('updateSingleValue')
            ->will($this->returnValue(false));

        $app['storage'] = $storage;

        $response = $controller->actionModify($request, 'held', 'pages', 3);
        $this->assertEquals('/bolt/overview/pages', $response->getTargetUrl());
        $err = $app['session']->getFlashBag()->get('info');
        $this->assertRegexp('/could not be modified/', $err[0]);

        // Test the delete proxy action
        // Note that the response will be 'could not be deleted'. Since this just
        // passes on the the deleteContent method that is enough to indicate that
        // the work of this method is done.
        $app['request'] = $request = Request::create('/bolt/content/delete/pages/3');
        $response = $controller->actionModify($request, 'delete', 'pages', 3);
        $this->assertEquals('/bolt/overview/pages', $response->getTargetUrl());
        $err = $app['session']->getFlashBag()->get('info');
        $this->assertRegexp('/could not be deleted/', $err[0]);
    }

    public function testOverview()
    {
        $app = $this->getApp();
        $controller = $app['controller.backend.records'];

        $app['request'] = $request = Request::create('/bolt/overview/pages');
        $response = $controller->actionOverview($request, 'pages');
        $context = $response->getContext();
        $this->assertEquals('Pages', $context['context']['contenttype']['name']);
        $this->assertGreaterThan(1, count($context['context']['multiplecontent']));

        // Test the the default records per page can be set
        $app['request'] = $request = Request::create('/bolt/overview/showcases');
        $response = $controller->actionOverview($request, 'showcases');

        // Test redirect when user isn't allowed.
        $users = $this->getMock('Bolt\Users', array('isAllowed'), array($app));
        $users->expects($this->once())
        ->method('isAllowed')
        ->will($this->returnValue(false));
        $app['users'] = $users;

        $app['request'] = $request = Request::create('/bolt/overview/pages');
        $response = $controller->actionOverview($request, 'pages');
        $this->assertEquals('/bolt', $response->getTargetUrl());
    }

    public function testOverviewFiltering()
    {
        $app = $this->getApp();
        $controller = $app['controller.backend.records'];

        $app['request'] = $request = Request::create(
            '/bolt/overview/pages',
            'GET',
            array(
                'filter'            => 'Lorem',
                'taxonomy-chapters' => 'main'
            )
        );
        $response = $controller->actionOverview($request, 'pages');
        $context = $response->getContext();
        $this->assertArrayHasKey('filter', $context['context']);
        $this->assertEquals('Lorem', $context['context']['filter'][0]);
        $this->assertEquals('main', $context['context']['filter'][1]);
    }

    public function testRelated()
    {
        $app = $this->getApp();
        $controller = $app['controller.backend.records'];

        $app['request'] = $request = Request::create('/bolt/relatedto/showcases/1');
        $response = $controller->actionRelated($request, 'showcases', 1);
        $context = $response->getContext();
        $this->assertEquals(1, $context['context']['id']);
        $this->assertEquals('Showcase', $context['context']['name']);
        $this->assertEquals('Showcases', $context['context']['contenttype']['name']);
        $this->assertEquals(2, count($context['context']['relations']));
        // By default we show the first one
        $this->assertEquals('Entries', $context['context']['show_contenttype']['name']);

        // Now we specify we want to see pages
        $app['request'] = $request = Request::create('/bolt/relatedto/showcases/1', 'GET', array('show' => 'pages'));
        $response = $controller->actionRelated($request, 'showcases', 1);
        $context = $response->getContext();
        $this->assertEquals('Pages', $context['context']['show_contenttype']['name']);

        // Try a request where there are no relations
        $app['request'] = $request = Request::create('/bolt/relatedto/pages/1');
        $response = $controller->actionRelated($request, 'pages', 1);
        $context = $response->getContext();
        $this->assertNull($context['context']['relations']);

        // Test redirect when user isn't allowed.
        $users = $this->getMock('Bolt\Users', array('isAllowed'), array($app));
        $users->expects($this->once())
            ->method('isAllowed')
            ->will($this->returnValue(false));
        $app['users'] = $users;

        $app['request'] = $request = Request::create('/bolt/relatedto/showcases/1');
        $response = $controller->actionRelated($request, 'showcases', 1);
        $this->assertEquals('/bolt', $response->getTargetUrl());
    }
}
