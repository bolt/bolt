<?php
namespace Bolt\Tests\Controller\Backend;

use Bolt\Controllers\Backend\Users;
use Bolt\Permissions;
use Bolt\Tests\BoltUnitTest;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class to test correct operation of src/Controllers/Backend/Users.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 **/
class UsersTest extends BoltUnitTest
{
    public function setup()
    {
        $this->resetDb();
        $app = $this->getApp();
        $this->addDefaultUser($app);
    }

    public function testAdmin()
    {
        $app = $this->getApp();
        $controller = new Users();
        $controller->connect($app);

        $app['request'] = $request = Request::create('/bolt/users');
        $response = $controller->actionAdmin($request);
        $context = $response->getContext();
        $this->assertNotNull($context['context']['users']);
        $this->assertNotNull($context['context']['sessions']);
    }

    public function testEdit()
    {
        $app = $this->getApp();
        $controller = new Users();
        $controller->connect($app);

        $user = $app['users']->getUser(1);
        $app['users']->currentuser = $user;
        $app['request'] = $request = Request::create('/bolt/useredit/1');

        // This one should redirect because of permission failure
        $response = $controller->actionEdit($request, 1);
        $this->assertEquals('/bolt/users', $response->getTargetUrl());

        // Now we allow the permsission check to return true
        $perms = $this->getMock('Bolt\Permissions', array('isAllowedToManipulate'), array($app));
        $perms->expects($this->any())
            ->method('isAllowedToManipulate')
            ->will($this->returnValue(true));
        $app['permissions'] = $perms;

        $response = $controller->actionEdit($request, 1);
        $context = $response->getContext();
        $this->assertEquals('edit', $context['context']['kind']);
        $this->assertInstanceOf('Symfony\Component\Form\FormView', $context['context']['form']);
        $this->assertEquals('Admin', $context['context']['displayname']);

        // Test that an empty user gives a create form
        $app['request'] = $request = Request::create('/bolt/useredit');
        $response = $controller->actionEdit($request, null);
        $context = $response->getContext();
        $this->assertEquals('create', $context['context']['kind']);
    }

    public function testUserEditPost()
    {
        $app = $this->getApp();
        $controller = new Users();
        $controller->connect($app);

        $user = $app['users']->getUser(1);
        $app['users']->currentuser = $user;

        $perms = $this->getMock('Bolt\Permissions', array('isAllowedToManipulate'), array($app));
        $perms->expects($this->any())
            ->method('isAllowedToManipulate')
            ->will($this->returnValue(true));
        $app['permissions'] = $perms;

        // Symfony forms normally need a CSRF token so we have to mock this too
        $csrf = $this->getMock('Symfony\Component\Form\Extension\Csrf\CsrfProvider\DefaultCsrfProvider', array('isCsrfTokenValid', 'generateCsrfToken'), array('secret'));
        $csrf->expects($this->once())
            ->method('isCsrfTokenValid')
            ->will($this->returnValue(true));
        $app['form.csrf_provider'] = $csrf;

        // Update the display name via a POST request
        $app['request'] = $request = Request::create(
            '/bolt/useredit/1',
            'POST',
            array(
                'form' => array(
                    'id'          => $user['id'],
                    'username'    => $user['username'],
                    'email'       => $user['email'],
                    'displayname' => "Admin Test",
                    '_token'      => 'xyz'
                )
            )
        );

        $response = $controller->actionEdit($request, 1);
        $this->assertEquals('/bolt/users', $response->getTargetUrl());
    }

    public function testFirst()
    {
        $app = $this->getApp();
        $controller = new Users();
        $controller->connect($app);

        // Symfony forms need a CSRF token so we have to mock this too
        $csrf = $this->getMock('Symfony\Component\Form\Extension\Csrf\CsrfProvider\DefaultCsrfProvider', array('isCsrfTokenValid', 'generateCsrfToken'), array('secret'));
        $csrf->expects($this->any())
            ->method('isCsrfTokenValid')
            ->will($this->returnValue(true));

        $csrf->expects($this->any())
            ->method('generateCsrfToken')
            ->will($this->returnValue('xyz'));

        $app['form.csrf_provider'] = $csrf;

        // Because we have users in the database this should exit at first attempt
        $app['request'] = $request = Request::create('/bolt/userfirst');
        $response = $controller->actionFirst($request);
        $this->assertEquals('/bolt', $response->getTargetUrl());

        // Now we delete the users
        $app['db']->executeQuery('DELETE FROM bolt_users;');
        $app['users']->users = array();

        $app['request'] = $request = Request::create('/bolt/userfirst');
        $response = $controller->actionFirst($request);
        $context = $response->getContext();
        $this->assertEquals('create', $context['context']['kind']);

        // This block attempts to create the user


        $app['request'] = $request = Request::create(
            '/bolt/userfirst',
            'POST',
            array(
                'form' => array(
                    'username'              => 'admin',
                    'email'                 => 'test@example.com',
                    'displayname'           => 'Admin',
                    'password'              => 'password',
                    'password_confirmation' => 'password',
                    '_token'                => 'xyz'
                )
            )
        );
        $response = $controller->actionFirst($request);
        $this->assertEquals('/bolt', $response->getTargetUrl());
    }

    public function testModify()
    {
        $app = $this->getApp();
        $controller = new Users();
        $controller->connect($app);

        // First test should exit/redirect with no anti CSRF token
        $app['request'] = $request = Request::create('/bolt/user/disable/2');
        $response = $controller->actionModify($request, 'disable', 1);
        $info = $app['session']->getFlashBag()->get('info');
        $this->assertRegexp('/An error occurred/', $info[0]);
        $this->assertEquals('/bolt/users', $response->getTargetUrl());

        $app = $this->getApp();
        $controller = new Users();
        $controller->connect($app);

        // Now we mock the CSRF token to validate
        $users = $this->getMock('Bolt\Users', array('checkAntiCSRFToken'), array($app));
        $users->expects($this->any())
            ->method('checkAntiCSRFToken')
            ->will($this->returnValue(true));
        $app['users'] = $users;

        $currentuser = $app['users']->getUser(1);
        $app['users']->currentuser = $currentuser;

        // This request should fail because the user doesnt exist.
        $app['request'] = $request = Request::create('/bolt/user/disable/2');
        $response = $controller->actionModify($request, 'disable', 2);
        $this->assertEquals('/bolt/users', $response->getTargetUrl());
        $err = $app['session']->getFlashBag()->get('error');
        $this->assertRegexp('/No such user/', $err[0]);

        // This check will fail because we are operating on the current user
        $app['request'] = $request = Request::create('/bolt/user/disable/1');
        $response = $controller->actionModify($request, 'disable', 1);
        $this->assertEquals('/bolt/users', $response->getTargetUrl());
        $err = $app['session']->getFlashBag()->get('error');
        $this->assertRegexp('/yourself/', $err[0]);

        // We add a new user that isn't the current user and now perform operations.
        $this->addNewUser($app, 'editor', 'Editor', 'editor');

        // And retry the operation that will work now
        $app['request'] = $request = Request::create('/bolt/user/disable/2');
        $response = $controller->actionModify($request, 'disable', 2);

        $info = $app['session']->getFlashBag()->get('info');
        $this->assertRegexp('/is disabled/', $info[0]);
        $this->assertEquals('/bolt/users', $response->getTargetUrl());

        // Now try to enable the user
        $app['request'] = $request = Request::create('/bolt/user/enable/2');
        $response = $controller->actionModify($request, 'enable', 2);
        $info = $app['session']->getFlashBag()->get('info');
        $this->assertRegexp('/is enabled/', $info[0]);
        $this->assertEquals('/bolt/users', $response->getTargetUrl());

        // Try a non-existent action, make sure we get an error
        $app['request'] = $request = Request::create('/bolt/user/enhance/2');
        $response = $controller->actionModify($request, 'enhance', 2);
        $info = $app['session']->getFlashBag()->get('error');
        $this->assertRegexp('/No such action/', $info[0]);
        $this->assertEquals('/bolt/users', $response->getTargetUrl());

        // Now we run a delete action
        $app['request'] = $request = Request::create('/bolt/user/delete/2');
        $response = $controller->actionModify($request, 'delete', 2);
        $info = $app['session']->getFlashBag()->get('info');
        $this->assertRegexp('/is deleted/', $info[0]);
        $this->assertEquals('/bolt/users', $response->getTargetUrl());

        // Finally we mock the permsission check to return false and check
        // we get a priileges error.
        $perms = $this->getMock('Bolt\Permissions', array('isAllowedToManipulate'), array($app));
        $perms->expects($this->any())
            ->method('isAllowedToManipulate')
            ->will($this->returnValue(false));
        $app['permissions'] = $perms;

        $app['request'] = $request = Request::create('/bolt/user/disable/2');
        $response = $controller->actionModify($request, 'disable', 2);
        $this->assertEquals('/bolt/users', $response->getTargetUrl());
        $err = $app['session']->getFlashBag()->get('error');
        $this->assertRegexp('/right privileges/', $err[0]);
    }

    public function testModifyFailures()
    {
        $app = $this->getApp();
        $controller = new Users();
        $controller->connect($app);

        // We add a new user that isn't the current user and now perform operations.
        $this->addNewUser($app, 'editor', 'Editor', 'editor');

        // Now we mock the CSRF token to validate
        $users = $this->getMock('Bolt\Users', array('checkAntiCSRFToken', 'setEnabled', 'deleteUser'), array($app));
        $users->expects($this->any())
            ->method('checkAntiCSRFToken')
            ->will($this->returnValue(true));

        $users->expects($this->any())
            ->method('setEnabled')
            ->will($this->returnValue(false));

        $users->expects($this->any())
            ->method('deleteUser')
            ->will($this->returnValue(false));

        $app['users'] = $users;

        // Setup the current user
        $user = $app['users']->getUser(1);
        $app['users']->currentuser = $user;

        // This mocks a failure and ensures the error is reported
        $app['request'] = $request = Request::create('/bolt/user/disable/2');
        $response = $controller->actionModify($request, 'disable', 2);
        $info = $app['session']->getFlashBag()->get('info');
        $this->assertRegexp('/could not be disabled/', $info[0]);
        $this->assertEquals('/bolt/users', $response->getTargetUrl());

        $app['request'] = $request = Request::create('/bolt/user/enable/2');
        $response = $controller->actionModify($request, 'enable', 2);
        $info = $app['session']->getFlashBag()->get('info');
        $this->assertRegexp('/could not be enabled/', $info[0]);
        $this->assertEquals('/bolt/users', $response->getTargetUrl());

        $app['request'] = $request = Request::create('/bolt/user/delete/2');
        $response = $controller->actionModify($request, 'delete', 2);
        $info = $app['session']->getFlashBag()->get('info');
        $this->assertRegexp('/could not be deleted/', $info[0]);
        $this->assertEquals('/bolt/users', $response->getTargetUrl());
    }

    public function testProfile()
    {
        $app = $this->getApp();
        $controller = new Users();
        $controller->connect($app);

        // Symfony forms need a CSRF token so we have to mock this too
        $this->removeCSRF($app);
        $user = $app['users']->getUser(1);
        $app['users']->currentuser = $user;
        $app['request'] = $request = Request::create('/bolt/profile');
        $response = $controller->actionProfile($request);
        $context = $response->getContext();
        $this->assertEquals('edituser/edituser.twig', $response->getTemplateName());
        $this->assertEquals('profile', $context['context']['kind']);

        // Now try a POST to update the profile
        $app['request'] = $request = Request::create(
            '/bolt/profile',
            'POST',
            array(
                'form' => array(
                    'id'                    => 1,
                    'email'                 => $user['email'],
                    'password'              => '',
                    'password_confirmation' => '',
                    'displayname'           => "Admin Test",
                    '_token'                => 'xyz'
                )
            )
        );

        $response = $controller->actionProfile($request);
        $this->assertEquals('/bolt/profile', $response->getTargetUrl());
        $this->assertNotEmpty($app['session']->getFlashBag()->get('success'));
    }

    public function testUsernameEditKillsSession()
    {
        $app = $this->getApp();
        $controller = new Users();
        $controller->connect($app);

        $user = $app['users']->getUser(1);
        $app['users']->currentuser = $user;

        $perms = $this->getMock('Bolt\Permissions', array('isAllowedToManipulate'), array($app));
        $perms->expects($this->any())
            ->method('isAllowedToManipulate')
            ->will($this->returnValue(true));
        $app['permissions'] = $perms;

        // Symfony forms normally need a CSRF token so we have to mock this too
        $csrf = $this->getMock('Symfony\Component\Form\Extension\Csrf\CsrfProvider\DefaultCsrfProvider', array('isCsrfTokenValid', 'generateCsrfToken'), array('secret'));
        $csrf->expects($this->once())
            ->method('isCsrfTokenValid')
            ->will($this->returnValue(true));
        $app['form.csrf_provider'] = $csrf;

        // Update the display name via a POST request
        $app['request'] = $request = Request::create(
            '/bolt/useredit/1',
            'POST',
            array(
                'form' => array(
                    'id'          => $user['id'],
                    'username'    => 'admin2',
                    'email'       => $user['email'],
                    'displayname' => $user['displayname'],
                    '_token'      => 'xyz'
                )
            )
        );
        $response = $controller->actionEdit($request, 1);
        $this->assertEquals('/bolt/login', $response->getTargetUrl());
    }

    public function testViewRoles()
    {
        $app = $this->getApp();
        $controller = new Users();
        $controller->connect($app);

        $app['request'] = Request::create('/bolt/roles');
        $response = $controller->actionViewRoles();
        $context = $response->getContext();
        $this->assertEquals('roles/roles.twig', $response->getTemplateName());
        $this->assertNotEmpty($context['context']['global_permissions']);
        $this->assertNotEmpty($context['context']['effective_permissions']);
    }
}
