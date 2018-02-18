<?php

namespace Bolt\Tests\Controller\Backend;

use Bolt\Common\Json;
use Bolt\Storage\Entity;
use Bolt\Tests\Controller\ControllerUnitTest;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

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
    }

    public function testEditNotPermitted()
    {
        // Since we're the test user we won't automatically have permission to edit.
        $token = $this->getService('csrf')->getToken('content_edit');
        $this->setRequest(Request::create('/bolt/editcontent/pages/4', 'POST', [
            'content_edit' => [
                'save'   => 1,
                '_token' => $token,
            ],
        ]));
        $response = $this->controller()->edit($this->getRequest(), 'pages', 4);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/bolt', $response->getTargetUrl());
    }

    public function testEditCreate()
    {
        $permissions = $this->getMockPermissions();
        $permissions->expects($this->any())
            ->method('isAllowed')
            ->will($this->returnValue(true));
        $this->setService('permissions', $permissions);

        $token = $this->getService('csrf')->getToken('content_edit');
        $request = Request::create('/bolt/editcontent/pages', 'POST', [
            'content_edit' => [
                'save'   => 1,
                '_token' => $token,
            ],
        ]);
        $this->setRequest($request);

        // Test creation
        $this->setRequest(Request::create('/bolt/editcontent/pages'));
        $response = $this->controller()->edit($this->getRequest(), 'pages', null);
        $context = $response->getContext();
        $this->assertEquals('Pages', $context['context']['contenttype']['name']);
        $this->assertInstanceOf(Entity\Content::class, $context['context']['content']);
        $this->assertNull($context['context']['content']['id']);
    }

    public function testEditGetNonExisting()
    {
        // Test that non-existent throws a redirect
        $this->setRequest(Request::create('/bolt/editcontent/pages/310'));
        $response = $this->controller()->edit($this->getRequest(), 'pages', 310);
        $this->assertEquals(302, $response->getStatusCode());
    }

    public function testEditDuplicate()
    {
        // Since we're the test user we won't automatically have permission to edit.
        $permissions = $this->getMockPermissions();
        $permissions->expects($this->any())
            ->method('isAllowed')
            ->will($this->returnValue(true));
        $this->setService('permissions', $permissions);

        $token = $this->getService('csrf')->getToken('content_edit');
        $request = Request::create('/bolt/editcontent/pages', 'POST', [

            'title'        => 'Dangers to watch for',
            'body'         => 'Drop bear',
            'teaser'       => 'They sneak up on you',
            'content_edit' => [
                'save'   => 1,
                '_token' => $token,
            ],
        ]);
        $this->setRequest($request);

        // Save a test record
        $response = $this->controller()->edit($this->getRequest(), 'pages', null);
        $this->assertInstanceOf(RedirectResponse::class, $response);

        $parts = explode('/', $response->getTargetUrl());
        $newId = array_pop($parts);
        $request = Request::create('/bolt/editcontent/pages' . $newId, 'GET', [
            'source'    => $newId,
            'duplicate' => true,
        ]);
        $this->setRequest($request);

        $original = $this->getService('storage')->getContent('page/' . $newId);
        $response = $this->controller()->edit($this->getRequest(), 'pages', $newId);
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

    public function testEditPermissions()
    {
        $users = $this->getMockUsers(['checkAntiCSRFToken']);
        $users->expects($this->any())
            ->method('checkAntiCSRFToken')
            ->will($this->returnValue(true));
        $this->setService('users', $users);

        $permissions = $this->getMockPermissions();
        $permissions->expects($this->any())
            ->method('isAllowed')
            ->will($this->returnValue(false));
        $this->setService('permissions', $permissions);

        // We should get kicked here because we dont have permissions to edit this
        $this->setRequest(Request::create('/bolt/editcontent/showcases/3', 'POST'));
        $response = $this->controller()->edit($this->getRequest(), 'showcases', 3);
        $this->assertEquals('/bolt', $response->getTargetUrl());
    }

    public function testEditPostReturn()
    {
        $permissions = $this->getMockPermissions();
        $permissions->expects($this->any())
            ->method('isAllowed')
            ->will($this->returnValue(true));
        $this->setService('permissions', $permissions);

        $token = $this->getService('csrf')->getToken('content_edit');
        $request = Request::create('/bolt/editcontent/pages', 'POST', [
            'title'        => 'Koala Country',
            'slug'         => 'koala-country',
            'content_edit' => [
                'save_return' => 1,
                '_token'      => $token,
            ],
        ]);
        $this->setRequest($request);

        // Save a test record
        $response = $this->controller()->edit($this->getRequest(), 'showcases', null);
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/bolt/overview/showcases', $response->getTargetUrl());
    }

    public function testEditPostAjax()
    {
        // Since we're the test user we won't automatically have permission to edit.
        $permissions = $this->getMockPermissions();
        $permissions->expects($this->any())
            ->method('isAllowed')
            ->will($this->returnValue(true));
        $this->setService('permissions', $permissions);

        $token = $this->getService('csrf')->getToken('content_edit');
        $request = Request::create('/bolt/editcontent/pages', 'POST', [
            'title'        => 'Koala Country',
            'slug'         => 'koala-country',
            'content_edit' => [
                'save'   => 1,
                '_token' => $token,
            ],
        ]);
        $request->headers->set('X-Requested-With', 'XMLHttpRequest');
        $request->attributes->set('_test', true);
        $this->setRequest($request);

        // Save a test record
        $response = $this->controller()->edit($request, 'pages', null);

        $returned = Json::parse($response->getContent());
        $this->assertEquals('Koala Country', $returned['title']);
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
        $permissions = $this->getMockPermissions();
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
                'filter'          => 'Lorem',
                'taxonomy-groups' => 'main',
            ]
        ));
        $response = $this->controller()->overview($this->getRequest(), 'pages');
        $context = $response->getContext();

        $this->assertArrayHasKey('filter', $context['context']);
        $this->assertEquals('Lorem', $context['context']['filter'][0]);
        $this->assertEquals('main', $context['context']['filter']['groups']);
    }

    /**
     * @return \Bolt\Controller\Backend\Records
     */
    protected function controller()
    {
        return $this->getService('controller.backend.records');
    }
}
