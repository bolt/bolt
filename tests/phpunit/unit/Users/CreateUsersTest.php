<?php
namespace Bolt\Tests\Users;

use Bolt\Tests\BoltUnitTest;

/**
 * Test creating new users
 *
 * @author Chris Hilsdon <chris@koolserve.uk>
 **/
class CreateUsersTest extends BoltUnitTest
{
    /**
     * @see \PHPUnit_Framework_TestCase::setUp
     */
    protected function setUp()
    {
        $this->resetDb();
    }

    public function testCreateDisabledUser()
    {
        $this->addNewUser($this->getApp(), 'diabeldadmin', 'DiabeldAdmin', 'root', 0);

        // Setup test
        $users = $this->getMock('Bolt\Users', ['getUsers'], [$this->getApp()]);

        // Run test
        $result = $users->getUser('diabeldadmin');

        // Check result
        $this->assertFalse($result['enabled'], 'User was setup but is still enabled even after wanting to disable the user');
    }

    public function testCreateEnabledUser()
    {
        $this->addNewUser($this->getApp(), 'enabledadmin', 'EnabledAdmin', 'root', 1);

        // Setup test
        $users = $this->getMock('Bolt\Users', ['getUsers'], [$this->getApp()]);

        // Run test
        $result = $users->getUser('enabledadmin');

        // Check result
        $this->assertTrue($result['enabled'], 'User was setup but is disabled even after wanting to enable the user');
    }
}
