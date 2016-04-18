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
        $this->addNewUser($this->getApp(), 'disabledadmin', 'DisabeldAdmin', 'root', false);

        $users = $this->getMock('Bolt\Users', ['getUsers'], [$this->getApp()]);
        $result = $users->getUser('disabledadmin');

        $this->assertFalse($result['enabled'], 'User was setup but is still enabled even after wanting to disable the user');
    }

    public function testCreateEnabledUser()
    {
        $this->addNewUser($this->getApp(), 'enabledadmin', 'EnabledAdmin', 'root', true);

        $users = $this->getMock('Bolt\Users', ['getUsers'], [$this->getApp()]);
        $result = $users->getUser('enabledadmin');

        $this->assertTrue($result['enabled'], 'User was setup but is disabled even after wanting to enable the user');
    }
}
