<?php
namespace Bolt\Tests\Controller\Backend;

use Bolt\Tests\Controller\ControllerUnitTest;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Security\Csrf\CsrfTokenManager;
use Symfony\Component\Security\Csrf\TokenStorage\SessionTokenStorage;

/**
 * Class to test correct operation of src/Controller/Backend/Records.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 **/
class RecordsTest extends ControllerUnitTest
{
    public function setUp()
    {
        $this->resetConfig();
    }

    public function testEditGet()
    {
        // First test will fail permission so we check we are kicked back to the dashboard
        $this->setRequest(Request::create('/bolt/editcontent/pages/4'));
        $response = $this->controller()->edit($this->getRequest(), 'pages', 4);
        $this->assertEquals('/bolt', $response->getTargetUrl());

        // Since we're the test user we won't automatically have permission to edit.
        $permissions = $this->getMock('Bolt\AccessControl\Permissions', ['isAllowed'], [$this->getApp()]);
        $permissions->expects($this->any())
            ->method('isAllowed')
            ->will($this->returnValue(true));
        $this->setService('permissions', $permissions);

        $this->setRequest(Request::create('/bolt/editcontent/pages/4'));
        $response = $this->controller()->edit($this->getRequest(), 'pages', 4);
        $context = $response->getContext();
        $this->assertEquals('Pages', $context['context']['contenttype']['name']);
        $this->assertInstanceOf('Bolt\Storage\Entity\Content', $context['context']['content']);

        // Test creation
        $this->setRequest(Request::create('/bolt/editcontent/pages'));
        $response = $this->controller()->edit($this->getRequest(), 'pages', null);
        $context = $response->getContext();
        $this->assertEquals('Pages', $context['context']['contenttype']['name']);
        $this->assertInstanceOf('Bolt\Storage\Entity\Content', $context['context']['content']);
        $this->assertNull($context['context']['content']->id);

        // Test that non-existent throws a redirect
        $this->setRequest(Request::create('/bolt/editcontent/pages/310'));
        $response = $this->controller()->edit($this->getRequest(), 'pages', 310);
        $this->assertEquals(302, $response->getStatusCode());
    }

    public function testEditDuplicate()
    {
        // Since we're the test user we won't automatically have permission to edit.
        $permissions = $this->getMock('Bolt\AccessControl\Permissions', ['isAllowed'], [$this->getApp()]);
        $permissions->expects($this->any())
            ->method('isAllowed')
            ->will($this->returnValue(true));
        $this->setService('permissions', $permissions);

        $this->setRequest(Request::create('/bolt/editcontent/pages/4', 'GET', ['duplicate' => true]));
        $original = $this->getService('storage')->getContent('pages/4');
        $response = $this->controller()->edit($this->getRequest(), 'pages', 4);
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
        $csrf = $this->getMock(CsrfTokenManager::class, ['isTokenValid'], [null, new SessionTokenStorage(new Session(new MockArraySessionStorage()))]);
        $csrf->expects($this->any())
            ->method('isTokenValid')
            ->will($this->returnValue(false));
        $this->setService('csrf', $csrf);

        $permissions = $this->getMock('Bolt\AccessControl\Permissions', ['isAllowed'], [$this->getApp()]);
        $permissions->expects($this->any())
            ->method('isAllowed')
            ->will($this->returnValue(true));
        $this->setService('permissions', $permissions);

        $this->setRequest(Request::create('/bolt/editcontent/showcases/3', 'POST'));
        $this->setExpectedException('Symfony\Component\HttpKernel\Exception\HttpException', 'Something went wrong');
        $this->controller()->edit($this->getRequest(), 'showcases', 3);
    }

    public function testEditPermissions()
    {
        $users = $this->getMock('Bolt\Users', ['checkAntiCSRFToken'], [$this->getApp()]);
        $users->expects($this->any())
            ->method('checkAntiCSRFToken')
            ->will($this->returnValue(true));
        $this->setService('users', $users);

        $permissions = $this->getMock('Bolt\AccessControl\Permissions', ['isAllowed'], [$this->getApp()]);
        $permissions->expects($this->any())
            ->method('isAllowed')
            ->will($this->returnValue(false));
        $this->setService('permissions', $permissions);

        // We should get kicked here because we dont have permissions to edit this
        $this->setRequest(Request::create('/bolt/editcontent/showcases/3', 'POST'));
        $response = $this->controller()->edit($this->getRequest(), 'showcases', 3);
        $this->assertEquals('/bolt', $response->getTargetUrl());
    }

    public function testEditPost()
    {
        $csrf = $this->getMock(CsrfTokenManager::class, ['isTokenValid'], [null, new SessionTokenStorage(new Session(new MockArraySessionStorage()))]);
        $csrf->expects($this->any())
            ->method('isTokenValid')
            ->will($this->returnValue(true));
        $this->setService('csrf', $csrf);

        $users = $this->getMock('Bolt\Users', ['checkAntiCSRFToken'], [$this->getApp()]);
        $users->expects($this->any())
            ->method('checkAntiCSRFToken')
            ->will($this->returnValue(true));
        $this->setService('users', $users);

        $permissions = $this->getMock('Bolt\AccessControl\Permissions', ['isAllowed'], [$this->getApp()]);
        $permissions->expects($this->any())
            ->method('isAllowed')
            ->will($this->returnValue(true));
        $this->setService('permissions', $permissions);

        $this->setRequest(Request::create('/bolt/editcontent/showcases/3', 'POST', ['floatfield' => 1.2]));
        $response = $this->controller()->edit($this->getRequest(), 'showcases', 3);
        $this->assertEquals('/bolt/overview/showcases', $response->getTargetUrl());
    }

    public function testEditPostAjax()
    {
        $csrf = $this->getMock(CsrfTokenManager::class, ['isTokenValid'], [null, new SessionTokenStorage(new Session(new MockArraySessionStorage()))]);
        $csrf->expects($this->any())
            ->method('isTokenValid')
            ->will($this->returnValue(true));
        $this->setService('csrf', $csrf);

        $users = $this->getMock('Bolt\Users', ['checkAntiCSRFToken'], [$this->getApp()]);
        $users->expects($this->any())
            ->method('checkAntiCSRFToken')
            ->will($this->returnValue(true));
        $this->setService('users', $users);

        // Since we're the test user we won't automatically have permission to edit.
        $permissions = $this->getMock('Bolt\AccessControl\Permissions', ['isAllowed'], [$this->getApp()]);
        $permissions->expects($this->any())
            ->method('isAllowed')
            ->will($this->returnValue(true));
        $this->setService('permissions', $permissions);

        // We use ?returnto=test here as that is handled exactly the same as
        // ?returnto=ajax except that it doesn't flush output buffers which we
        // require to ensure the JSON response is clean from debug or error
        // output, but PHPUnit marks the test "Risky"
        $this->setRequest(Request::create('/bolt/editcontent/pages/4?returnto=test', 'POST'));
        $original = $this->getService('storage')->getContent('pages/4');
        $response = $this->controller()->edit($this->getRequest(), 'pages', 4);
        $returned = json_decode($response->getContent());

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\JsonResponse', $response);
        $this->assertEquals($original['title'], $returned->title);
    }

    public function testOverview()
    {
        $this->setRequest(Request::create('/bolt/overview/pages'));
        $response = $this->controller()->overview($this->getRequest(), 'pages');
        $context = $response->getContext();
        $this->assertEquals('Pages', $context['context']['contenttype']['name']);
        $this->assertGreaterThan(1, count($context['context']['multiplecontent']));

        // Test the the default records per page can be set
        $this->setRequest(Request::create('/bolt/overview/showcases'));
        $this->controller()->overview($this->getRequest(), 'showcases');

        // Test redirect when user isn't allowed.
        $permissions = $this->getMock('Bolt\AccessControl\Permissions', ['isAllowed'], [$this->getApp()]);
        $permissions->expects($this->any())
            ->method('isAllowed')
            ->will($this->returnValue(false));
        $this->setService('permissions', $permissions);

        $this->setRequest(Request::create('/bolt/overview/pages'));
        $response = $this->controller()->overview($this->getRequest(), 'pages');

        $this->assertEquals('/bolt', $response->getTargetUrl());
    }

    public function testOverviewFiltering()
    {
        $this->setRequest(Request::create(
            '/bolt/overview/pages',
            'GET',
            [
                'filter'            => 'Lorem',
                'taxonomy-groups' => 'main',
            ]
        ));
        $response = $this->controller()->overview($this->getRequest(), 'pages');
        $context = $response->getContext();

        $this->assertArrayHasKey('filter', $context['context']);
        $this->assertEquals('Lorem', $context['context']['filter'][0]);
        $this->assertEquals('main', $context['context']['filter']['groups']);
    }

    public function testRelated()
    {
        $this->setRequest(Request::create('/bolt/relatedto/showcases/1'));
        $response = $this->controller()->related($this->getRequest(), 'showcases', 1);
        $context = $response->getContext();
        $this->assertEquals(1, $context['context']['id']);
        $this->assertEquals('Showcase', $context['context']['name']);
        $this->assertEquals('Showcases', $context['context']['contenttype']['name']);
        $this->assertEquals(2, count($context['context']['relations']));
        // By default we show the first one
        $this->assertEquals('Entries', $context['context']['show_contenttype']['name']);

        // Now we specify we want to see pages
        $this->setRequest(Request::create('/bolt/relatedto/showcases/1', 'GET', ['show' => 'pages']));
        $response = $this->controller()->related($this->getRequest(), 'showcases', 1);
        $context = $response->getContext();
        $this->assertEquals('Pages', $context['context']['show_contenttype']['name']);

        // Try a request where there are no relations
        $this->setRequest(Request::create('/bolt/relatedto/pages/1'));
        $response = $this->controller()->related($this->getRequest(), 'pages', 1);
        $context = $response->getContext();
        $this->assertNull($context['context']['relations']);

        // Test redirect when user isn't allowed.
        $permissions = $this->getMock('Bolt\AccessControl\Permissions', ['isAllowed'], [$this->getApp()]);
        $permissions->expects($this->any())
            ->method('isAllowed')
            ->will($this->returnValue(false));
        $this->setService('permissions', $permissions);

        $this->setRequest(Request::create('/bolt/relatedto/showcases/1'));
        $response = $this->controller()->related($this->getRequest(), 'showcases', 1);
        $this->assertEquals('/bolt', $response->getTargetUrl());
    }

    /**
     * @return \Bolt\Controller\Backend\Records
     */
    protected function controller()
    {
        return $this->getService('controller.backend.records');
    }
}
