<?php

namespace Bolt\Tests\Controller\Async;

use Bolt\AccessControl\Permissions;
use Bolt\Storage\Entity\Entity;
use Bolt\Tests\Controller\ControllerUnitTest;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Csrf\CsrfTokenManager;

/**
 * Class to test correct operation of src/Controller/Async/Records.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class RecordsTest extends ControllerUnitTest
{
    public function testDelete()
    {
        $this->addSomeContent();
        $csrf = $this->getMockBuilder(CsrfTokenManager::class)
            ->disableOriginalConstructor()
            ->setMethods(['isTokenValid'])
            ->getMock()
        ;
        $csrf->expects($this->atLeastOnce())
            ->method('isTokenValid')
            ->willReturn(true)
        ;
        $this->setService('csrf', $csrf);

        $request = Request::create('/async/content/action');
        $request->setMethod('POST');
        $request->attributes->add([
            'contenttype' => 'pages',
            'actions'     => [
                'showcases' => [
                    1 => ['delete' => null],
                ],
            ],
        ]);

        $this->setRequest($request);
        $this->assertInstanceOf(Entity::class, $this->getService('storage')->getRepository('showcases')->findOneBy(['id' => 1]));

        // This one should fail for permissions
        $this->controller()->action($this->getRequest());
        $err = $this->getFlashBag()->get('error');
        $this->assertRegExp('/could not be modified/', $err[0]);

        $permissions = $this->getMockBuilder(Permissions::class)
            ->disableOriginalConstructor()
            ->setMethods(['isAllowed'])
            ->getMock()
        ;
        $permissions->expects($this->any())
            ->method('isAllowed')
            ->will($this->returnValue(true));
        $this->setService('permissions', $permissions);

        $this->controller()->action($this->getRequest());

        $this->assertFalse($this->getService('storage')->getRepository('showcases')->findOneBy(['id' => 1]));
    }

    public function testModify()
    {
        $this->addSomeContent();
        $csrf = $this->getMockBuilder(CsrfTokenManager::class)
            ->disableOriginalConstructor()
            ->setMethods(['isTokenValid'])
            ->getMock()
        ;
        $csrf->expects($this->atLeastOnce())
            ->method('isTokenValid')
            ->willReturn(true)
        ;
        $this->setService('csrf', $csrf);

        $request = Request::create('/async/content/action');
        $request->setMethod('POST');
        $request->attributes->add([
            'contenttype' => 'pages',
            'actions'     => [
                'showcases' => [
                    1 => ['modify' => ['title' => 'Drop Bear Attacks']],
                ],
            ],
        ]);

        $this->setRequest($request);
        $entityA = $this->getService('storage')->getRepository('showcases')->findOneBy(['id' => 1]);
        $this->assertInstanceOf(Entity::class, $entityA);
        $this->assertNotEquals('Drop Bear Attacks', $entityA->getTitle());

        // This one should fail for permissions
        $this->controller()->action($this->getRequest());
        $err = $this->getFlashBag()->get('error');
        $this->assertRegExp('/could not be modified/', $err[0]);

        $permissions = $this->getMockBuilder(Permissions::class)
            ->disableOriginalConstructor()
            ->setMethods(['isAllowed'])
            ->getMock()
        ;
        $permissions->expects($this->any())
            ->method('isAllowed')
            ->will($this->returnValue(true));
        $this->setService('permissions', $permissions);

        $this->controller()->action($this->getRequest());

        $entityB = $this->getService('storage')->getRepository('showcases')->findOneBy(['id' => 1]);
        $this->assertSame('Drop Bear Attacks', $entityB->getTitle());
    }

    /**
     * @return \Bolt\Controller\Async\Records
     */
    protected function controller()
    {
        return $this->getService('controller.async.records');
    }
}
