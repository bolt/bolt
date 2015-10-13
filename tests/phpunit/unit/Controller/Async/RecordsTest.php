<?php
namespace Bolt\Tests\Controller\Async;

use Bolt\Tests\Controller\ControllerUnitTest;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class to test correct operation of src/Controller/Async/Records.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class RecordsTest extends ControllerUnitTest
{
    public function testDelete()
    {
        $this->markTestSkipped('TODO');

        $this->setRequest(Request::create('/bolt/deletecontent/pages/4'));
        $response = $this->controller()->modify($this->getRequest(), 'pages', 4);

        // This one should fail for permissions
        $this->assertEquals('/bolt/overview/pages', $response->getTargetUrl());
        $err = $this->getFlashBag()->get('error');
        $this->assertRegExp('/denied/', $err[0]);

        $permissions = $this->getMock('Bolt\AccessControl\Permissions', ['isAllowed'], [$this->getApp()]);
        $permissions->expects($this->any())
            ->method('isAllowed')
            ->will($this->returnValue(true));
        $this->setService('permissions', $permissions);

        // This one should get killed by the anti CSRF check
        $response = $this->controller()->modify($this->getRequest(), 'pages', 4);
        $this->assertEquals('/bolt/overview/pages', $response->getTargetUrl());
        $err = $this->getFlashBag()->get('info');
        $this->assertRegExp('/could not be deleted/', $err[0]);

        $users = $this->getMock('Bolt\Users', ['checkAntiCSRFToken'], [$this->getApp()]);
        $users->expects($this->any())
            ->method('checkAntiCSRFToken')
            ->will($this->returnValue(true));
        $this->setService('users', $users);

        $response = $this->controller()->modify($this->getRequest(), 'pages', 4);
        $this->assertEquals('/bolt/overview/pages', $response->getTargetUrl());
        $err = $this->getFlashBag()->get('info');
        $this->assertRegExp('/has been deleted/', $err[0]);
    }

    public function testModify()
    {
        $this->markTestSkipped('TODO');

        // Try status switches
        $this->setRequest(Request::create('/bolt/content/held/pages/3'));

        // This one should fail for lack of permission
        $response = $this->controller()->modify($this->getRequest(), 'held', 'pages', 3);
        $this->assertEquals('/bolt/overview/pages', $response->getTargetUrl());
        $err = $this->getFlashBag()->get('error');
        $this->assertRegExp('/right privileges/', $err[0]);

        $users = $this->getMock('Bolt\Users', ['checkAntiCSRFToken', 'isContentStatusTransitionAllowed'], [$this->getApp()]);
        $this->setService('users', $users);

        $permissions = $this->getMock('Bolt\AccessControl\Permissions', ['isAllowed'], [$this->getApp()]);
        $permissions->expects($this->any())
            ->method('isAllowed')
            ->will($this->returnValue(true));
        $this->setService('permissions', $permissions);

        // This one should fail for the second permission check `isContentStatusTransitionAllowed`
        $response = $this->controller()->modify($this->getRequest(), 'held', 'pages', 3);
        $this->assertEquals('/bolt/overview/pages', $response->getTargetUrl());
        $err = $this->getFlashBag()->get('error');
        $this->assertRegExp('/right privileges/', $err[0]);

        $this->getService('users')->expects($this->any())
            ->method('isContentStatusTransitionAllowed')
            ->will($this->returnValue(true));

        $response = $this->controller()->modify($this->getRequest(), 'held', 'pages', 3);
        $this->assertEquals('/bolt/overview/pages', $response->getTargetUrl());
        $err = $this->getFlashBag()->get('info');
        $this->assertRegExp('/has been changed/', $err[0]);

        // Test an invalid action fails
        $this->setRequest(Request::create('/bolt/content/fake/pages/3'));
        $this->controller()->modify($this->getRequest(), 'fake', 'pages', 3);
        $err = $this->getFlashBag()->get('error');
        $this->assertRegExp('/No such action/', $err[0]);

        // Test that any save error gets reported
        $this->setRequest(Request::create('/bolt/content/held/pages/3'));
        $permissions = $this->getMock('Bolt\AccessControl\Permissions', ['isAllowed'], [$this->getApp()]);
        $permissions->expects($this->any())
            ->method('isAllowed')
            ->will($this->returnValue(false));
        $this->setService('permissions', $permissions);

        // Test that we can't depublish "held" a record
        $response = $this->controller()->modify($this->getRequest(), 'held', 'pages', 3);
        $this->assertEquals('/bolt/overview/pages', $response->getTargetUrl());
        $err = $this->getFlashBag()->get('error');
        $this->assertRegExp('/You do not have the right privileges to depublish that record./', $err[0]);

        // Test the delete proxy action
        // Note that the response will be 'could not be deleted'. Since this just
        // passes on the the deleteContent method that is enough to indicate that
        // the work of this method is done.
        $this->setRequest(Request::create('/bolt/content/delete/pages/3'));
        $response = $this->controller()->modify($this->getRequest(), 'delete', 'pages', 3);
        $this->assertEquals('/bolt/overview/pages', $response->getTargetUrl());
        $err = $this->getFlashBag()->get('error');
        $this->assertRegExp('/Permission denied/', (string) $err[0]);
    }

    /**
     * @return \Bolt\Controller\Async\Records
     */
    protected function controller()
    {
        return $this->getService('controller.async.records');
    }
}
