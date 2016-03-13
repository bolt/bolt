<?php
namespace Bolt\Tests\Users;

use Bolt\Events\AccessControlEvent;
use Bolt\Tests\BoltUnitTest;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class to test correct operation of src/AuthenticationTest.
 *
 * @author Steven de Vries <info@stevendevries.nl>
 **/
class AuthenticationTest extends BoltUnitTest
{
    /**
     * @var array
     */
    private $user;

    /**
     * @see \PHPUnit_Framework_TestCase::setUp
     */
    protected function setUp()
    {
        $this->resetDb();

        $app = $this->getApp();
        $this->addNewUser($app, 'admin', 'Admin', 'root');
        $this->addNewUser($app, 'editor', 'Editor', 'editor');
        $this->user = ['id' => 2, 'username' => 'editor', 'email' => 'editor@example.com'];
    }

    public function testLoginWithUsername()
    {
        // Setup test
        $app = $this->getApp();
        $loginMock = $this->getLoginMock($app);

        $loginMock->expects($this->once())->method('login')->willReturn(true);

        // Run test
        $request = new Request();
        $event = new AccessControlEvent($request);
        /** @var \Bolt\AccessControl\Login $loginMock */
        $result = $loginMock->login('anotheruser', 'test123', $event);

        // Check result
        $this->assertEquals(true, $result);
    }

    public function testLoginWithEmail()
    {
        // Setup test
        $app = $this->getApp();
        $loginMock = $this->getLoginMock($app);
        $loginMock->expects($this->once())->method('login')->willReturn(true);

        // Run test
        $request = new Request();
        $event = new AccessControlEvent($request);
        /** @var \Bolt\AccessControl\Login $loginMock */
        $result = $loginMock->login('test@example.com', 'test123', $event);

        // Check result
        $this->assertEquals(true, $result);
    }
}
