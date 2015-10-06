<?php
namespace Bolt\Tests\Controller\Backend;

use Bolt\Storage\Entity;
use Bolt\Tests\Controller\ControllerUnitTest;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class to test correct operation of src/Controller/Backend/Users.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 **/
class UsersTest extends ControllerUnitTest
{
    public function testAdmin()
    {
        $this->setRequest(Request::create('/bolt/users'));
        $response = $this->controller()->admin();

        $context = $response->getContext();
        $this->assertNotNull($context['context']['users']);
        $this->assertNotNull($context['context']['sessions']);
    }

    public function testEdit()
    {
        $user = $this->getService('users')->getUser(1);
        $this->setSessionUser(new Entity\Users($user));
        $this->setRequest(Request::create('/bolt/useredit/1'));

        // This one should redirect because of permission failure
        $response = $this->controller()->edit($this->getRequest(), 1);
        $this->assertEquals('/bolt/users', $response->getTargetUrl());

        // Now we allow the permsission check to return true
        $perms = $this->getMock('Bolt\AccessControl\Permissions', ['isAllowedToManipulate'], [$this->getApp()]);
        $perms->expects($this->any())
            ->method('isAllowedToManipulate')
            ->will($this->returnValue(true));
        $this->setService('permissions', $perms);

        $response = $this->controller()->edit($this->getRequest(), 1);
        $context = $response->getContext();
        $this->assertEquals('edit', $context['context']['kind']);
        $this->assertInstanceOf('Symfony\Component\Form\FormView', $context['context']['form']);
        $this->assertEquals('Admin', $context['context']['displayname']);

        // Test that an empty user gives a create form
        $this->setRequest(Request::create('/bolt/useredit'));
        $response = $this->controller()->edit($this->getRequest(), null);
        $context = $response->getContext();
        $this->assertEquals('create', $context['context']['kind']);
    }

    public function testUserEditPost()
    {
        $user = $this->getService('users')->getUser(1);
        $this->setSessionUser(new Entity\Users($user));

        $perms = $this->getMock('Bolt\AccessControl\Permissions', ['isAllowedToManipulate'], [$this->getApp()]);
        $perms->expects($this->any())
            ->method('isAllowedToManipulate')
            ->will($this->returnValue(true));
        $this->setService('permissions', $perms);

        // Symfony forms normally need a CSRF token so we have to mock this too
        $csrf = $this->getMock('Symfony\Component\Form\Extension\Csrf\CsrfProvider\DefaultCsrfProvider', ['isCsrfTokenValid', 'generateCsrfToken'], ['secret']);
        $csrf->expects($this->once())
            ->method('isCsrfTokenValid')
            ->will($this->returnValue(true));
        $this->setService('form.csrf_provider', $csrf);

        // Update the display name via a POST request
        $this->setRequest(Request::create(
            '/bolt/useredit/1',
            'POST',
            [
                'form' => [
                    'id'          => $user['id'],
                    'username'    => $user['username'],
                    'email'       => $user['email'],
                    'displayname' => 'Admin Test',
                    '_token'      => 'xyz'
                ]
            ]
        ));

        $response = $this->controller()->edit($this->getRequest(), 1);
        $this->assertEquals('/bolt/users', $response->getTargetUrl());
    }

    public function testFirst()
    {
        // Symfony forms need a CSRF token so we have to mock this too
        $csrf = $this->getMock('Symfony\Component\Form\Extension\Csrf\CsrfProvider\DefaultCsrfProvider', ['isCsrfTokenValid', 'generateCsrfToken'], ['secret']);
        $csrf->expects($this->any())
            ->method('isCsrfTokenValid')
            ->will($this->returnValue(true));

        $csrf->expects($this->any())
            ->method('generateCsrfToken')
            ->will($this->returnValue('xyz'));
        $this->setService('form.csrf_provider', $csrf);

        // Because we have users in the database this should exit at first attempt
        $this->setRequest(Request::create('/bolt/userfirst'));
        $response = $this->controller()->first($this->getRequest());
        $this->assertEquals('/bolt', $response->getTargetUrl());

        // Now we delete the users
        $this->getService('db')->executeQuery('DELETE FROM bolt_users;');
        $this->getService('users')->users = [];

        $this->setRequest(Request::create('/bolt/userfirst'));
        $response = $this->controller()->first($this->getRequest());
        $context = $response->getContext();
        $this->assertEquals('create', $context['context']['kind']);

        // This block attempts to create the user


        $this->setRequest(Request::create(
            '/bolt/userfirst',
            'POST',
            [
                'form' => [
                    'username'              => 'admin',
                    'email'                 => 'test@example.com',
                    'displayname'           => 'Admin',
                    'password'              => 'password',
                    'password_confirmation' => 'password',
                    '_token'                => 'xyz'
                ]
            ]
        ));
        $response = $this->controller()->first($this->getRequest());
        $this->assertEquals('/bolt', $response->getTargetUrl());
    }

    public function testModifyBadCsrf()
    {
        // First test should exit/redirect with no anti CSRF token
        $this->setRequest(Request::create('/bolt/user/disable/2'));
        $response = $this->controller()->modify('disable', 1);
        $info = $this->getFlashBag()->get('info');

        $this->assertRegExp('/An error occurred/', $info[0]);
        $this->assertEquals('/bolt/users', $response->getTargetUrl());
    }

    public function testModifyValidCsrf()
    {
        // Now we mock the CSRF token to validate
        $app = $this->getApp();
        $users = $this->getMock('Bolt\Users', ['checkAntiCSRFToken'], [$this->getApp()]);
        $users->expects($this->any())
            ->method('checkAntiCSRFToken')
            ->will($this->returnValue(true));
        $this->setService('users', $users);

        $currentuser = $this->getService('users')->getUser(1);
        $this->setSessionUser(new Entity\Users($currentuser));

        // This request should fail because the user doesnt exist.
        $this->setRequest(Request::create('/bolt/user/disable/2'));
        $response = $this->controller()->modify('disable', 42);

        $this->assertEquals('/bolt/users', $response->getTargetUrl());
        $err = $this->getFlashBag()->get('error');
        $this->assertRegExp('/No such user/', $err[0]);

        // This check will fail because we are operating on the current user
        $this->setRequest(Request::create('/bolt/user/disable/1'));
        $response = $this->controller()->modify('disable', 1);
        $this->assertEquals('/bolt/users', $response->getTargetUrl());
        $err = $this->getFlashBag()->get('error');
        $this->assertRegExp('/yourself/', $err[0]);

        // We add a new user that isn't the current user and now perform operations.
        $this->addNewUser($this->getApp(), 'editor', 'Editor', 'editor');
        $editor = $this->getService('users')->getUser('editor');

        // And retry the operation that will work now
        $this->setRequest(Request::create('/bolt/user/disable/2'));
        $response = $this->controller()->modify('disable', $editor['id']);

        $info = $this->getFlashBag()->get('info');
        $this->assertRegExp('/is disabled/', $info[0]);
        $this->assertEquals('/bolt/users', $response->getTargetUrl());

        // Now try to enable the user
        $this->setRequest(Request::create('/bolt/user/enable/2'));
        $response = $this->controller()->modify('enable', $editor['id']);
        $info = $this->getFlashBag()->get('info');
        $this->assertRegExp('/is enabled/', $info[0]);
        $this->assertEquals('/bolt/users', $response->getTargetUrl());

        // Try a non-existent action, make sure we get an error
        $this->setRequest(Request::create('/bolt/user/enhance/2'));
        $response = $this->controller()->modify('enhance', $editor['id']);
        $info = $this->getFlashBag()->get('error');
        $this->assertRegExp('/No such action/', $info[0]);
        $this->assertEquals('/bolt/users', $response->getTargetUrl());

        // Now we run a delete action
        $this->setRequest(Request::create('/bolt/user/delete/2'));
        $response = $this->controller()->modify('delete', $editor['id']);
        $info = $this->getFlashBag()->get('info');
        $this->assertRegExp('/is deleted/', $info[0]);
        $this->assertEquals('/bolt/users', $response->getTargetUrl());

        // Finally we mock the permsission check to return false and check
        // we get a priileges error.
        $this->addNewUser($this->getApp(), 'editor', 'Editor', 'editor');
        $editor = $this->getService('users')->getUser('editor');

        $perms = $this->getMock('Bolt\AccessControl\Permissions', ['isAllowedToManipulate'], [$this->getApp()]);
        $perms->expects($this->any())
            ->method('isAllowedToManipulate')
            ->will($this->returnValue(false));
        $this->setService('permissions', $perms);

        $this->setRequest(Request::create('/bolt/user/disable/' . $editor['id']));
        $response = $this->controller()->modify('disable', $editor['id']);

        $this->assertEquals('/bolt/users', $response->getTargetUrl());
        $err = $this->getFlashBag()->get('error');
        $this->assertRegExp('/right privileges/', $err[0]);
    }

    public function testModifyFailures()
    {
        // We add a new user that isn't the current user and now perform operations.
        $this->addNewUser($this->getApp(), 'editor', 'Editor', 'editor');

        // Now we mock the CSRF token to validate
        $users = $this->getMock('Bolt\Users', ['checkAntiCSRFToken', 'setEnabled', 'deleteUser'], [$this->getApp()]);
        $users->expects($this->any())
            ->method('checkAntiCSRFToken')
            ->will($this->returnValue(true));
        $users->expects($this->any())
            ->method('setEnabled')
            ->will($this->returnValue(false));
        $users->expects($this->any())
            ->method('deleteUser')
            ->will($this->returnValue(false));
        $this->setService('users', $users);

        // Setup the current user
        $user = $this->getService('users')->getUser(1);
        $this->setSessionUser(new Entity\Users($user));

        // This mocks a failure and ensures the error is reported
        $this->setRequest(Request::create('/bolt/user/disable/2'));
        $response = $this->controller()->modify('disable', 2);
        $info = $this->getFlashBag()->get('info');
        $this->assertRegExp('/could not be disabled/', $info[0]);
        $this->assertEquals('/bolt/users', $response->getTargetUrl());

        $this->setRequest(Request::create('/bolt/user/enable/2'));
        $response = $this->controller()->modify('enable', 2);
        $info = $this->getFlashBag()->get('info');
        $this->assertRegExp('/could not be enabled/', $info[0]);
        $this->assertEquals('/bolt/users', $response->getTargetUrl());

        $this->setRequest(Request::create('/bolt/user/delete/2'));
        $response = $this->controller()->modify('delete', 2);
        $info = $this->getFlashBag()->get('info');
        $this->assertRegExp('/could not be deleted/', $info[0]);
        $this->assertEquals('/bolt/users', $response->getTargetUrl());
    }

    public function testProfile()
    {
        // Symfony forms need a CSRF token so we have to mock this too
        $this->removeCSRF($this->getApp());
        $user = $this->getService('users')->getUser(1);
        $this->setSessionUser(new Entity\Users($user));
        $this->setRequest(Request::create('/bolt/profile'));
        $response = $this->controller()->profile($this->getRequest());
        $context = $response->getContext();
        $this->assertEquals('@bolt/edituser/edituser.twig', $response->getTemplateName());
        $this->assertEquals('profile', $context['context']['kind']);

        // Now try a POST to update the profile
        $this->setRequest(Request::create(
            '/bolt/profile',
            'POST',
            [
                'form' => [
                    'id'                    => 1,
                    'password'              => '',
                    'password_confirmation' => '',
                    'email'                 => $user['email'],
                    'displayname'           => 'Admin Test',
                    '_token'                => 'xyz'
                ]
            ]
        ));

        $this->controller()->profile($this->getRequest());
        $this->assertNotEmpty($this->getFlashBag()->get('success'));
    }

    public function testUsernameEditKillsSession()
    {
        $user = $this->getService('users')->getUser(1);
        $this->setSessionUser(new Entity\Users($user));

        $perms = $this->getMock('Bolt\AccessControl\Permissions', ['isAllowedToManipulate'], [$this->getApp()]);
        $perms->expects($this->any())
            ->method('isAllowedToManipulate')
            ->will($this->returnValue(true));
        $this->setService('permissions', $perms);

        // Symfony forms normally need a CSRF token so we have to mock this too
        $csrf = $this->getMock('Symfony\Component\Form\Extension\Csrf\CsrfProvider\DefaultCsrfProvider', ['isCsrfTokenValid', 'generateCsrfToken'], ['secret']);
        $csrf->expects($this->once())
            ->method('isCsrfTokenValid')
            ->will($this->returnValue(true));
        $this->setService('form.csrf_provider', $csrf);

        // Update the display name via a POST request
        $this->setRequest(Request::create(
            '/bolt/useredit/1',
            'POST',
            [
                'form' => [
                    'id'          => $user['id'],
                    'username'    => 'admin2',
                    'email'       => $user['email'],
                    'displayname' => $user['displayname'],
                    '_token'      => 'xyz'
                ]
            ]
        ));
        $response = $this->controller()->edit($this->getRequest(), 1);
        $this->assertEquals('/bolt/login', $response->getTargetUrl());
    }

    public function testViewRoles()
    {
        $this->setRequest(Request::create('/bolt/roles'));
        $response = $this->controller()->viewRoles();
        $context = $response->getContext();
        $this->assertEquals('@bolt/roles/roles.twig', $response->getTemplateName());
        $this->assertNotEmpty($context['context']['global_permissions']);
        $this->assertNotEmpty($context['context']['effective_permissions']);
    }

    /**
     * @return \Bolt\Controller\Backend\Users
     */
    protected function controller()
    {
        return $this->getService('controller.backend.users');
    }
}
