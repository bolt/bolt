<?php
namespace Bolt\Tests\Users;

use Bolt\Tests\BoltUnitTest;

/**
 * Class to test correct operation of src/Users.
 *
 * @author Steven de Vries <info@stevendevries.nl>
 **/
class UsersTest extends BoltUnitTest
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
     * @covers Bolt\Users::getUser
     */
    public function testGetUserById()
    {
        // Setup test
        $users = $this->getMock('Bolt\Users', ['getUsers'], [$this->getApp()]);

        // Run test
        $result = $users->getUser(2);

        // Check result
        $this->assertEquals($this->user['id'], $result['id']);
        $this->assertEquals($this->user['username'], $result['username']);
        $this->assertEquals($this->user['email'], $result['email']);
    }

    /**
     * @covers Bolt\Users::getUser
     */
    public function testGetUserByUnknownId()
    {
        // Setup test
        $users = $this->getMock('Bolt\Users', ['getUsers'], [$this->getApp()]);

        // Run test
        $result = $users->getUser(0);

        // Check result
        $this->assertEquals(false, $result);
    }

    /**
     * @covers Bolt\Users::getUser
     */
    public function testGetUserByUsername()
    {
        // Setup test
        $users = $this->getMock('Bolt\Users', ['getUsers'], [$this->getApp()]);

        // Run test
        $result = $users->getUser('editor');

        // Check result
        $this->assertEquals($this->user['id'], $result['id']);
        $this->assertEquals($this->user['username'], $result['username']);
        $this->assertEquals($this->user['email'], $result['email']);
    }

    /**
     * @covers Bolt\Users::getUser
     */
    public function testGetUserByUnknownUsername()
    {
        // Setup test
        $users = $this->getMock('Bolt\Users', ['getUsers'], [$this->getApp()]);

        // Run test
        $result = $users->getUser('anotheruser');

        // Check result
        $this->assertEquals(false, $result);
    }
}
