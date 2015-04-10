<?php
namespace Bolt\Tests\Controller;

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
        $this->user = array('id'=>5, 'username'=>'test', 'email' => 'test@example.com');
    }

    /**
     * @covers Bolt\Users::GetUserById
     */
    public function testGetUserById()
    {
        //setup test
        $users = $this->getMock('Bolt\Users', ['getUsers']);
        $users->expects($this->once())->method('getUsers')->willReturn(array($this->user));
        
        //run test
        $result = $users->GetUserById(5);

        //check result
        $this->assertEquals($this->user, $result);
    }

    /**
     * @covers Bolt\Users::GetUserById
     */
    public function testGetUserByUnknownId()
    {
        //setup test
        $users = $this->getMock('Bolt\Users', ['getUsers']);
        $users->expects($this->once())->method('getUsers')->willReturn(array($this->user));
        
        //run test
        $result = $users->GetUserById(2);

        //check result
        $this->assertEquals(false, $result);
    }

    /**
     * @covers Bolt\Users::GetUser
     */
    public function testGetUserWithEmail()
    {
        //setup test
        $users = $this->getMock('Bolt\Users', ['getUsers']);
        $users->expects($this->once())->method('getUsers')->willReturn(array($this->user));
        
        //run test
        $result = $users->GetUser('test@example.com');

        //check result
        $this->assertEquals($this->user, $result);
    }

    /**
     * @covers Bolt\Users::GetUser
     */
    public function testGetUserWithId()
    {
        //setup test
        $users = $this->getMock('Bolt\Users', ['getUsers']);
        $users->expects($this->once())->method('getUsers')->willReturn(array($this->user));
        
        //run test
        $result = $users->GetUser(10);

        //check result
        $this->assertEquals($this->user, $result);
    }

    /**
     * @covers Bolt\Users::getUserByUsername
     */
    public function testGetUserByUsername()
    {
        //setup test
        $users = $this->getMock('Bolt\Users', ['getUsers']);
        $users->expects($this->once())->method('getUsers')->willReturn(array($this->user));
        
        //run test
        $result = $users->getUserByUsername('test');

        //check result
        $this->assertEquals($this->user, $result);
    }

    /**
     * @covers Bolt\Users::getUserByUsername
     */
    public function testGetUserByUnknownUsername()
    {
        //setup test
        $users = $this->getMock('Bolt\Users', ['getUsers']);
        $users->expects($this->once())->method('getUsers')->willReturn(array($this->user));
        
        //run test
        $result = $users->getUserByUsername('anotheruser');

        //check result
        $this->assertEquals(false, $result);
    }

    /**
     * @covers Bolt\Users::login
     */
    public function testLoginWithUsername()
    {
        //setup test
        $users = $this->getMock('Bolt\Users', ['loginUsername', 'loginEmail']);
        $users->expects($this->once())->method('loginUsername')->willReturn(true);
        $users->expects($this->never())->method('loginEmail');
        
        //run test
        $result = $users->login('anotheruser', 'test123');

        //check result
        $this->assertEquals(true, $result);
    }

    /**
     * @covers Bolt\Users::login
     */
    public function testLoginWithEmail()
    {
        //setup test
        $users = $this->getMock('Bolt\Users', ['loginUsername', 'loginEmail']);
        $users->expects($this->once())->method('loginEmail')->willReturn(true);
        $users->expects($this->never())->method('loginUsername');

        //run test
        $result = $users->login('test@example.com', 'test123');

        //check result
        $this->assertEquals(true, $result);
    }
}
