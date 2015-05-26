<?php
namespace Bolt\Tests\Users;

use Bolt\Tests\BoltUnitTest;

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

    /**
     * @covers Bolt\Users::login
     */
    public function testLoginWithUsername()
    {
        // Setup test
        $users = $this->getMock('Bolt\AccessControl\Authentication', ['login'], [$this->getApp()]);
        $users->expects($this->once())->method('login')->willReturn(true);

        // Run test
        $result = $users->login('anotheruser', 'test123');

        // Check result
        $this->assertEquals(true, $result);
    }

    /**
     * @covers Bolt\Users::login
     */
    public function testLoginWithEmail()
    {
        // Setup test
        $users = $this->getMock('Bolt\AccessControl\Authentication', ['login'], [$this->getApp()]);
        $users->expects($this->once())->method('login')->willReturn(true);

        // Run test
        $result = $users->login('test@example.com', 'test123');

        // Check result
        $this->assertEquals(true, $result);
    }
}
